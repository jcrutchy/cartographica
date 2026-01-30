unit uWebSocketServer;

{$mode objfpc}{$H+}

interface

uses
  Forms, Classes, SysUtils, Sockets, Base64, SHA1, WinSock2, uLog;

type
  TWebSocketOpcode = (
    wsoContinuation = $0,
    wsoText         = $1,
    wsoBinary       = $2,
    wsoClose        = $8,
    wsoPing         = $9,
    wsoPong         = $A
  );

  TWSHeader = array[0..9] of Byte;

  TWebSocketConnectionThread = class;

  { Event types }

  TWSOnMessageEvent = procedure(AThread: TWebSocketConnectionThread; const AText: string) of object;
  TWSOnConnectEvent = procedure(AThread: TWebSocketConnectionThread) of object;
  TWSOnDisconnectEvent = procedure(AThread: TWebSocketConnectionThread) of object;

  { TWebSocketServer }

  TWebSocketServer = class(TThread)
  private
    FPort: Word;
    FListenSock: LongInt;
    FActive: Boolean;

    FPingIntervalMS: Cardinal;
    FPongTimeoutMS: Cardinal;

    FOnMessage: TWSOnMessageEvent;
    FOnConnect: TWSOnConnectEvent;
    FOnDisconnect: TWSOnDisconnectEvent;

    procedure InitSocket;
  protected
    procedure Execute; override;
  public
    constructor Create(APort: Word); reintroduce;
    destructor Destroy; override;

    procedure StartServer;
    procedure StopServer;

    property Active: Boolean read FActive;
    property Port: Word read FPort;

    property PingIntervalMS: Cardinal read FPingIntervalMS write FPingIntervalMS;
    property PongTimeoutMS: Cardinal read FPongTimeoutMS write FPongTimeoutMS;

    property OnMessage: TWSOnMessageEvent read FOnMessage write FOnMessage;
    property OnConnect: TWSOnConnectEvent read FOnConnect write FOnConnect;
    property OnDisconnect: TWSOnDisconnectEvent read FOnDisconnect write FOnDisconnect;
  end;

  { TWebSocketConnectionThread }

  TWebSocketConnectionThread = class(TThread)
  private
    FServer: TWebSocketServer;
    FSock: LongInt;
    FLastPong: QWord;
    FLastPing: QWord;
    FConnected: Boolean;

    procedure DoMessage(const AText: string);
    procedure DoConnect;
    procedure DoDisconnect;

    function PerformHandshake: Boolean;
    function ReadHTTPLine: string;

    function ReadFrame(out AOpcode: TWebSocketOpcode; out APayload: RawByteString): Boolean;
    function SendFrame(AOpcode: TWebSocketOpcode; const APayload: RawByteString): Boolean;

    procedure SendPing;
    procedure SendPong(const APayload: RawByteString);
    procedure SendClose;
  protected
    procedure Execute; override;
  public
    constructor Create(AServer: TWebSocketServer; ASock: LongInt); reintroduce;
    destructor Destroy; override;
    procedure SendText(const AText: string);
    property Connected: Boolean read FConnected;
  end;

implementation

const
  WS_GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

{ Utilities }

function GetTickCount64MS: QWord;
begin
  Result := GetTickCount64;
end;

function SHA1DigestToBase64(const D: TSHA1Digest): string;
var
  raw: RawByteString;
begin
  // Build the raw string directly from the digest bytes
  SetString(raw, PChar(@D[0]), SizeOf(D));
  Result := EncodeStringBase64(raw);
end;

{ ---------------- TWebSocketServer ---------------- }

constructor TWebSocketServer.Create(APort: Word);
begin
  inherited Create(True);
  FreeOnTerminate := False;

  FPort := APort;
  FListenSock := -1;
  FActive := False;

  FPingIntervalMS := 25000; // 25s default
  FPongTimeoutMS  := 10000; // 10s default
end;

destructor TWebSocketServer.Destroy;
begin
  StopServer;
  inherited Destroy;
end;

