unit uCortexRuntime;

{$mode objfpc}{$H+}

interface

uses
  Forms, Classes, SysUtils, Process, Dialogs;

function StartBridge: Boolean;
procedure StopBridge;

var
  BridgeProc: TProcess = nil;

implementation

uses
  uCortexMain;

const
  BUF_SIZE = 2048;

function StartBridge: Boolean;
var
  BridgeDir: string;
  BytesRead: longint;
  Buffer: array[1..BUF_SIZE] of byte;
  OutputStream: TStringStream;
begin
  Result := False;
  if  Assigned(BridgeProc) then
  begin
    if BridgeProc.Running then
    begin
      MainForm.BridgeMemo.Lines.Add('Bridge is already running');
      Exit(True);
    end
    else
    begin
      MainForm.BridgeMemo.Lines.Add('Bridge is already assigned but NOT running');
      Exit(False);
    end;
  end;
  BridgeDir := ExtractFilePath(Application.ExeName) + 'bridge';
  if not DirectoryExists(BridgeDir) then
  begin
    MainForm.BridgeMemo.Lines.Add('Bridge directory not found: ' + BridgeDir);
    Exit;
  end;
  BridgeProc := TProcess.Create(nil);
  BridgeProc.Options := [poUsePipes, poNoConsole, poNewProcessGroup];
  BridgeProc.ShowWindow := swoNone;
  BridgeProc.Executable := 'C:\php\php.exe';
  BridgeProc.CurrentDirectory := BridgeDir;
  BridgeProc.Parameters.Add('cortex_ws.php');

  MainForm.BridgeMemo.Lines.Add('Starting bridge...');
  MainForm.BridgeMemo.Lines.Add('Executable: ' + BridgeProc.Executable);
  MainForm.BridgeMemo.Lines.Add('CurrentDirectory: ' + BridgeProc.CurrentDirectory);
  MainForm.BridgeMemo.Lines.Add('Parameters: ' + BridgeProc.Parameters.Text);
  try
    BridgeProc.Execute;
    MainForm.BridgeMemo.Lines.Add('Execute returned without exception');
    MainForm.Timer1.Enabled := True;
    Result := True;
  except
    on E: Exception do
    begin
      MainForm.BridgeMemo.Lines.Add('Exception: ' + E.ClassName + ' - ' + E.Message);
      BridgeProc.Free;
      BridgeProc := nil;
      Exit(False);
    end;
  end;
end;

procedure StopBridge;
begin
  if Assigned(BridgeProc) then
  begin
    if BridgeProc.Running then
    begin
      BridgeProc.Terminate(0);
    end;
    BridgeProc.Free;
    BridgeProc := nil;
  end;
end;

end.
