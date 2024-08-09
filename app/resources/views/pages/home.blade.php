@extends('layouts.topnav')

@section('content')
    <div class="row">
        <div class="col-sm-8 col-sm-offset-2">
            <p><img src="{{ asset('assets/app/images/opendominion.png') }}" class="img-responsive center-block" alt="OpenDominion"></p>
        </div>
    </div>

    <div class="row">

        <div class="col-sm-3 hidden-xs">
            <p><img src="{{ asset('assets/app/images/human-scene.jpg') }}" class="img-responsive center-block" alt="Human Thief"></p>
        </div>

        <div class="col-sm-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Welcome to OpenDominion!</h3>
                </div>
                <div class="box-body">
                    <p>OpenDominion is a free online text-based strategy game in a medieval fantasy setting. You control a nation called a 'dominion', along with its resources, buildings, land and units. You are placed in a realm with other dominions and you must work together to make your realm the wealthiest and most powerful in the land and crush your enemies!</p>

                    <p>OpenDominion is a free and open source remake of Dominion from Kamikaze Games, which ran from 2000 to 2012 before <a href="http://dominion.opendominion.net/GameOver.htm" target="_blank">stopping indefinitely <i class="fa fa-external-link"></i></a>.</p>
                    
                    @if (Auth::user() == null)
                        <p>To start playing, <a href="{{ route('auth.register') }}">register</a> an account and sign up for a round after registration. If you already have an account, <a href="{{ route('auth.login') }}">login</a> instead.</p>
                    @else
                        <p>Vist your <a href="{{ route('dashboard') }}">dashboard</a> to register for the current round or select a dominion to play.</p>
                    @endif

                    <p>To help you get started, please consult the following resources:</p>

                    <ul>
                        <li><a href="{{ route('scribes.overview') }}">How to Play (The Scribes)</a></li>
                        <li><a href="https://wiki.opendominion.net/wiki/My_First_Round" target="_blank">My First Round <i class="fa fa-external-link"></i></a> on the <a href="https://wiki.opendominion.net/" target="_blank">OpenDominion Wiki <i class="fa fa-external-link"></i></a>.</li>
                        <li>A mirror of the <a href="http://dominion.opendominion.net/" target="_blank">original website <i class="fa fa-external-link"></i></a> <strong>(Outdated)</strong> </li>
                    </ul>

                    @if ($discordInviteLink = config('app.discord_invite_link'))
                        <p>Also feel free to join the OpenDominion <a href="{{ $discordInviteLink }}" target="_blank">Discord server <i class="fa fa-external-link"></i></a>! It's the main place for game announcements, game-related chat and development chat.</p>
                    @endif

                    <p>OpenDominion is open source software and can be found on <a href="https://github.com/OpenDominion/OpenDominion" target="_blank">GitHub <i class="fa fa-external-link"></i></a>.</p>
                </div>
            </div>
        </div>

        <div class="col-sm-3 hidden-xs">
            <p><img src="{{ asset('assets/app/images/darkelf-scene.jpg') }}" class="img-responsive center-block" alt="Dark Elf Mage"></p>
        </div>

    </div>
    <div class="row">

        <div class="col-sm-3">
            <div class="box">
                <div class="box-header with-border text-center">
                    <h3 class="box-title">
                        @if ($currentRound === null)
                            Current Round
                        @else
                            {{ $currentRound->hasStarted() ? 'Current' : 'Next' }} Round: <strong>{{ $currentRound->number }}</strong>
                        @endif
                    </h3>
                </div>
                @if ($currentRound === null || $currentRound->hasEnded())
                    <div class="box-body text-center" style="padding: 0; border-bottom: 1px solid #f4f4f4;">
                        <p style="font-size: 1.5em;" class="text-red">Inactive</p>
                    </div>
                    <div class="box-body text-center">
                        <p><strong>There is no ongoing round.</strong></p>
                        @if ($discordInviteLink = config('app.discord_invite_link'))
                            <p>Check the Discord for more information.</p>

                            <p style="padding: 0 20px;">
                                <a href="{{ $discordInviteLink }}" target="_blank">
                                    <img src="{{ asset('assets/app/images/join-the-discord.png') }}" alt="Join the Discord" class="img-responsive">
                                </a>
                            </p>
                        @endif
                    </div>
                @else
                    @if ($currentRound->realmAssignmentDate() > now())
                        <div class="box-body text-center" style="padding: 0; border-bottom: 1px solid #f4f4f4;">
                            <p style="font-size: 1.5em;" class="text-yellow">Open for Registration</p>
                        </div>
                        <div class="box-body text-center">
                            <p>The deadline to register a pack is in {{ $currentRound->timeUntilRealmAssignment() }} ({{ $currentRound->realmAssignmentDate() }}).</p>
                            <p>The round will start in {{ $currentRound->timeUntilStart() }} ({{ $currentRound->start_date }}) and lasts for {{ $currentRound->durationInDays() }} days.</p>
                        </div>
                    @elseif ($currentRound->start_date > now())
                        <div class="box-body text-center" style="padding: 0; border-bottom: 1px solid #f4f4f4;">
                            <p style="font-size: 1.5em;" class="text-yellow">Starting Soon</p>
                        </div>
                        <div class="box-body text-center">
                            <p>Individual registration is still open!</p>
                            <p>The round will start in {{ $currentRound->timeUntilStart() }} ({{ $currentRound->start_date }}) and lasts for {{ $currentRound->durationInDays() }} days.</p>
                        </div>
                    @else
                        <div class="box-body text-center" style="padding: 0;">
                            <p style="font-size: 1.5em;" class="text-green">Active</p>
                        </div>
                    @endif
                    <div class="box-body table-responsive no-padding">
                        <table class="table">
                            <colgroup>
                                <col width="50%">
                                <col width="50%">
                            </colgroup>
                            <tbody>
                                <tr>
                                    <td class="text-center">Day:</td>
                                    <td class="text-center">
                                        {{ number_format($currentRound->daysInRound()) }} / {{ number_format($currentRound->durationInDays()) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center">Players:</td>
                                    <td class="text-center">{{ number_format($currentRound->dominions->where('user_id', '!=', null)->count()) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-center">Realms:</td>
                                    <td class="text-center">{{ number_format($currentRound->realms->count() - 1) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer text-center">
                        @if ($currentRound->daysUntilEnd() < 7)
                            <p>
                                <em class="text-red">The round ends in {{ $currentRound->daysUntilEnd() }} {{ str_plural('day', $currentRound->daysUntilEnd()) }} and {{ $currentRound->hoursUntilReset() - 1 }} {{ str_plural('hour', $currentRound->hoursUntilReset() - 1) }}.</em>
                            </p>
                        @endif
                        <p>
                            <a href="{{ route('round.register', $currentRound) }}" class="btn btn-primary">Register</a>
                        </p>
                    </div>
                @endif
            </div>

            <div>
                <a href="https://anchor.fm/riol-talk" target="_blank">
                    <img class="img-responsive" src="https://d3t3ozftmdmh3i.cloudfront.net/production/podcast_uploaded/19200887/19200887-1636311189500-b3f26a6c8046e.jpg" />
                </a>
            </div>
        </div>

        <div class="col-sm-6">
            @if ($currentRound !== null)
                <div class="box">
                    <div class="box-header with-border text-center">
                        <h3 class="box-title">
                            {{ $currentRound->hasStarted() && !$currentRound->hasEnded() ? 'Current' : 'Previous' }} Round Rankings
                        </h3>
                    </div>
                    @if ($currentRankings !== null && !$currentRankings->isEmpty())
                        <div class="box-body table-responsive text-center no-padding">
                            <table class="table">
                                <colgroup>
                                    <col>
                                    <col>
                                    <col>
                                    <col>
                                </colgroup>
                                <thead>
                                </thead>
                                <tbody>
                                    @foreach ($currentRankings as $row)
                                        <tr>
                                            <td class="text-center">{{ $row->rank }}</td>
                                            <td>
                                                {{ $row->dominion_name }} (#{{ $row->realm_number }})
                                            </td>
                                            <td class="text-center">{{ number_format($row->value) }}</td>
                                            <td class="text-center">
                                                @php
                                                    $rankChange = (int) ($row->previous_rank - $row->rank);
                                                @endphp
                                                @if ($rankChange > 0)
                                                    <span class="text-success"><i class="fa fa-caret-up"></i> {{ $rankChange }}</span>
                                                @elseif ($rankChange === 0)
                                                    <span class="text-warning">-</span>
                                                @else
                                                    <span class="text-danger"><i class="fa fa-caret-down"></i> {{ abs($rankChange) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="box-body text-center">
                            No rankings recorded yet.
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <div class="col-sm-3 hidden-xs">
            <div class="text-center">
                <iframe src="https://discord.com/widget?id=325315157335212032&theme={{ Auth::user() && Auth::user()->skin == 'skin-classic' ? 'dark' : 'light' }}" width="255" height="500" allowtransparency="true" frameborder="0" sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts"></iframe>
            </div>
        </div>

    </div>
    <div class="row">

        <div class="col-sm-3 hidden visible-xs">
            <p><img src="{{ asset('assets/app/images/human-scene.jpg') }}" class="img-responsive center-block" alt="Human Thief"></p>
        </div>

        <div class="col-sm-3 hidden visible-xs">
            <p><img src="{{ asset('assets/app/images/darkelf-scene.jpg') }}" class="img-responsive center-block" alt="Dark Elf Mage"></p>
        </div>

    </div>
@endsection