procedure TWebSocketServer.InitSocket;
var
  Addr: TInetSockAddr;
  Opt: LongInt;
begin
  FListenSock := fpSocket(AF_INET, SOCK_STREAM, 0);
  if FListenSock < 0 then
    raise Exception.Create('Failed to create socket');

  Opt := 1;
  fpSetSockOpt(FListenSock, SOL_SOCKET, SO_REUSEADDR, @Opt, SizeOf(Opt));

  Addr := Default(TInetSockAddr);
  Addr.sin_family := AF_INET;
  Addr.sin_port := htons(FPort);
  Addr.sin_addr.s_addr := htonl(INADDR_ANY);

  if fpBind(FListenSock, @Addr, SizeOf(Addr)) <> 0 then
    raise Exception.Create('Failed to bind socket');

  if fpListen(FListenSock, 10) <> 0 then
    raise Exception.Create('Failed to listen on socket');
end;

procedure TWebSocketServer.StartServer;
begin
  if FActive then Exit;
  InitSocket;
  FActive := True;
  Start;
end;

procedure TWebSocketServer.StopServer;
begin
  if not FActive then Exit;
  FActive := False;
  if FListenSock >= 0 then
  begin
    shutdown(FListenSock, SD_BOTH);
    closesocket(FListenSock);
    FListenSock := -1;
  end;
end;

procedure TWebSocketServer.Execute;
var
  ClientSock: LongInt;
begin
  while not Terminated and FActive do
  begin
    ClientSock := fpAccept(FListenSock, nil, nil);
    if ClientSock >= 0 then
    begin
      // thread per connection
      TWebSocketConnectionThread.Create(Self, ClientSock);
    end
    else
    begin
      Sleep(10);
    end;
  end;
end;

{ ---------------- TWebSocketConnectionThread ---------------- }

constructor TWebSocketConnectionThread.Create(AServer: TWebSocketServer; ASock: LongInt);
begin
  inherited Create(True);
  FreeOnTerminate := True;

  FServer := AServer;
  FSock := ASock;
  FConnected := False;
  FLastPong := GetTickCount64MS;
  FLastPing := 0;

  Start;
end;

destructor TWebSocketConnectionThread.Destroy;
begin
  if FSock >= 0 then
  begin
    shutdown(FSock, SD_BOTH);
    closesocket(FSock);
    FSock := -1;
  end;
  inherited Destroy;
end;

procedure TWebSocketConnectionThread.DoMessage(const AText: string);
begin
  if Assigned(FServer.FOnMessage) then
    FServer.FOnMessage(Self, AText);
end;

procedure TWebSocketConnectionThread.DoConnect;
begin
  if Assigned(FServer.FOnConnect) then
    FServer.FOnConnect(Self);
end;

procedure TWebSocketConnectionThread.DoDisconnect;
begin
  if Assigned(FServer.FOnDisconnect) then
    FServer.FOnDisconnect(Self);
end;

function TWebSocketConnectionThread.ReadHTTPLine: string;
var
  ch: Char;
  res: Integer;
begin
  Result := '';
  while True do
  begin
    res := fpRecv(FSock, @ch, 1, 0);
    if res <= 0 then Exit;
    if ch = #10 then Break;
    if ch <> #13 then
      Result := Result + ch;
  end;
end;

function TWebSocketConnectionThread.PerformHandshake: Boolean;
var
  Line, Key, AcceptKey, Response: string;
  Headers: TStringList;
  Sha: TSHA1Digest;

  procedure SendAll(const S: string);
  var
    P: PChar;
    ToSend, Sent: Integer;
  begin
    P := PChar(S);
    ToSend := Length(S);
    while ToSend > 0 do
    begin
      Sent := fpSend(FSock, P, ToSend, 0);
      if Sent <= 0 then Exit;
      Inc(P, Sent);
      Dec(ToSend, Sent);
    end;
  end;

  function HeaderValue(const Name: string): string;
  var
    j, p: Integer;
    LHS, RHS, Line: string;
  begin
    Result := '';
    for j := 0 to Headers.Count - 1 do
    begin
      Line := Trim(Headers[j]);
      p := Pos(':', Line);
      if p > 0 then
      begin
        LHS := Trim(LowerCase(Copy(Line, 1, p - 1)));
        if LHS = LowerCase(Name) then
        begin
          RHS := Trim(Copy(Line, p + 1, MaxInt));
          Exit(RHS);
        end;
      end;
    end;
  end;

