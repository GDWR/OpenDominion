@extends('layouts.topnav')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Races</h3>
        </div>
        <div class="box-body table-responsive no-padding">
            <div class="col-md-12 col-md-6">
                <div class="box-header with-border">
                    <h4 class="box-title">Good</h4>
                </div>
                <table class="table table-striped">
                    <tbody>
                        @foreach ($goodRaces as $race)
                            <tr>
                                <td>
                                    <a href="{{ route('scribes.race', str_slug($race['name'])) }}">{{ $race['name'] }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="col-md-12 col-md-6">
                <div class="box-header with-border">
                    <h4 class="box-title">Evil</h4>
                </div>
                <table class="table table-striped">
                    <tbody>
                        @foreach ($evilRaces as $race)
                            <tr>
                                <td>
                                    <a href="{{ route('scribes.race', $race['name']) }}">{{ $race['name'] }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
