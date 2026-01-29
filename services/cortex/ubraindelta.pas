unit uBrainDelta;

{$mode objfpc}{$H+}

interface

uses
  uBrainTypes;

const
  LearningRate = 0.02;

procedure ApplyRewardToDelta(var Delta: TBrainDelta; const Reward: Double);
function CreateEmptyDelta(const Base: TBrainBase): TBrainDelta;

implementation

function CreateEmptyDelta(const Base: TBrainBase): TBrainDelta;
var
  l, o, i: Integer;
begin
  // Trunk layers
  SetLength(Result.Trunk.Layers, Length(Base.Trunk.Layers));
  for l := 0 to High(Base.Trunk.Layers) do
  begin
    SetLength(Result.Trunk.Layers[l].Weights, Length(Base.Trunk.Layers[l].Weights));
    for o := 0 to High(Base.Trunk.Layers[l].Weights) do
    begin
      SetLength(Result.Trunk.Layers[l].Weights[o], Length(Base.Trunk.Layers[l].Weights[o]));
      for i := 0 to High(Result.Trunk.Layers[l].Weights[o]) do
        Result.Trunk.Layers[l].Weights[o][i] := 0.0;
    end;

    SetLength(Result.Trunk.Layers[l].Biases, Length(Base.Trunk.Layers[l].Biases));
    for o := 0 to High(Result.Trunk.Layers[l].Biases) do
      Result.Trunk.Layers[l].Biases[o] := 0.0;
  end;

  // Gameplay head layers
  SetLength(Result.GameplayHead.Layers, Length(Base.GameplayHead.Layers));
  for l := 0 to High(Base.GameplayHead.Layers) do
  begin
    SetLength(Result.GameplayHead.Layers[l].Weights, Length(Base.GameplayHead.Layers[l].Weights));
    for o := 0 to High(Base.GameplayHead.Layers[l].Weights) do
    begin
      SetLength(Result.GameplayHead.Layers[l].Weights[o], Length(Base.GameplayHead.Layers[l].Weights[o]));
      for i := 0 to High(Result.GameplayHead.Layers[l].Weights[o]) do
        Result.GameplayHead.Layers[l].Weights[o][i] := 0.0;
    end;

    SetLength(Result.GameplayHead.Layers[l].Biases, Length(Base.GameplayHead.Layers[l].Biases));
    for o := 0 to High(Result.GameplayHead.Layers[l].Biases) do
      Result.GameplayHead.Layers[l].Biases[o] := 0.0;
  end;
end;

procedure ApplyRewardToDelta(var Delta: TBrainDelta; const Reward: Double);
var
  l, o: Integer;
begin
  // Simple rule:
  // Reward nudges biases in the direction of the reward.
  // This is intentionally tiny and safe.

  for l := 0 to High(Delta.GameplayHead.Layers) do
    for o := 0 to High(Delta.GameplayHead.Layers[l].Biases) do
      Delta.GameplayHead.Layers[l].Biases[o] += Reward * LearningRate;
end;

end.