begin
  Result := False;
  Log.Info('Handshake: starting');
  Headers := TStringList.Create;
  try
    // Read request line
    Line := ReadHTTPLine;
    Log.Info('Handshake: request line = "' + Line + '"');
    if Line = '' then Exit;

    // Read headers
    repeat
      Line := ReadHTTPLine;
      Log.Info('Handshake: header line = "' + Line + '"');
      if Line = '' then Break;
      Headers.Add(Line);
    until False;
    Log.Info('Handshake: headers read = ' + IntToStr(Headers.Count));

    Log.Info('Handshake: upgrade=' + HeaderValue('upgrade'));
    Log.Info('Handshake: connection=' + HeaderValue('connection'));
    Log.Info('Handshake: key=' + HeaderValue('sec-websocket-key'));

    // Validate Upgrade
    if LowerCase(HeaderValue('upgrade')) <> 'websocket' then Exit;

    // Validate Connection
    if Pos('upgrade', LowerCase(HeaderValue('connection'))) = 0 then Exit;

    // Extract key
    Key := HeaderValue('sec-websocket-key');
    if Key = '' then Exit;

    // Compute accept key
    Sha := SHA1String(Key + WS_GUID);
    AcceptKey := SHA1DigestToBase64(Sha);

    // Build response
    Response :=
      'HTTP/1.1 101 Switching Protocols'#13#10 +
      'Upgrade: websocket'#13#10 +
      'Connection: Upgrade'#13#10 +
      'Sec-WebSocket-Accept: ' + AcceptKey + #13#10#13#10;

    Log.Info('Handshake: accept key = ' + AcceptKey);
    Log.Info('Handshake: sending response...');

    SendAll(Response);
    Log.Info('Handshake: SUCCESS');
    Result := True;
  finally
    Headers.Free;
  end;
end;

function TWebSocketConnectionThread.ReadFrame(out AOpcode: TWebSocketOpcode;
  out APayload: RawByteString): Boolean;
var
  hdr: array[0..1] of Byte;
  res: Integer;
  fin, masked: Boolean;
  opcode: Byte;
  payloadLen: QWord;
  extLen16: Word;
  extLen64: QWord;
  maskKey: array[0..3] of Byte;
  i: QWord;
  b: Byte;
begin
  Result := False;
  APayload := '';

  res := fpRecv(FSock, @hdr[0], 2, 0);
  if res <> 2 then Exit;

  fin := (hdr[0] and $80) <> 0;
  opcode := hdr[0] and $0F;
  masked := (hdr[1] and $80) <> 0;
  payloadLen := hdr[1] and $7F;

  if not fin then
  begin
    // no fragmentation support in this minimal version
    Exit;
  end;

  if payloadLen = 126 then
  begin
    res := fpRecv(FSock, @extLen16, SizeOf(extLen16), 0);
    if res <> SizeOf(extLen16) then Exit;
    payloadLen := ntohs(extLen16);
  end
  else if payloadLen = 127 then
  begin
    res := fpRecv(FSock, @extLen64, SizeOf(extLen64), 0);
    if res <> SizeOf(extLen64) then Exit;
    // NOTE: ignoring >2^31 lengths in this minimal version
    payloadLen := NtoHl(LongInt(extLen64)); // crude, but fine for small payloads
  end;

  if masked then
  begin
    res := fpRecv(FSock, @maskKey[0], 4, 0);
    if res <> 4 then Exit;
  end;

  SetLength(APayload, payloadLen);
  i := 0;
  while i < payloadLen do
  begin
    res := fpRecv(FSock, @b, 1, 0);
    if res <> 1 then Exit;
    if masked then
      b := b xor maskKey[i mod 4];
    APayload[i + 1] := Char(b);
    Inc(i);
  end;

  AOpcode := TWebSocketOpcode(opcode);
  Result := True;
