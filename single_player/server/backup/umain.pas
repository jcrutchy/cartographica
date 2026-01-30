unit uMain;

{$mode objfpc}{$H+}

interface

uses
  Classes, SysUtils, Forms, Controls, Graphics, Dialogs, StdCtrls, ExtCtrls,
  uGameServer, uLog;

type

  { TFormMain }

  TFormMain = class(TForm)
    MemoLog: TMemo;
    Panel1: TPanel;
    Panel2: TPanel;
    procedure FormCreate(Sender: TObject);
    procedure FormDestroy(Sender: TObject);
  private
    FPendingLog: string;
    FShuttingDown: Boolean;
    FGameServer: TGameServer;
    procedure DoLogSync;
  public
    procedure LogThreadSafe(const S: string);
    procedure LogToMemo(const S: string);
  public
    property GameServer: TGameServer read FGameServer;
  end;

var
  FormMain: TFormMain;

implementation

{$R *.lfm}

{ TFormMain }

procedure TFormMain.FormCreate(Sender: TObject);
begin
  FShuttingDown := False;
  Log.SetCallback(@Self.LogThreadSafe);
  MemoLog.Lines.Add('Starting Cartographica server...');
  FGameServer := TGameServer.Create(8081);
  FGameServer.Start;
  MemoLog.Lines.Add('Listening on port 8081');
end;

procedure TFormMain.FormDestroy(Sender: TObject);
begin
  FShuttingDown := True;
  if Assigned(FGameServer) then
  begin
    FGameServer.Stop;
    FreeAndNil(FGameServer);
  end;
end;

procedure TFormMain.LogThreadSafe(const S: string);
begin
  if FShuttingDown then Exit;
  FPendingLog := S;
  TThread.Synchronize(nil, @DoLogSync);
end;

procedure TFormMain.DoLogSync;
begin
  if FShuttingDown then Exit;
  MemoLog.Lines.Add(FPendingLog);
  FPendingLog := '';
end;

procedure TFormMain.LogToMemo(const S: string);
begin
  if FShuttingDown then Exit;
  MemoLog.Lines.Add(S);
end;

end.
