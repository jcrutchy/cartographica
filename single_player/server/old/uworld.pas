unit uWorld;

{$mode objfpc}{$H+}

interface

uses
  SysUtils, Classes, fpjson, fgl, uIsland, uTileMap, uPlayer;

type

  TIslandList = specialize TFPGObjectList<TIsland>;
  TPlayerList = specialize TFPGObjectList<TPlayer>;

  { TWorld }

  TWorld = class
  private
    FIslands: TIslandList;
    FPlayers: TPlayerList;
  public
    constructor Create;
    destructor Destroy; override;

    function AddIsland(AIsland: TIsland): Integer;
    function GetIslandByID(AID: string): TIsland;

    procedure SaveToFile(const FileName: string);
    procedure LoadFromFile(const FileName: string);

    procedure Generate(const GraphType: string);

    function ToJSON: TJSONObject;
    procedure FromJSON(AObj: TJSONObject);

    property Islands: TIslandList read FIslands;
    property Players: TPlayerList read FPlayers;
  end;

implementation

{ TWorld }

constructor TWorld.Create;
begin
  inherited Create;
  FIslands := TIslandList.Create(True);
  FPlayers := TPlayerList.Create(True);
end;

destructor TWorld.Destroy;
begin
  FIslands.Free;
  FPlayers.Free;
  inherited Destroy;
end;

function TWorld.AddIsland(AIsland: TIsland): Integer;
begin
  Result := FIslands.Add(AIsland);
end;

procedure TWorld.SaveToFile(const FileName: string);
var
  json: TJSONObject;
  stream: TFileStream;
  s: string;
begin
  json := Self.ToJSON;
  try
    s := json.AsJSON;

    stream := TFileStream.Create(FileName, fmCreate);
    try
      stream.WriteBuffer(Pointer(s)^, Length(s));
    finally
      stream.Free;
    end;

  finally
    json.Free;
  end;
end;

procedure TWorld.LoadFromFile(const FileName: string);
var
  json: TJSONData;
  obj: TJSONObject;
  stream: TFileStream;
begin
  stream := TFileStream.Create(FileName, fmOpenRead);
  try
    json := GetJSON(stream);
  finally
    stream.Free;
  end;

  if json.JSONType <> jtObject then
    raise Exception.Create('Invalid world file');

  obj := TJSONObject(json);

  // Now reconstruct the world from JSON
  // (minimal version for now)
  Self.FromJSON(obj);

  json.Free;
end;

function TWorld.GetIslandByID(AID: string): TIsland;
var
  i: Integer;
begin
  for i := 0 to FIslands.Count - 1 do
    if FIslands[i].ID = AID then
      Exit(FIslands[i]);
  Result := nil;
end;

function TWorld.ToJSON: TJSONObject;
var
  arr: TJSONArray;
  i: Integer;
begin
  Result := TJSONObject.Create;
  arr := TJSONArray.Create;
  Result.Add('islands', arr);

  for i := 0 to FIslands.Count - 1 do
    arr.Add(FIslands[i].ToJSON);
end;

procedure TWorld.FromJSON(AObj: TJSONObject);
var
  arrIslands: TJSONArray;
  arrPlayers: TJSONArray;
  i: Integer;
  islandObj: TJSONObject;
  playerObj: TJSONObject;
  island: TIsland;
  player: TPlayer;
begin
  // Clear existing data
  Self.Islands.Clear;
  Self.Players.Clear;

  // --- Load islands ---
  if AObj.Find('islands') <> nil then
  begin
    arrIslands := AObj.Arrays['islands'];

    for i := 0 to arrIslands.Count - 1 do
    begin
      islandObj := arrIslands.Objects[i];

      island := TIsland.Create(islandObj.Get('id', ''));
      island.FromJSON(islandObj);

      Self.Islands.Add(island);
    end;
  end;

  // --- Load players ---
  if AObj.Find('players') <> nil then
  begin
    arrPlayers := AObj.Arrays['players'];

    for i := 0 to arrPlayers.Count - 1 do
    begin
      playerObj := arrPlayers.Objects[i];

      player := TPlayer.Create(playerObj.Get('id', ''));
      player.FromJSON(playerObj);

      Self.Players.Add(player);
    end;
  end;
end;

procedure TWorld.Generate(const GraphType: string);
var
  island: TIsland;
begin
  // For now, ignore GraphType and generate a single island

  // Create island
  island := TIsland.Create('island_001');

  island.Generate;

  // Add island to world
  AddIsland(island);

  // Players list starts empty
  // (client will spawn player later)
end;

end.