end;

function TWebSocketConnectionThread.SendFrame(AOpcode: TWebSocketOpcode;
  const APayload: RawByteString): Boolean;
var
  hdr: TWSHeader;
  hdrLen: Integer;
  payloadLen: QWord;
  extLen16: Word;
  extLen64: QWord;
begin
  Result := False;
  hdr := Default(TWSHeader);

  payloadLen := Length(APayload);

  hdr[0] := $80 or (Ord(AOpcode) and $0F); // FIN + opcode

  if payloadLen <= 125 then
  begin
    hdr[1] := Byte(payloadLen);
    hdrLen := 2;
  end
  else if payloadLen <= High(Word) then
  begin
    hdr[1] := 126;
    extLen16 := htons(Word(payloadLen));
    Move(extLen16, hdr[2], SizeOf(extLen16));
    hdrLen := 4;
  end
  else
  begin
    hdr[1] := 127;
    extLen64 := payloadLen; // not strictly RFC-correct, but fine for small payloads
    Move(extLen64, hdr[2], SizeOf(extLen64));
    hdrLen := 10;
  end;

  if fpSend(FSock, @hdr[0], hdrLen, 0) <> hdrLen then Exit;
  if payloadLen > 0 then
    if fpSend(FSock, PChar(APayload), payloadLen, 0) <> LongInt(payloadLen) then Exit;

  Result := True;
end;

procedure TWebSocketConnectionThread.SendText(const AText: string);
begin
  SendFrame(wsoText, RawByteString(AText));
end;

procedure TWebSocketConnectionThread.SendPing;
begin
  SendFrame(wsoPing, '');
  FLastPing := GetTickCount64MS;
end;

procedure TWebSocketConnectionThread.SendPong(const APayload: RawByteString);
begin
  SendFrame(wsoPong, APayload);
end;

procedure TWebSocketConnectionThread.SendClose;
begin
  SendFrame(wsoClose, '');
end;

procedure TWebSocketConnectionThread.Execute;
var
  FDSet: TFDSet;
  TimeVal: TTimeVal;
  opcode: TWebSocketOpcode;
  payload: RawByteString;
  nowMS: QWord;
  sel: Integer;
begin
  if not PerformHandshake then
  begin
    Terminate;
    Exit;
  end;

  FConnected := True;
  FLastPong := GetTickCount64MS;
  DoConnect;

  while not Terminated and FConnected do
  begin
    nowMS := GetTickCount64MS;

    // Ping interval
    if (FServer.FPingIntervalMS > 0) and
       (nowMS - FLastPing >= FServer.FPingIntervalMS) then
      SendPing;

    // Pong timeout
    if (FServer.FPongTimeoutMS > 0) and
       (nowMS - FLastPong >= QWord(FServer.FPingIntervalMS + FServer.FPongTimeoutMS)) then
    begin
      FConnected := False;
      Break;
    end;

    // Prepare select()
    FD_ZERO(FDSet);
    FD_SET(FSock, FDSet);

    TimeVal.tv_sec := 0;
    TimeVal.tv_usec := 20000; // 20ms tick

    {$IFDEF WINDOWS}
    sel := Select(FSock + 1, @FDSet, nil, nil, @TimeVal);
    {$ELSE}
    sel := fpSelect(FSock + 1, @FDSet, nil, nil, @TimeVal);
    {$ENDIF}

    if sel > 0 then
    begin
      if not ReadFrame(opcode, payload) then
      begin
        FConnected := False;
        Break;
      end;

      case opcode of
        wsoText:
          DoMessage(String(payload));

        wsoPing:
          begin
            FLastPong := GetTickCount64MS;
            SendPong(payload);
          end;

        wsoPong:
          FLastPong := GetTickCount64MS;

        wsoClose:
          begin
            FConnected := False;
            Break;
          end;
      end;
    end;
  end;

  SendClose;
  DoDisconnect;
  FConnected := False;
end;

end.
