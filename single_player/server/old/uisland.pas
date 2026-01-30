unit uIsland;

{$mode objfpc}{$H+}

interface

uses
  SysUtils, Classes, fpjson, uTileMap;

type
  { TIsland }

  TIsland = class
  private
    FID: string;
    FOriginX: Integer;
    FOriginY: Integer;
    FDefaultTileset: string;
    FTileMap: TTileMap;
  public
    constructor Create(const AID: string);
    destructor Destroy; override;

    procedure Generate;

    function ToJSON: TJSONObject;
    procedure FromJSON(AObj: TJSONObject);

    property ID: string read FID;
    property DefaultTileset: string read FDefaultTileset write FDefaultTileset;
    property TileMap: TTileMap read FTileMap;
  end;

implementation

{ TIsland }

constructor TIsland.Create(const AID: string);
begin
  inherited Create;
  FID := AID;
  FDefaultTileset := '';
  FTileMap := TTileMap.Create;
end;

destructor TIsland.Destroy;
begin
  FTileMap.Free;
  inherited Destroy;
end;

procedure TIsland.Generate;
begin
  FTileMap.Generate;
end;

function TIsland.ToJSON: TJSONObject;
begin
  Result := TJSONObject.Create;
  Result.Add('island_id', FID);
  Result.Add('default_tileset', FDefaultTileset);
  Result.Add('tilemap', FTileMap.ToJSON);
end;

procedure TIsland.FromJSON(AObj: TJSONObject);
var
  tileObj: TJSONObject;
begin
  FID := AObj.Get('id', '');
  FOriginX := AObj.Get('originX', 0);
  FOriginY := AObj.Get('originY', 0);
  FDefaultTileset := AObj.Get('default_tileset', '');
  tileObj := AObj.Objects['tilemap'];
  if tileObj <> nil then
  begin
    FTileMap.FromJSON(AObj.Objects['tilemap']);
  end;
end;

end.
