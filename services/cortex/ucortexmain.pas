unit uCortexMain;

{$mode objfpc}{$H+}

interface

uses
  Classes, SysUtils, Forms, Controls, Graphics, Dialogs, StdCtrls, ExtCtrls,
  uBrainTypes, uCortexServerThread, uCortexRuntime;

type

  { TMainForm }

  TMainForm = class(TForm)
    CortexMemo: TMemo;
    BridgeMemo: TMemo;
    Panel1: TPanel;
    Panel2: TPanel;
    Splitter1: TSplitter;
    Timer1: TTimer;
    procedure FormClose(Sender: TObject; var CloseAction: TCloseAction);
    procedure FormCreate(Sender: TObject);
    procedure Timer1Timer(Sender: TObject);
  private

  public

  end;

var
  MainForm: TMainForm;
  CortexThread: TCortexServerThread;

procedure InitBrainBase(var B: TBrainBase; InputSize, HiddenSize, OutputSize: Integer);

implementation

{$R *.lfm}

procedure InitLayer(var L: TLayer; InSize, OutSize: Integer);
var
  o, i: Integer;
begin
  SetLength(L.Weights, OutSize);
  SetLength(L.Biases, OutSize);

  for o := 0 to OutSize - 1 do
  begin
    SetLength(L.Weights[o], InSize);
    for i := 0 to InSize - 1 do
      L.Weights[o][i] := (Random - 0.5) * 0.2; // small random init
    L.Biases[o] := 0.0;
  end;
end;

procedure InitBrainBase(var B: TBrainBase; InputSize, HiddenSize, OutputSize: Integer);
begin
  B.Arch.Version := 1;
  B.Arch.InputSize := InputSize;
  B.Arch.OutputSize := OutputSize;
  SetLength(B.Arch.HiddenSizes, 1);
  B.Arch.HiddenSizes[0] := HiddenSize;

  SetLength(B.Trunk.Layers, 1);
  InitLayer(B.Trunk.Layers[0], InputSize, HiddenSize);

  SetLength(B.GameplayHead.Layers, 1);
  InitLayer(B.GameplayHead.Layers[0], HiddenSize, OutputSize);
end;

{ TMainForm }

procedure TMainForm.FormClose(Sender: TObject; var CloseAction: TCloseAction);
begin
  if Assigned(CortexThread) then
  begin
    CortexThread.Terminate;
    CortexThread.WaitFor;
    CortexThread.Free;
  end;
  StopBridge;
end;

procedure TMainForm.FormCreate(Sender: TObject);
begin
  CortexThread := TCortexServerThread.Create;
  StartBridge;
end;

procedure TMainForm.Timer1Timer(Sender: TObject);
var
  Count: Integer;
  S: string = '';
begin
  if Assigned(BridgeProc) and BridgeProc.Running then
  begin
    // --- stdout ---
    Count := BridgeProc.Output.NumBytesAvailable;
    while Count > 0 do
    begin
      SetLength(S, Count);
      BridgeProc.Output.Read(S[1], Count);
      BridgeMemo.Lines.Add('[OUT] ' + S);
      Count := BridgeProc.Output.NumBytesAvailable;
    end;

    // --- stderr ---
    Count := BridgeProc.Stderr.NumBytesAvailable;
    while Count > 0 do
    begin
      SetLength(S, Count);
      BridgeProc.Stderr.Read(S[1], Count);
      BridgeMemo.Lines.Add('[ERR] ' + S);
      Count := BridgeProc.Stderr.NumBytesAvailable;
    end;
  end
  else
  begin
    Timer1.Enabled := False;
    BridgeMemo.Lines.Add('BridgeProc is NOT running');
  end;
end;


end.

