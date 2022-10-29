@extends('layouts.master')

@section('page-header', 'Heroes')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-knight-helmet"></i> Heroes</h3>
                </div>
                @if ($heroes->isEmpty())
                    <form class="form-horizontal" action="{{ route('dominion.heroes.create') }}" method="post" role="form">
                        @csrf
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">Name</label>
                                        <div class="col-sm-9">
                                            <input name="name" id="name" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="form-group hidden">
                                        <label class="col-sm-3 control-label">Class</label>
                                        <div class="col-sm-9">
                                            <select name="class" class="form-control">
                                                @foreach ($heroHelper->getClasses() as $class)
                                                    <option value="{{ $class['key'] }}">
                                                        {{ $class['name'] }} - Gains double XP from {{ $class['xp_bonus_type'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">Class</label>
                                        <div class="col-sm-9">
                                            <select name="trade" class="form-control">
                                                @foreach ($heroHelper->getTrades() as $trade)
                                                    <option value="{{ $trade['key'] }}">
                                                        {{ $trade['name'] }} - {{ str_replace('_', ' ', $trade['perk_type']) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Create Hero</button>
                        </div>
                    </form>
                @else
                    @foreach ($heroes as $hero)
                        <div class="box-body">
                            <div class="row">
                                <!--
                                <div class="col-md-6">
                                    <div class="row" style="font-size: 36px;">
                                        <div class="col-xs-3">
                                            <i class="ra ra-knight-helmet" title="Helmet" data-toggle="tooltip"></i><br/>
                                            <i class="ra ra-sword" title="Sword" data-toggle="tooltip"></i><br/>
                                            <i class="ra ra-shield" title="Shield" data-toggle="tooltip"></i>
                                        </div>
                                        <div class="col-xs-6">
                                            <img class="img-responsive" src="https://place-hold.it/200x300" />
                                        </div>
                                        <div class="col-xs-3">
                                            <i class="ra ra-gold-bar" title="Alchemist" data-toggle="tooltip"></i><br/>
                                            <i class="ra ra-falling" title="Ooopsie" data-toggle="tooltip"></i><br/>
                                            <i class="ra ra-roast-chicken" title="Hangry" data-toggle="tooltip"></i>
                                        </div>
                                    </div>
                                </div>
                                -->
                                <div class="col-md-3">
                                    <div class="text-center" style="font-size: 64px;">
                                        <i class="{{ $heroHelper->getTradeIconClass($hero->trade) }}" title="{{ $heroHelper->getTradeDisplayName($hero->trade) }}" data-toggle="tooltip"></i>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-center" style="font-size: 24px;">
                                        {{ $hero->name }}
                                    </div>
                                    <div class="text-center">
                                        Level {{ $heroCalculator->getHeroLevel($hero) }} {{ $heroHelper->getTradeDisplayName($hero->trade) }}
                                    </div>
                                    <div class="text-center">
                                        {{ $hero->experience }} / {{ $heroCalculator->getNextLevelXP($hero) }} XP
                                    </div>
                                    <div class="text-center">
                                        {{ $heroCalculator->getTradeDescription($hero) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>You can only have one hero at a time.</p>
                    <p>Your hero gains experience and levels up, increasing it's trade bonus and unlocking new upgrades.</p>
                    <p>Your hero gains 1 XP per acre conquered, 1 XP per info operation, and 5 XP per black/war operation.</p>
                    <p>You can also <a href="{{ route('dominion.heroes.retire') }}">retire your hero</a> and create another. The new hero will start with XP equal to half that of its predecessor.</p>
                    <!--
                    <table class="table table-condensed">
                        <thead>
                            <tr>
                                <th>Level</th>
                                <th>XP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($heroCalculator->getExperienceLevels() as $level)
                                @if ($level['level'] !== 0)
                                    <tr>
                                        <td>{{ $level['level'] }}</td>
                                        <td>{{ $level['xp'] }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                    -->
                </div>
            </div>
        </div>

    </div>
@endsection