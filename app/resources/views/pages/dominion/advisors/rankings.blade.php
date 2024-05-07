@extends('layouts.master')

@php
    $target = $selectedDominion;
    $pageHeader = 'Rankings Advisor';
    if ($targetDominion != null) {
        $target = $targetDominion;
        $pageHeader .= ' for '.$target->name;
    }
@endphp

@section('page-header', $pageHeader)

@section('content')
    @include('partials.dominion.advisor-selector')
    <div class="row">

        <div class="col-md-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-trophy"></i> {{ $pageHeader }}</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-striped">
                        <colgroup>
                            <col>
                            <col>
                            <col width="100">
                            <col width="100">
                            <col width="100">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Ranking</th>
                                <th class="text-right">Statistic</th>
                                <th></th>
                                <th class="text-center">Rank</th>
                                <th class="text-center">Change</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $myRankings = $rankingsService->getRankingsForDominion($target); @endphp
                            @foreach ($rankingsHelper->getRankings() as $ranking)
                                <tr>
                                    <td>
                                        <i class="ra ra-fw {{ $ranking && $ranking['title_icon'] ? $ranking['title_icon'] : 'ra-trophy' }}" data-toggle="tooltip" title="{{ $ranking['name'] }}"></i>
                                        <a href="{{ route('dominion.rankings', $ranking['key']) }}">
                                            {{ $ranking['name'] }}
                                        </a>
                                    </td>
                                    <td class="text-right">
                                        {{ $ranking['stat_label'] }}:
                                    </td>
                                    @if (isset($myRankings[$ranking['key']]))
                                        <td>
                                            {{ number_format($myRankings[$ranking['key']]->value) }}
                                        </td>
                                        <td class="text-center">
                                            {{ $myRankings[$ranking['key']]->rank }}
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $rankChange = (int) ($myRankings[$ranking['key']]->previous_rank - $myRankings[$ranking['key']]->rank);
                                            @endphp
                                            @if ($rankChange > 0)
                                                <span class="text-success"><i class="fa fa-caret-up"></i> {{ $rankChange }}</span>
                                            @elseif ($rankChange === 0)
                                                <span class="text-warning">-</span>
                                            @else
                                                <span class="text-danger"><i class="fa fa-caret-down"></i> {{ abs($rankChange) }}</span>
                                            @endif
                                        </td>
                                    @else
                                        <td>
                                            0
                                        </td>
                                        <td class="text-center">
                                            -
                                        </td>
                                        <td class="text-center">
                                            -
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>The rankings advisor tells you how you are doing in the world compared to other dominions.</p>
                    <p>Rankings are updated every day.</p>
                </div>
            </div>

            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Titles Held</h3>
                </div>
                <div class="box-body">
                    <p style="font-size: 16px; line-height: 26px;">
                        @if ($myRankings)
                            @foreach ($rankingsHelper->getRankings() as $ranking)
                                @if (isset($myRankings[$ranking['key']]) && $myRankings[$ranking['key']]->rank == 1)
                                    @if ($ranking['title'])
                                        <i class="ra {{ $ranking && $ranking['title_icon'] ? $ranking['title_icon'] : 'ra-trophy' }}" data-toggle="tooltip" title="{{ $ranking['name'] }}" style="font-size: 22px; vertical-align: text-bottom;"></i>
                                        {{ $ranking['title'] }}<br/>
                                    @endif
                                @endif
                            @endforeach
                        @else
                            You do not hold any titles.
                        @endif
                    </p>
                </div>
            </div>
        </div>

    </div>
@endsection
