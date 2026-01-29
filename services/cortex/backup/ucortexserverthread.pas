unit uCortexServerThread;

{$mode objfpc}{$H+}

interface

uses
  WinSock2, Classes, SysUtils, Sockets, fpjson, jsonparser,
  uBrainTypes, uBrainRegistry, uNpcRegistry, uBrainForward, uBrainDelta;

type
  TCortexServerThread = class(TThread)
  private
    FLogBuffer: TStringList;
    FCritical: TRTLCriticalSection;
  protected
    procedure Execute; override;
  public
    constructor Create;
    destructor Destroy; override;

    procedure Log(const S: string);
    procedure FlushLog;

    // now returns Boolean so we can loop on a persistent connection
    function HandleClient(Sock: LongInt): Boolean;
  end;

implementation

uses
  uCortexMain;

{ -------------------------------------------------------------------
  Handle a single request on an already-open socket.
  Returns TRUE if the socket should remain open.
  Returns FALSE if the client disconnected or an error occurred.
  ------------------------------------------------------------------- }
function TCortexServerThread.HandleClient(Sock: LongInt): Boolean;
var
  Buffer: array[0..2047] of Char;
  ReadBytes: LongInt;
  Line: string;
  JSON: TJSONData;
  Obj, StateObj, TraitObj: TJSONObject;
  StateArr: TVector;
  ActionIdx: Integer;
  RespObj: TJSONObject;
  RespStr: string;
  i: Integer;
  npc: PNpcRecord;
  baseBrain: PBrainBase;
  npcId, roleId: Integer;
begin
  Result := False;

  ReadBytes := fpRecv(Sock, @Buffer, SizeOf(Buffer), 0);

  if ReadBytes = 0 then
  begin
      // client closed connection
      Result := False;
      Exit;
  end;

  if (ReadBytes < 0) and (WSAGetLastError = WSAEWOULDBLOCK) then
  begin
      // no data yet, keep connection alive
      Result := True;
      Exit;
  end;

  if ReadBytes < 0 then
  begin
      // real error
      Result := False;
      Exit;
  end;

  // otherwise: we got data

  SetString(Line, Buffer, ReadBytes);

  Log('--- Cortex Request ---');
  Log('Raw JSON: ' + LineEnding + Line);

  JSON := GetJSON(Line);
  Obj := TJSONObject(JSON);

  npcId := Obj.Get('npc_id', 0);
  roleId := Obj.Get('role_id', 1);

  npc := GetOrCreateNpc(npcId, roleId);

  if Obj.Find('traits') <> nil then
  begin
    TraitObj := TJSONObject(Obj.Find('traits'));
    for i := 0 to High(TraitList) do
      npc^.Traits[i] := TraitObj.Get(TraitList[i].Name, 0.0);
  end;

  if Obj.Find('reward') = nil then
    npc^.Reward := 0.1
  else
    npc^.Reward := Obj.Get('reward', 0.0);

  baseBrain := GetBaseBrainForRole(npc^.RoleId);
  if baseBrain = nil then
  begin
    Log('ERROR: No base brain for role ' + LineEnding + IntToStr(npc^.RoleId));
    JSON.Free;
    Exit;
  end;

  StateObj := Obj.Objects['state'];
  if StateObj = nil then
  begin
    Log('ERROR: Missing state object');
    JSON.Free;
    Exit;
  end;

  StateArr := BuildStateVectorFromObject(StateObj);

  ActionIdx := DecideAction(baseBrain^, npc^.BrainDelta, StateArr, npc^.Traits);

  if npc^.Reward <> 0.0 then
  begin
    ApplyRewardToDelta(npc^.BrainDelta, npc^.Reward);
    npc^.Reward := 0.0;
  end;

  RespObj := TJSONObject.Create;
  RespObj.Add('npc_id', npcId);
  RespObj.Add('action', ActionIdx);

  Log('NPC ID: ' + LineEnding + IntToStr(npc^.NpcId));
  Log('Role: ' + LineEnding + GetRoleName(npc^.RoleId));
  Log('Traits:');
  for i := 0 to High(TraitList) do
    Log('  ' + LineEnding + TraitList[i].Name + ': ' + FormatFloat('0.00', npc^.Traits[i]));
  Log('Delta bias sample: ' + LineEnding + FormatFloat('0.0000',
      npc^.BrainDelta.GameplayHead.Layers[0].Biases[0]));
  Log('Chosen action index: ' + LineEnding + IntToStr(ActionIdx));
  Log('Action name: ' + LineEnding + ActionList[ActionIdx].Name);
  Log('-----------------------');

  RespStr := RespObj.AsJSON + LineEnding;
  fpSend(Sock, @RespStr[1], Length(RespStr), 0);

  RespObj.Free;
  JSON.Free;

  Result := True; // keep socket alive
end;

{ -------------------------------------------------------------------
  Logging
  ------------------------------------------------------------------- }
constructor TCortexServerThread.Create;
begin
  inherited Create(False);
  FreeOnTerminate := False;
  FLogBuffer := TStringList.Create;
  InitCriticalSection(FCritical);
end;

destructor TCortexServerThread.Destroy;
begin
  DoneCriticalSection(FCritical);
  FLogBuffer.Free;
  inherited Destroy;
end;

procedure TCortexServerThread.Log(const S: string);
begin
  EnterCriticalSection(FCritical);
  try
    FLogBuffer.Add(S);
  finally
    LeaveCriticalSection(FCritical);
  end;

  TThread.Queue(nil, @FlushLog);
end;

procedure TCortexServerThread.FlushLog;
var
  Temp: TStringList;
begin
  Temp := TStringList.Create;
  try
    EnterCriticalSection(FCritical);
    try
      Temp.Assign(FLogBuffer);
      FLogBuffer.Clear;
    finally
      LeaveCriticalSection(FCritical);
    end;

    MainForm.CortexMemo.Lines.AddStrings(Temp);
  finally
    Temp.Free;
  end;
end;

{ -------------------------------------------------------------------
  Main server loop — now supports persistent client connections
  ------------------------------------------------------------------- }
procedure TCortexServerThread.Execute;
var
  ListenSock, ClientSock: TSocket;
  Addr: TInetSockAddr;
  NonBlocking: u_long;
begin
  ListenSock := fpSocket(AF_INET, SOCK_STREAM, 0);
  NonBlocking := 1;
  ioctlsocket(ListenSock, LongInt(FIONBIO), NonBlocking);
  Addr := Default(TInetSockAddr);
  Addr.sin_family := AF_INET;
  Addr.sin_port := htons(5555);
  Addr.sin_addr.s_addr := htonl($7F000001); // 127.0.0.1
  fpBind(ListenSock, @Addr, SizeOf(Addr));
  fpListen(ListenSock, 5);
  while not Terminated do
  begin
    ClientSock := fpAccept(ListenSock, nil, nil);

    if ClientSock >= 0 then
    begin
      NonBlocking := 1;
      ioctlsocket(ClientSock, LongInt(FIONBIO), NonBlocking);

      while not Terminated do
      begin
        if not HandleClient(ClientSock) then
          break;

        Sleep(0); // yield
      end;

      shutdown(ClientSock, SD_BOTH);
      closesocket(ClientSock);
    end
    else
    begin
      if WSAGetLastError <> WSAEWOULDBLOCK then
        Sleep(1);

      Sleep(1);
    end;
  end;
end;

end.
