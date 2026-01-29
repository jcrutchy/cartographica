unit uProtocol;

{$mode ObjFPC}{$H+}

interface

uses
  Classes, SysUtils, fpjson, uConfig, uGameServer;

function BuildMenuJSON: TJSONObject;

implementation

uses
  uMain;

function BuildMenuJSON: TJSONObject;
var
  obj, nw, lw, opt: TJSONObject;
  arr: TJSONArray;
begin
  obj := TJSONObject.Create;

  // Main menu
  arr := TJSONArray.Create;
  arr.Add(TJSONObject.Create(['id', 'new_world', 'label', 'New World']));
  arr.Add(TJSONObject.Create(['id', 'load_world', 'label', 'Load World']));
  arr.Add(TJSONObject.Create(['id', 'options', 'label', 'Options']));
  obj.Add('main_menu', arr);

  // New world options
  nw := TJSONObject.Create;
  nw.Add('graph_types', Config.WorldGen.Arrays['graph_types'].Clone);
  nw.Add('island_sizes', Config.WorldGen.Arrays['island_sizes'].Clone);
  nw.Add('player_modes', Config.WorldGen.Arrays['player_modes'].Clone);
  obj.Add('new_world', nw);

  // Load world list
  lw := TJSONObject.Create;
  lw.Add('worlds', FormMain.GameServer.ListWorlds);
  obj.Add('load_world', lw);

  // Options (static for now)
  opt := TJSONObject.Create;
  opt.Add('audio', TJSONArray.Create(['on', 'off']));
  opt.Add('graphics', TJSONArray.Create(['low', 'medium', 'high']));
  opt.Add('ui_scale', TJSONArray.Create([1.0, 1.25, 1.5]));
  obj.Add('options', opt);

  Result := obj;
end;

end.

