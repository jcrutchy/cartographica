unit uBrainRegistry;

{$mode objfpc}{$H+}

interface

uses
  SysUtils, fileutil, fpjson, jsonparser, uBrainTypes;

type

  TFeatureDef = record
    Name: string;
    MinVal: Double;
    MaxVal: Double;
  end;

  TActionDef = record
    Name: string;
  end;

  TTraitDef = record
    Name: string;
  end;

  TRoleDef = record
    Id: Integer;
    Name: string;
  end;

var
  FeatureList: array of TFeatureDef;
  ActionList: array of TActionDef;
  TraitList: array of TTraitDef;
  RoleList: array of TRoleDef;
  BaseBrains: array of TBrainBase;


procedure LoadRegistries;
function BuildStateVector(const JSONState: TJSONArray): TVector;
function BuildStateVectorFromObject(const Obj: TJSONObject): TVector;
function GetRoleName(RoleId: Integer): string;
function DefaultTraits: TVector;
function GetBaseBrainForRole(RoleId: Integer): PBrainBase;
function GetRoleIndex(RoleId: Integer): Integer;
procedure InitBaseBrains;

implementation

uses
  uCortexMain;

procedure LoadFeatures;
var
  JSON: TJSONData;
  Arr: TJSONArray;
  i: Integer;
begin
  JSON := GetJSON(ReadFileToString('registries/features.json'));
  Arr := TJSONObject(JSON).Arrays['features'];
  SetLength(FeatureList, Arr.Count);
  for i := 0 to Arr.Count - 1 do
  begin
    FeatureList[i].Name := Arr.Objects[i].Get('name', '');
    FeatureList[i].MinVal := Arr.Objects[i].Get('min', 0.0);
    FeatureList[i].MaxVal := Arr.Objects[i].Get('max', 1.0);
  end;
  JSON.Free;
end;

procedure LoadActions;
var
  JSON: TJSONData;
  Arr: TJSONArray;
  i: Integer;
begin
  JSON := GetJSON(ReadFileToString('registries/actions.json'));
  Arr := TJSONObject(JSON).Arrays['actions'];
  SetLength(ActionList, Arr.Count);
  for i := 0 to Arr.Count - 1 do
    ActionList[i].Name := Arr.Objects[i].Get('name', '');
  JSON.Free;
end;

procedure LoadRoles;
var
  JSON: TJSONData;
  Arr: TJSONArray;
  i: Integer;
begin
  JSON := GetJSON(ReadFileToString('registries/roles.json'));
  Arr := TJSONObject(JSON).Arrays['roles'];

  SetLength(RoleList, Arr.Count);
  for i := 0 to Arr.Count - 1 do
  begin
    RoleList[i].Id := Arr.Objects[i].Get('id', 0);
    RoleList[i].Name := Arr.Objects[i].Get('name', '');
  end;

  JSON.Free;
end;

procedure LoadTraits;
var
  JSON: TJSONData;
  Arr: TJSONArray;
  i: Integer;
begin
  JSON := GetJSON(ReadFileToString('registries/traits.json'));
  Arr := TJSONObject(JSON).Arrays['traits'];

  SetLength(TraitList, Arr.Count);
  for i := 0 to Arr.Count - 1 do
    TraitList[i].Name := Arr.Objects[i].Get('name', '');

  JSON.Free;
end;

function DefaultTraits: TVector;
var
  i: Integer;
begin
  SetLength(Result, Length(TraitList));
  for i := 0 to High(Result) do
    Result[i] := 0.0; // neutral baseline
end;

function GetRoleName(RoleId: Integer): string;
var
  i: Integer;
begin
  for i := 0 to High(RoleList) do
    if RoleList[i].Id = RoleId then
      Exit(RoleList[i].Name);
  Result := 'unknown';
end;

function BuildStateVector(const JSONState: TJSONArray): TVector;
var
  i: Integer;
begin
  SetLength(Result, JSONState.Count);
  for i := 0 to JSONState.Count - 1 do
    Result[i] := JSONState.Items[i].AsFloat;
end;

function BuildStateVectorFromObject(const Obj: TJSONObject): TVector;
var
  i: Integer;
  fname: string;
begin
  SetLength(Result, Length(FeatureList));
  for i := 0 to High(FeatureList) do
  begin
    fname := FeatureList[i].Name;
    if Obj.Find(fname) <> nil then
      Result[i] := Obj.Get(fname, 0.0)
    else
      Result[i] := 0.0; // default
  end;
end;

function GetBaseBrainForRole(RoleId: Integer): PBrainBase;
var
  i: Integer;
begin
  for i := 0 to High(RoleList) do
    if RoleList[i].Id = RoleId then
      Exit(@BaseBrains[i]);
  Result := nil;
end;

function GetRoleIndex(RoleId: Integer): Integer;
var
  i: Integer;
begin
  for i := 0 to High(RoleList) do
    if RoleList[i].Id = RoleId then
      Exit(i);
  Result := -1;
end;

procedure InitBaseBrains;
var
  i: Integer;
begin
  SetLength(BaseBrains, Length(RoleList));
  for i := 0 to High(RoleList) do
  begin
    InitBrainBase(
      BaseBrains[i],
      Length(FeatureList),
      32, // hidden layer size for now
      Length(ActionList)
    );
  end;
end;

procedure LoadRegistries;
begin
  LoadFeatures;
  LoadActions;
  LoadRoles;
  LoadTraits;
end;

end.
