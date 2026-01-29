unit uPlayer;

{$mode objfpc}{$H+}

interface

uses
  SysUtils, fpjson, jsonparser;

type
  { TPlayer }

  TPlayer = class
  private
    FID: string;
    FX: Integer;
    FY: Integer;
    FName: string;
  public
    constructor Create(const AID: string);

    property ID: string read FID write FID;
    property X: Integer read FX write FX;
    property Y: Integer read FY write FY;
    property Name: string read FName write FName;

    function ToJSON: TJSONObject;
    procedure FromJSON(AObj: TJSONObject);
  end;

implementation

{ TPlayer }

constructor TPlayer.Create(const AID: string);
begin
  inherited Create;
  FID := AID;
  FX := 0;
  FY := 0;
  FName := '';
end;

function TPlayer.ToJSON: TJSONObject;
begin
  Result := TJSONObject.Create;
  Result.Add('id', FID);
  Result.Add('x', FX);
  Result.Add('y', FY);
  Result.Add('name', FName);
end;

procedure TPlayer.FromJSON(AObj: TJSONObject);
begin
  if AObj = nil then Exit;

  FID := AObj.Get('id', '');
  FX := AObj.Get('x', 0);
  FY := AObj.Get('y', 0);
  FName := AObj.Get('name', '');
end;

end.
