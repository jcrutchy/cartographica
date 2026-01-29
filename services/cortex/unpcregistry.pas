unit uNpcRegistry;

{$mode objfpc}{$H+}

interface

uses
  SysUtils, uBrainTypes, uBrainRegistry, uBrainDelta;

type

  PNpcRecord = ^TNpcRecord;

  TNpcRecord = record
    NpcId: Int64;
    RoleId: Integer;
    Traits: TVector;
    BrainDelta: TBrainDelta;
    Reward: Double;
  end;

var
  NpcList: array of TNpcRecord;

function GetOrCreateNpc(NpcId: Int64; RoleId: Integer): PNpcRecord;

implementation

function GetOrCreateNpc(NpcId: Int64; RoleId: Integer): PNpcRecord;
var
  i, roleIndex: Integer;
  npc: PNpcRecord;
begin
  // Look for existing NPC
  for i := 0 to High(NpcList) do
    if NpcList[i].NpcId = NpcId then
      Exit(@NpcList[i]);

  // Create new NPC
  SetLength(NpcList, Length(NpcList) + 1);

  npc := @NpcList[High(NpcList)];
  npc^.NpcId := NpcId;
  npc^.RoleId := RoleId;
  npc^.Traits := DefaultTraits;

  // Find role index for delta initialization
  roleIndex := GetRoleIndex(RoleId);
  if roleIndex >= 0 then
    npc^.BrainDelta := CreateEmptyDelta(BaseBrains[roleIndex])
  else
    npc^.BrainDelta := CreateEmptyDelta(BaseBrains[0]); // fallback

  npc^.Reward := 0.0;

  Result := npc;
end;

end.
