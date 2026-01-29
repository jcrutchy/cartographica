unit uGameServer;

{$mode objfpc}{$H+}

interface

uses
  SysUtils, Classes, fpjson,
  uWebSocketServer, uLog, LazUtils;

type
  TGameServer = class
  private
    FServer: TWebSocketServer;

    procedure HandleConnect(AThread: TWebSocketConnectionThread);
    procedure HandleDisconnect(AThread: TWebSocketConnectionThread);
    procedure HandleMessage(AThread: TWebSocketConnectionThread; const Text: string);

    procedure SendJSON(AThread: TWebSocketConnectionThread; const Msg: TJSONObject);

    procedure HandleListWorlds(AThread: TWebSocketConnectionThread);
    procedure HandleLoadWorld(AThread: TWebSocketConnectionThread; Msg: TJSONObject);
    procedure HandleSaveState(AThread: TWebSocketConnectionThread; Msg: TJSONObject);

    function ListWorlds: TJSONArray;
  public
    constructor Create(APort: Integer);
    destructor Destroy; override;

    procedure Start;
    procedure Stop;
  end;

implementation

const
  WORLDS_DIR = './data/worlds/';

{ TGameServer }

constructor TGameServer.Create(APort: Integer);
begin
  inherited Create;

  FServer := TWebSocketServer.Create(APort);
  FServer.OnConnect := @HandleConnect;
  FServer.OnDisconnect := @HandleDisconnect;
  FServer.OnMessage := @HandleMessage;
end;

destructor TGameServer.Destroy;
begin
  Stop;
  FServer.Free;
  inherited Destroy;
end;

procedure TGameServer.Start;
begin
  FServer.StartServer;
end;

procedure TGameServer.Stop;
begin
  FServer.StopServer;
end;

procedure TGameServer.HandleConnect(AThread: TWebSocketConnectionThread);
begin
  Log.Info('Client connected');
end;

procedure TGameServer.HandleDisconnect(AThread: TWebSocketConnectionThread);
begin
  Log.Info('Client disconnected');
end;

procedure TGameServer.HandleMessage(AThread: TWebSocketConnectionThread; const Text: string);
var
  msg: TJSONData;
  obj: TJSONObject;
  msgType: string;
begin
  try
    msg := GetJSON(Text);
    if msg.JSONType <> jtObject then Exit;

    obj := TJSONObject(msg);
    msgType := obj.Get('type', '');

    Log.Info('MESSAGE RECEIVED: ' + msgType);

    case msgType of
      'LIST_WORLDS': HandleListWorlds(AThread);
      'LOAD_WORLD':  HandleLoadWorld(AThread, obj);
      'SAVE_STATE':  HandleSaveState(AThread, obj);
    else
      Log.Warn('Unknown message type: ' + msgType);
    end;

  except
    on E: Exception do
      Log.Error('Error parsing client message: ' + E.Message);
  end;
end;

procedure TGameServer.SendJSON(AThread: TWebSocketConnectionThread; const Msg: TJSONObject);
begin
  AThread.SendText(Msg.AsJSON);
end;

procedure TGameServer.HandleListWorlds(AThread: TWebSocketConnectionThread);
var
  arr: TJSONArray;
  msg: TJSONObject;
begin
  arr := ListWorlds;

  msg := TJSONObject.Create([
    'type', 'WORLD_LIST',
    'worlds', arr
  ]);

  SendJSON(AThread, msg);
end;

function TGameServer.ListWorlds: TJSONArray;
var
  sr: TSearchRec;
  arr: TJSONArray;
begin
  arr := TJSONArray.Create;

  if FindFirst(WORLDS_DIR + '*', faDirectory, sr) = 0 then
  begin
    repeat
      if (sr.Attr and faDirectory <> 0) and (sr.Name <> '.') and (sr.Name <> '..') then
      begin
        arr.Add(TJSONObject.Create([
          'id', sr.Name,
          'name', sr.Name
        ]));
      end;
    until FindNext(sr) <> 0;
    FindClose(sr);
  end;

  Result := arr;
end;

procedure TGameServer.HandleLoadWorld(AThread: TWebSocketConnectionThread; Msg: TJSONObject);
var
  worldId: string;
  path: string;
  json: TJSONData;
  msgOut: TJSONObject;
begin
  worldId := Msg.Get('world_id', '');
  path := WORLDS_DIR + worldId + '/world.json';

  if not FileExists(path) then
  begin
    Log.Error('World not found: ' + worldId);
    Exit;
  end;

  json := GetJSON(ReadFileToString(path));

  msgOut := TJSONObject.Create([
    'type', 'WORLD_DATA',
    'world', json
  ]);

  SendJSON(AThread, msgOut);
end;

procedure TGameServer.HandleSaveState(AThread: TWebSocketConnectionThread; Msg: TJSONObject);
var
  worldId: string;
  changes: TJSONArray;
  ack: TJSONObject;
begin
  worldId := Msg.Get('world_id', '');
  changes := Msg.Arrays['changes'];

  // TODO: queue async save

  ack := TJSONObject.Create([
    'type', 'SAVE_ACK',
    'world_id', worldId
  ]);

  SendJSON(AThread, ack);
end;

end.
