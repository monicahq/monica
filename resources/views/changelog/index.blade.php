@extends('layouts.skeleton')

@section('content')
  <div class="changelog">

    <section class="ph3 ph0-ns pv4 cf w-100 bg-gray-monica">
      <div class="mt4 mw7 center mb3">
        <h3>{{ trans('changelog.title') }}</h3>

        @if (\App::getLocale() != 'en')
        <p>{{ trans('changelog.note') }}</p>
        @endif
      </div>

      @foreach ($changelogs as $changelog)
      <div class="mw7 center br3 ba b--gray-monica bg-white mb4 pa4">
        <div class="f7 mb2">
          {{ $changelog['date'] }}
        </div>
        <div>
          <h2>{{ $changelog['title'] }}</h2>
          {!! (new \Parsedown())->text($changelog['description']) !!}
        </div>
      </div>
      @endforeach
    </section>

  </div>
@endsection
