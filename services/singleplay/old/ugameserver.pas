unit uGameServer;

{$mode objfpc}{$H+}

interface

uses
  SysUtils, Classes, fpjson,
  uWorld, uIsland, uTileMap,
  uWebSocketServer, uConfig, uLog;

type
  { TGameServer }

  TGameServer = class
  private
    FServer: TWebSocketServer;

    procedure HandleConnect(AThread: TWebSocketConnectionThread);
    procedure HandleDisconnect(AThread: TWebSocketConnectionThread);
    procedure HandleMessage(AThread: TWebSocketConnectionThread; const Text: string);

    procedure SendTypedJSON(AThread: TWebSocketConnectionThread; const MsgType: string; Data: TJSONObject);

    procedure SendMenu(AThread: TWebSocketConnectionThread);
    procedure SendNewWorldOptions(AThread: TWebSocketConnectionThread);
    procedure SendWorldList(AThread: TWebSocketConnectionThread);
    procedure SendOptions(AThread: TWebSocketConnectionThread);

    procedure HandleCreateWorld(AThread: TWebSocketConnectionThread; Msg: TJSONObject);
    procedure HandleLoadWorld(AThread: TWebSocketConnectionThread; Msg: TJSONObject);
  public
    constructor Create(APort: Integer);
    destructor Destroy; override;
    function ListWorlds: TJSONArray;
    procedure Start;
    procedure Stop;
  end;

implementation

{ TGameServer }

constructor TGameServer.Create(APort: Integer);
begin
  inherited Create;

  // Create WebSocket server
  FServer := TWebSocketServer.Create(APort);

  // Hook events
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
  // For now, just send the world snapshot
  Log.Info('Client connected');
end;

procedure TGameServer.HandleDisconnect(AThread: TWebSocketConnectionThread);
begin
  // Later: remove player, cleanup, etc.
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
    if msgType = 'GET_MENU' then
      SendMenu(AThread)
    else if msgType = 'GET_NEW_WORLD_OPTIONS' then
      SendNewWorldOptions(AThread)
    else if msgType = 'GET_WORLD_LIST' then
      SendWorldList(AThread)
    else if msgType = 'GET_OPTIONS' then
      SendOptions(AThread)
    else if msgType = 'CREATE_WORLD' then
      HandleCreateWorld(AThread, obj)
    else if msgType = 'LOAD_WORLD' then
      HandleLoadWorld(AThread, obj);

  except
    on E: Exception do
      Log.Error('Error parsing client message: ' + E.Message);
  end;
end;

procedure TGameServer.SendTypedJSON(AThread: TWebSocketConnectionThread; const MsgType: string; Data: TJSONObject);
var
  wrapper: TJSONObject;
begin
  wrapper := TJSONObject.Create;
  wrapper.Add('type', MsgType);
  wrapper.Add('data', Data);

  AThread.SendText(wrapper.AsJSON);
  wrapper.Free;
end;

procedure TGameServer.SendMenu(AThread: TWebSocketConnectionThread);
var
  obj: TJSONObject;
  arr: TJSONArray;
begin
  obj := TJSONObject.Create;

  arr := TJSONArray.Create;
  arr.Add(TJSONObject.Create(['id', 'new_world', 'label', 'New World']));
  arr.Add(TJSONObject.Create(['id', 'load_world', 'label', 'Load World']));
  arr.Add(TJSONObject.Create(['id', 'options', 'label', 'Options']));
  obj.Add('main_menu', arr);

  SendTypedJSON(AThread, 'MENU', obj);
end;

procedure TGameServer.SendNewWorldOptions(AThread: TWebSocketConnectionThread);
var
  obj: TJSONObject;
begin
  obj := TJSONObject.Create;

  obj.Add('graph_types', Config.WorldGen.Arrays['graph_types'].Clone);
  obj.Add('island_sizes', Config.WorldGen.Arrays['island_sizes'].Clone);
  obj.Add('player_modes', Config.WorldGen.Arrays['player_modes'].Clone);

  SendTypedJSON(AThread, 'NEW_WORLD_OPTIONS', obj);
end;

procedure TGameServer.SendWorldList(AThread: TWebSocketConnectionThread);
var
  arr: TJSONArray;
begin
  arr := ListWorlds; // we’ll implement this below
  SendTypedJSON(AThread, 'WORLD_LIST', TJSONObject.Create(['worlds', arr]));
end;

function TGameServer.ListWorlds: TJSONArray;
var
  sr: TSearchRec;
  arr: TJSONArray;
  worldId: string;
begin
  arr := TJSONArray.Create;

  if FindFirst(Config.WorldsPath + '*', faDirectory, sr) = 0 then
  begin
    repeat
      if (sr.Attr and faDirectory <> 0) and (sr.Name <> '.') and (sr.Name <> '..') then
      begin
        worldId := sr.Name;
        arr.Add(TJSONObject.Create([
          'id', worldId,
          'name', worldId  // later: load metadata.json
        ]));
      end;
    until FindNext(sr) <> 0;
    FindClose(sr);
  end;

  Result := arr;
end;

procedure TGameServer.SendOptions(AThread: TWebSocketConnectionThread);
var
  obj: TJSONObject;
begin
  obj := TJSONObject.Create;

  obj.Add('audio', TJSONArray.Create(['on', 'off']));
  obj.Add('graphics', TJSONArray.Create(['low', 'medium', 'high']));
  obj.Add('ui_scale', TJSONArray.Create([1.0, 1.25, 1.5]));

  SendTypedJSON(AThread, 'OPTIONS', obj);
end;

procedure TGameServer.HandleCreateWorld(AThread: TWebSocketConnectionThread; Msg: TJSONObject);
var
  worldId: string;
  world: TWorld;
  graph: string;
begin
  graph := Msg.Get('graph', 'single');

  // Generate a new unique world ID
  worldId := FormatDateTime('yyyymmdd_hhnnss', Now);

  // Create world directory
  ForceDirectories(Config.WorldPath(worldId));

  // Generate the world
  world := TWorld.Create;
  try
    world.Generate(graph);  // your worldgen logic
    world.SaveToFile(Config.WorldPath(worldId) + 'world.json');
  finally
    world.Free;
  end;

  // Tell client world is ready
  SendTypedJSON(AThread, 'WORLD_CREATED',
    TJSONObject.Create(['world_id', worldId]));
end;

procedure TGameServer.HandleLoadWorld(AThread: TWebSocketConnectionThread; Msg: TJSONObject);
var
  id: string;
  world: TWorld;
  json: TJSONObject;
begin
  id := Msg.Get('world_id', '');

  if id = '' then Exit;

  world := TWorld.Create;
  try
    world.LoadFromFile(Config.WorldPath(id) + 'world.json');
    json := world.ToJSON;
    SendTypedJSON(AThread, 'WORLD_DATA', json);
  finally
    world.Free;
  end;
end;

end.
