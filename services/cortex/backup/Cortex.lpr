program Cortex;

{$mode objfpc}{$H+}

uses
  {$IFDEF UNIX}
  cthreads,
  {$ENDIF}
  {$IFDEF HASAMIGA}
  athreads,
  {$ENDIF}
  Interfaces, // this includes the LCL widgetset
  Forms, uCortexMain, uBrainDelta, uBrainForward, uBrainRegistry,
  uBrainTypes, uCortexRuntime, uNpcRegistry, uCortexServerThread
  { you can add units after this };

{$R *.res}

begin
  Randomize;
  LoadRegistries;   // loads features, actions, roles, traits
  InitBaseBrains;   // creates base brains for each role
  RequireDerivedFormResource:=True;
  Application.Scaled:=True;
  {$PUSH}{$WARN 5044 OFF}
  Application.MainFormOnTaskbar:=True;
  {$POP}
  Application.Initialize;
  Application.CreateForm(TForm1, Form1);
  Application.Run;
end.

