unit uBrainForward;

{$mode objfpc}{$H+}

interface

uses
  uBrainTypes, math;

function DecideAction(const Brain: TBrainBase; const Delta: TBrainDelta; const State: TVector; const Traits: TVector): Integer;
function ForwardLayer(const L: TLayer; const Input: TVector): TVector;
function ForwardTrunk(const T: TTrunk; const Input: TVector): TVector;
function ForwardHead(const H: THead; const TrunkOut: TVector): TVector;
function ArgMax(const V: TVector): Integer;
function ApplyTraitBias(const Output: TVector; const Traits: TVector): TVector;
function CombineBrain(const Base: TBrainBase; const Delta: TBrainDelta): TBrainBase;

implementation

function Tanh(x: TFloat): TFloat;
begin
  Result := math.Tanh(x);
end;

function DecideAction(const Brain: TBrainBase; const Delta: TBrainDelta; const State: TVector; const Traits: TVector): Integer;
var
  Combined: TBrainBase;
  h, outVec, biased: TVector;
begin
  Combined := CombineBrain(Brain, Delta);

  h := ForwardTrunk(Combined.Trunk, State);
  outVec := ForwardHead(Combined.GameplayHead, h);
  biased := ApplyTraitBias(outVec, Traits);

  Result := ArgMax(biased);
end;

function ForwardLayer(const L: TLayer; const Input: TVector): TVector;
var
  o, i: Integer;
  sum: TFloat;
begin
  SetLength(Result, Length(L.Biases));
  for o := 0 to High(Result) do
  begin
    sum := L.Biases[o];
    for i := 0 to High(Input) do
      sum := sum + L.Weights[o][i] * Input[i];
    Result[o] := Tanh(sum);
  end;
end;

function ForwardTrunk(const T: TTrunk; const Input: TVector): TVector;
var
  i: Integer;
  act: TVector;
begin
  act := Input;
  for i := 0 to High(T.Layers) do
    act := ForwardLayer(T.Layers[i], act);
  Result := act;
end;

function ForwardHead(const H: THead; const TrunkOut: TVector): TVector;
var
  i: Integer;
  act: TVector;
begin
  act := TrunkOut;
  for i := 0 to High(H.Layers) do
    act := ForwardLayer(H.Layers[i], act);
  Result := act;
end;

function ArgMax(const V: TVector): Integer;
var
  i: Integer;
  bestIdx: Integer;
  bestVal: TFloat;
begin
  bestIdx := 0;
  bestVal := V[0];
  for i := 1 to High(V) do
    if V[i] > bestVal then
    begin
      bestVal := V[i];
      bestIdx := i;
    end;
  Result := bestIdx;
end;

function ApplyTraitBias(const Output: TVector; const Traits: TVector): TVector;
var
  i: Integer;
begin
  // Copy original output
  SetLength(Result, Length(Output));
  for i := 0 to High(Output) do
    Result[i] := Output[i];

  // Simple example:
  // aggression increases preference for action 0 (move_north)
  // curiosity increases preference for action 2 (move_east)
  // caution increases preference for action 4 (idle)

  if Length(Traits) >= 3 then
  begin
    Result[0] := Result[0] + Traits[0] * 0.5; // aggression
    Result[2] := Result[2] + Traits[1] * 0.5; // curiosity
    Result[4] := Result[4] + Traits[2] * 0.5; // caution
  end;
end;

function CombineBrain(const Base: TBrainBase; const Delta: TBrainDelta): TBrainBase;
var
  i, o, l: Integer;
begin
  Result := Base;

  // Apply delta to gameplay head only for now
  for l := 0 to High(Base.GameplayHead.Layers) do
    for o := 0 to High(Base.GameplayHead.Layers[l].Biases) do
      Result.GameplayHead.Layers[l].Biases[o] += Delta.GameplayHead.Layers[l].Biases[o];
end;

end.
