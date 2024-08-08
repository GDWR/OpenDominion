@extends('layouts.master')

@section('page-header', 'Invasion Result')

@section('content')
    @php
        $boxColor = ($event->data['result']['success'] ? 'success' : 'danger');

        // todo: refactor/optimize
        // Invert box color if we are the target
        if ($event->target->id === $selectedDominion->id) {
            $boxColor = ($event->data['result']['success'] ? 'danger' : 'success');
        }

        $sourceName = 'You';
        if ($event->source->id != $selectedDominion->id) {
            $sourceName = sprintf("%s (#%s)", $event->source->name, $event->source->realm->number);
        }

        $targetName = 'You';
        if ($event->target->id != $selectedDominion->id) {
            $targetName = sprintf("%s (#%s)", $event->target->name, $event->target->realm->number);
        }
    @endphp
    <div class="row">
        <div class="col-sm-12 col-md-8 col-md-offset-2">
            <div class="box box-{{ $boxColor }}">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="ra ra-crossed-swords"></i>
                        {{ $event->source->name }} (#{{ $event->source->realm->number }})
                        vs
                        {{ $event->target->name }} (#{{ $event->target->realm->number }})
                    </h3>
                </div>
                <div class="box-body no-padding">
                    <div class="row">

                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2" class="text-center">
                                            @if ($event->source->id === $selectedDominion->id)
                                                Your Losses
                                            @else
                                                {{ $event->source->name }} (#{{ $event->source->realm->number }})'s Losses
                                            @endif
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($event->data['attacker']['unitsLost'] as $unitSlot => $amount)
                                        @if ($amount === 0)
                                            @continue
                                        @endif
                                        @php
                                            $unitType = "unit{$unitSlot}";
                                        @endphp
                                        <tr>
                                            <td>
                                                {!! $unitHelper->getUnitTypeIconHtml($unitType, $event->source->race) !!}
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $event->source->race) }}">
                                                    {{ $event->source->race->units->where('slot', $unitSlot)->first()->name }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ number_format($amount) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if (isset($event->data['attacker']['boatsLost']))
                                        <tr>
                                            <td><i class="ra ra-droplet text-blue"></i> Boats</td>
                                            <td>{{ number_format($event->data['attacker']['boatsLost']) }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2" class="text-center">
                                            @if ($event->target->id === $selectedDominion->id)
                                                Your Losses
                                            @else
                                                {{ $event->target->name }} (#{{ $event->target->realm->number }})'s Losses
                                            @endif
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (array_sum($event->data['defender']['unitsLost']) === 0)
                                        <tr>
                                            <td colspan="2" class="text-center">
                                                <em>None</em>
                                            </td>
                                        </tr>
                                    @else
                                        @foreach ($event->data['defender']['unitsLost'] as $unitSlot => $amount)
                                            @if ($amount === 0)
                                                @continue
                                            @endif
                                            @php
                                                $unitType = (($unitSlot !== 'draftees') ? "unit{$unitSlot}" : 'draftees');
                                            @endphp
                                            <tr>
                                                <td>
                                                    {!! $unitHelper->getUnitTypeIconHtml($unitType, $event->target->race) !!}
                                                    <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $event->target->race) }}">
                                                        @if ($unitType === 'draftees')
                                                            Draftees
                                                        @else
                                                            {{ $event->target->race->units->where('slot', $unitSlot)->first()->name }}
                                                        @endif
                                                    </span>
                                                </td>
                                                <td>
                                                    {{ number_format($amount) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                    @if (isset($event->data['defender']['boatsLost']))
                                        <tr>
                                            <td><i class="ra ra-droplet text-blue"></i> Boats</td>
                                            <td>{{ number_format($event->data['defender']['boatsLost']) }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2" class="text-center">
                                            @if ($event->target->id === $selectedDominion->id)
                                                Land Lost
                                            @else
                                                Land Conquered
                                            @endif
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (!isset($event->data['attacker']['landConquered']))
                                        <tr>
                                            <td colspan="2" class="text-center">
                                                <em>None</em>
                                            </td>
                                        </tr>
                                    @else
                                        @foreach ($event->data['attacker']['landConquered'] as $landType => $amount)
                                            @if ($amount === 0)
                                                @continue
                                            @endif
                                            <tr>
                                                <td>{{ ucfirst($landType) }}</td>
                                                <td>{{ number_format($amount) }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            @if (isset($event->data['defender']['recentlyInvadedCount']) && $event->data['defender']['recentlyInvadedCount'] > 0 && $event->data['result']['success'])
                                <p class="text-center">
                                    @if ($event->source->id === $selectedDominion->id)
                                        Because the target was recently invaded, their defensive losses are reduced.
                                    @else
                                        Because the target was recently invaded, {{ $event->target->name }} (# {{ $event->target->realm->number }})'s defensive losses are reduced.
                                    @endif
                                </p>
                            @endif

                            @if (isset($event->data['result']['overwhelmed']) && $event->data['result']['overwhelmed'])
                                <p class="text-center text-red">
                                    @if ($event->source->id === $selectedDominion->id)
                                        Because you were severely outmatched, you inflicted no casualties.
                                    @else
                                        Because {{ $event->source->name }} (# {{ $event->source->realm->number }}) was severely outmatched, they inflicted no casualties.
                                    @endif
                                </p>
                            @endif

                            @if (isset($event->data['attacker']['repeatInvasion']) && $event->data['attacker']['repeatInvasion'])
                                <p class="text-center text-red">
                                    @if ($event->source->id === $selectedDominion->id)
                                        Due to repeated invasions, you did not gain prestige, research points, or discovered land.
                                    @else
                                        Due to repeated invasions, {{ $event->source->name }} (# {{ $event->source->realm->number }}) did not gain prestige, research points, or discovered land.
                                    @endif
                                </p>
                            @endif

                            @if (isset($event->data['attacker']['habitualInvasion']) && $event->data['attacker']['habitualInvasion'])
                                <p class="text-center text-red">
                                    @if ($event->source->id === $selectedDominion->id)
                                        Due to repeated invasions, prestige gain is reduced.
                                    @else
                                        Due to repeated invasions, {{ $event->source->name }} (# {{ $event->source->realm->number }})'s prestige gain is reduced.
                                    @endif
                                </p>
                            @endif

                            {{-- Additional information to show if we are in the attacker's realm --}}
                            @if ($event->source->realm_id === $selectedDominion->realm_id)
                                @if (isset($event->data['attacker']['prestigeChange']))
                                    @php
                                        $prestigeChange = $event->data['attacker']['prestigeChange'];
                                    @endphp
                                    @if ($prestigeChange < 0)
                                        <p class="text-center text-red">
                                            {{ $sourceName }} lost <b>{{ number_format(-$prestigeChange) }}</b> prestige.
                                        </p>
                                    @elseif ($prestigeChange > 0)
                                        <p class="text-center text-green">
                                            {{ $sourceName }} gained <b>{{ number_format($prestigeChange) }}</b> prestige.
                                        </p>
                                    @endif
                                @endif
                                @if (isset($event->data['attacker']['researchPoints']))
                                    <p class="text-center text-green">
                                        {{ $sourceName }} gained <b>{{ number_format($event->data['attacker']['researchPoints']) }}</b> research points.
                                    </p>
                                @endif

                                @if (isset($event->data['attacker']['landVerdantBloom']))
                                    <p class="text-center text-green">
                                        Additionally, {{ number_format($event->data['attacker']['landVerdantBloom']) }} acres will be converted to forest due to Verdant Bloom.
                                    </p>
                                @endif
                                @if (isset($event->data['attacker']['landErosion']))
                                    <p class="text-center text-green">
                                        Additionally, {{ number_format($event->data['attacker']['landErosion']) }} acres will be converted to water due to Erosion.
                                    </p>
                                @endif
                                @if (isset($event->data['attacker']['plunder']))
                                    <p class="text-center text-green">
                                        @if (isset($event->data['attacker']['plunder']['mana']) && $event->data['attacker']['plunder']['mana'] > 0)
                                            {{ $sourceName }} plundered {{ number_format($event->data['attacker']['plunder']['mana']) }} mana.
                                        @else
                                            {{ $sourceName }} plundered {{ number_format($event->data['attacker']['plunder']['platinum']) }} platinum and {{ number_format($event->data['attacker']['plunder']['gems']) }} gems.
                                        @endif
                                    </p>
                                @endif
                                @if (isset($event->data['attacker']['salvage']))
                                    <p class="text-center text-green">
                                        @if (isset($event->data['attacker']['salvage']['lumber']) || isset($event->data['attacker']['salvage']['ore']))
                                            {{ $sourceName }} salvaged {{ number_format($event->data['attacker']['salvage']['lumber']) }} lumber and {{ number_format($event->data['attacker']['salvage']['ore']) }} ore.
                                        @endif
                                    </p>
                                @endif
                                @if (isset($event->data['attacker']['conversion']))
                                    <p class="text-center text-green">
                                        {{ $unitHelper->getConvertedUnitsString($event->data['attacker']['conversion'], $event->source->race) }}
                                    </p>
                                @endif
                                @if (isset($event->data['attacker']['xpGain']))
                                    <p class="text-center text-green">
                                        +<b>{{ number_format($event->data['attacker']['xpGain'], 2) }}</b> XP
                                    </p>
                                @endif
                            @endif

                            {{-- Additional information to show if we are in the defender's realm --}}
                            @if ($event->target->realm_id === $selectedDominion->realm_id)
                                @if (isset($event->data['defender']['prestigeChange']))
                                    @php
                                        $prestigeChange = $event->data['defender']['prestigeChange'];
                                    @endphp
                                    @if ($prestigeChange < 0)
                                        <p class="text-center text-red">
                                            {{ $targetName }} lost <b>{{ number_format(-$prestigeChange) }}</b> prestige.
                                        </p>
                                    @elseif ($prestigeChange > 0)
                                        <p class="text-center text-green">
                                            {{ $targetName }} gained <b>{{ number_format($prestigeChange) }}</b> prestige.
                                        </p>
                                    @endif
                                @endif
                                @if (isset($event->data['defender']['xpLoss']))
                                    <p class="text-center text-green">
                                        -<b>{{ number_format($event->data['defender']['xpLoss'], 2) }}</b> XP
                                    </p>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
