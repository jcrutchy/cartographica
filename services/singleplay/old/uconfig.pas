unit uConfig;

{$mode objfpc}{$H+}

interface

uses
  SysUtils, Classes, fpjson, jsonparser;

type
  { TConfig }

  TConfig = class
  private
    FBasePath: string;
    FConfigPath: string;
    FWorldsPath: string;

    FServerConfig: TJSONObject;
    FDefaults: TJSONObject;
    FWorldGen: TJSONObject;

    function LoadJSONFile(const FileName: string): TJSONObject;
    procedure EnsureDirectory(const Path: string);
  public
    constructor Create;
    destructor Destroy; override;

    // Accessors
    function Server: TJSONObject;
    function Defaults: TJSONObject;
    function WorldGen: TJSONObject;

    // Paths
    function BasePath: string;
    function ConfigPath: string;
    function WorldsPath: string;
    function WorldPath(const ID: string): string;
  end;

var
  Config: TConfig;

implementation

uses
  uLog;

{ TConfig }

constructor TConfig.Create;
begin
  inherited Create;

  // Base directory for all data
  FBasePath := ExpandFileName('data');
  FConfigPath := IncludeTrailingPathDelimiter(FBasePath + DirectorySeparator + 'config');
  FWorldsPath := IncludeTrailingPathDelimiter(FBasePath + DirectorySeparator + 'worlds');

  // Ensure directory structure exists
  EnsureDirectory(FBasePath);
  EnsureDirectory(FConfigPath);
  EnsureDirectory(FWorldsPath);

  // Load config files
  FServerConfig := LoadJSONFile(FConfigPath + 'server.json');
  FDefaults := LoadJSONFile(FConfigPath + 'defaults.json');
  FWorldGen := LoadJSONFile(FConfigPath + 'worldgen.json');

  Log.Info('Configuration loaded from ' + FConfigPath);
end;

destructor TConfig.Destroy;
begin
  FServerConfig.Free;
  FDefaults.Free;
  FWorldGen.Free;
  inherited Destroy;
end;

procedure TConfig.EnsureDirectory(const Path: string);
begin
  if not DirectoryExists(Path) then
  begin
    if not ForceDirectories(Path) then
      raise Exception.Create('Failed to create directory: ' + Path);
  end;
end;

function TConfig.LoadJSONFile(const FileName: string): TJSONObject;
var
  JSON: TJSONData;
  Stream: TFileStream;
begin
  Result := TJSONObject.Create;

  if not FileExists(FileName) then
  begin
    Log.Warn('Config file missing: ' + FileName + ' (using empty object)');
    Exit;
  end;

  try
    Stream := TFileStream.Create(FileName, fmOpenRead);
    try
      JSON := GetJSON(Stream);
      if JSON.JSONType = jtObject then
        Result := TJSONObject(JSON.Clone)
      else
        Log.Warn('Config file is not a JSON object: ' + FileName);
    finally
      Stream.Free;
    end;
  except
    on E: Exception do
    begin
      Log.Error('Failed to load config file: ' + FileName + ' (' + E.Message + ')');
    end;
  end;
end;

function TConfig.Server: TJSONObject;
begin
  Result := FServerConfig;
end;

function TConfig.Defaults: TJSONObject;
begin
  Result := FDefaults;
end;

function TConfig.WorldGen: TJSONObject;
begin
  Result := FWorldGen;
end;

function TConfig.BasePath: string;
begin
  Result := FBasePath;
end;

function TConfig.ConfigPath: string;
begin
  Result := FConfigPath;
end;

function TConfig.WorldsPath: string;
begin
  Result := FWorldsPath;
end;

function TConfig.WorldPath(const ID: string): string;
begin
  Result := IncludeTrailingPathDelimiter(FWorldsPath + ID);
end;

initialization
  Config := TConfig.Create;

finalization
  Config.Free;

end.
