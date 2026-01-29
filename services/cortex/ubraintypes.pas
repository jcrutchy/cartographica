unit uBrainTypes;

{$mode objfpc}{$H+}

interface

uses
  SysUtils, fpjson;

type

  PBrainBase = ^TBrainBase;
  PBrainDelta = ^TBrainDelta;
  PNpcBrainInstance = ^TNpcBrainInstance;

  TFloat = Single;

  TVector = array of TFloat;
  TMatrix = array of array of TFloat;

  TLayer = record
    Weights: TMatrix;
    Biases: TVector;
  end;

  TLayerArray = array of TLayer;

  TBrainArchitecture = record
    Version: Integer;
    InputSize: Integer;
    HiddenSizes: array of Integer;
    OutputSize: Integer;
    // Future: dynamic layers, NEAT nodes, connections
  end;

  TTrunk = record
    Layers: TLayerArray;
  end;

  THead = record
    Layers: TLayerArray;
  end;

  TBrainBase = record
    Arch: TBrainArchitecture;
    Trunk: TTrunk;
    GameplayHead: THead;
    // Future: ModerationHead
    // Future: ProposalHead
  end;

  TBrainDelta = record
    Trunk: TTrunk;
    GameplayHead: THead;
    // Future: ModerationHead
    // Future: ProposalHead
  end;

  TNpcBrainInstance = record
    NpcId: Int64;
    RoleId: Integer;
    Traits: TVector;
    Delta: TBrainDelta;
  end;

  TProposal = record
    ProposalType: string;
    Payload: TJSONData;
    Confidence: Single;
  end;

implementation

end.
