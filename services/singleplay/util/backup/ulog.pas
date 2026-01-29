unit uLog;

{$mode objfpc}{$H+}

interface

uses
  SysUtils;

type
  TLogCallback = procedure(const S: string) of object;

  { TLogger }

  TLogger = class
  private
    FCallback: TLogCallback;
    procedure Dispatch(const Prefix, S: string);
  public
    procedure SetCallback(ACallback: TLogCallback);

    procedure Info(const S: string);
    procedure Warn(const S: string);
    procedure Error(const S: string);
  end;

var
  Log: TLogger;  // global singleton

implementation

uses
  uMain;

{ TLogger }

procedure TLogger.SetCallback(ACallback: TLogCallback);
begin
  FCallback := ACallback;
end;

procedure TLogger.Dispatch(const Prefix, S: string);
begin
  if Assigned(FCallback) then
    FCallback(Prefix + S);
end;

procedure TLogger.Info(const S: string);
begin
  if FormMain.ShuttingDown then
    Exit;
  Dispatch('[INFO] ', S);
end;

procedure TLogger.Warn(const S: string);
begin
  if FormMain.ShuttingDown then
    Exit;
  Dispatch('[WARN] ', S);
end;

procedure TLogger.Error(const S: string);
begin
  if FormMain.ShuttingDown then
    Exit;
  Dispatch('[ERROR] ', S);
end;

initialization
  Log := TLogger.Create;

finalization
  Log.Free;

end.
