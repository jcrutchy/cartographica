unit uTileMap;

{$mode objfpc}{$H+}

interface

uses
  SysUtils, Classes, fpjson;

type
  { TTileMap }

  TTileMap = class
  private
    FWidth: Integer;
    FHeight: Integer;
    FTiles: array of array of Integer; // [Y][X]
  public
    constructor Create;

    procedure SetSize(AWidth, AHeight: Integer);
    function GetTile(X, Y: Integer): Integer;
    procedure SetTile(X, Y: Integer; AValue: Integer);

    procedure Generate;

    function ToJSON: TJSONArray;
    procedure FromJSON(AObj: TJSONObject);

    property Width: Integer read FWidth;
    property Height: Integer read FHeight;
  end;

implementation

{ TTileMap }

constructor TTileMap.Create;
begin
  inherited Create;
end;

procedure TTileMap.SetSize(AWidth, AHeight: Integer);
var
  y: Integer;
begin
  FWidth := AWidth;
  FHeight := AHeight;
  SetLength(FTiles, FHeight);
  for y := 0 to FHeight - 1 do
    SetLength(FTiles[y], FWidth);
end;

function TTileMap.GetTile(X, Y: Integer): Integer;
begin
  if (Y < 0) or (Y >= FHeight) or (X < 0) or (X >= FWidth) then
    Exit(0);
  Result := FTiles[Y][X];
end;

procedure TTileMap.SetTile(X, Y: Integer; AValue: Integer);
begin
  if (Y < 0) or (Y >= FHeight) or (X < 0) or (X >= FWidth) then
    Exit;
  FTiles[Y][X] := AValue;
end;

procedure TTileMap.Generate;
var
  x, y: Integer;
begin
  SetSize(20, 20); // figure out something better later
  for y := 0 to Height - 1 do
    for x := 0 to Width - 1 do
      SetTile(x, y, 1); // 1 = grass, or whatever your tileset uses
end;

function TTileMap.ToJSON: TJSONArray;
var
  y, x: Integer;
  row: TJSONArray;
begin
  Result := TJSONArray.Create;
  for y := 0 to FHeight - 1 do
  begin
    row := TJSONArray.Create;
    for x := 0 to FWidth - 1 do
      row.Add(FTiles[y][x]);
    Result.Add(row);
  end;
end;

procedure TTileMap.FromJSON(AObj: TJSONObject);
var
  arr: TJSONArray;
  x, y, idx: Integer;
begin
  x := AObj.Get('width', 0);
  y := AObj.Get('height', 0);
  SetSize(x, y);
  arr := AObj.Arrays['tiles'];
  idx := 0;
  for y := 0 to Height - 1 do
    for x := 0 to Width - 1 do
    begin
      FTiles[y][x] := arr.Integers[idx];
      Inc(idx);
    end;
end;

end.
