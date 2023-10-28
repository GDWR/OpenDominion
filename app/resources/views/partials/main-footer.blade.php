<footer class="main-footer">
    <div class="row">

        <div class="col-xs-6">
            <span class="hidden-xs">Version: </span>{!! $version !!}
            &nbsp;|&nbsp;
            <span class="hidden-xs hidden-sm hidden-md"><i class="fa fa-github"></i> View this project on </span><a href="https://github.com/OpenDominion/OpenDominion" target="_blank">GitHub <i class="fa fa-external-link"></i></a>
        </div>

        <div class="col-xs-6 text-right">
            @if (config('app.discord_report_webhook'))
                <a href="#" data-toggle="modal" data-target="#reportModal">Report a Problem</a>
            @endif

            @if (isset($selectedDominion) && ($selectedDominion->round->isActive()))
                @if (config('app.discord_report_webhook'))
                    &nbsp;|&nbsp;
                @endif
                @php
                    $roundDay = $selectedDominion->round->daysInRound();
                    $roundDurationInDays = $selectedDominion->round->durationInDays();
                    $currentHour = $selectedDominion->round->hoursInDay();
                @endphp
                <span data-toggle="tooltip" data-placement="top" title="{{ now() }}">
                    Day <strong>{{ $roundDay }}</strong>/{{ $roundDurationInDays }}, Hour <strong>{{ $currentHour }}</strong>
                </span>
            @endif
        </div>

    </div>
</footer>

<div class="modal fade" id="reportModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('dominion.misc.report') }}" method="POST" role="form">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Report a Problem</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="type">Type:</label>
                        <select name="type" class="form-control" id="type">
                            <option value="bug">Bug Report</option>
                            <option value="abuse">Cheating/Abuse</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea name="description" class="form-control" id="description" rows=5></textarea>
                        <span class="help-block">Please be as detailed as possible to help us address the issue.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

