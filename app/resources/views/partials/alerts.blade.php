@if (isset($selectedDominion) && !Route::is(['home', 'valhalla*', 'scribes*', 'user-agreement']))
    @if ($selectedDominion->isLocked())
        <div class="alert alert-warning">
            @if ($selectedDominion->locked_at !== null)
                <p><i class="icon fa fa-warning"></i> This dominion is <strong>locked</strong> due to a rules violation. No actions can be performed and no ticks will be processed.</p>
            @elseif ($selectedDominion->abandoned_at !== null && $selectedDominion->abandoned_at < now())
                <p><i class="icon fa fa-warning"></i> This dominion is <strong>locked</strong> due to abandonment. No actions can be performed and no ticks will be processed.</p>
            @else
                <p><i class="icon fa fa-warning"></i> This dominion is <strong>locked</strong> due to the round having ended. No actions can be performed and no ticks will be processed.</p>
                <p>Go to your <a href="{{ route('dashboard') }}">dashboard</a> to check if new rounds are open to play.</p>
            @endif
        </div>
    @elseif (now()->diffInHours($selectedDominion->round->end_date) < 24)
        <div class="alert alert-warning">
            <p><i class="icon fa fa-warning"></i> The round will end in {{ now()->longAbsoluteDiffForHumans($selectedDominion->round->end_date, 2) }}.
                @if ($selectedDominion->round->offensiveActionsAreEnabledButCanBeDisabled())
                    Offensive actions can be disabled at any time.
                @elseif ($selectedDominion->round->hasOffensiveActionsDisabled())
                    Offensive actions have been disabled.
                @endif
            </p>
        </div>
    @endif

    @if (!$selectedDominion->round->hasAssignedRealms())
        <div class="alert alert-warning">
            <p><i class="fa fa-warning"></i> The round has not yet started, but you can simulate your protection in advance. Realms will be assigned in {{ $selectedDominion->round->timeUntilRealmAssignment() }}, after which you will have 4 days to coordinate with your realm before the round starts.</p>
        </div>
    @endif

    @if ($selectedDominion->ai_enabled)
        <div class="alert alert-info">
            <p><i class="ra ra-robot-arm"></i> You have <a href="{{ route('dominion.bonuses.actions') }}">automated actions</a> scheduled in {{ hours_until_next_action($selectedDominion->ai_config, $selectedDominion->round->getTick()) }} tick(s).</p>
        </div>
    @endif
@endif

@if (!$errors->isEmpty())
    <div class="alert alert-danger alert-dismissible">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <h4>One or more errors occurred:</h4>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@foreach (['danger', 'warning', 'success', 'info'] as $alert_type)
    @if (Session::has('alert-' . $alert_type))
        <div class="alert alert-{{ $alert_type }} alert-dismissible">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <p>{{ Session::get('alert-' . $alert_type) }}</p>
        </div>
    @endif
@endforeach
