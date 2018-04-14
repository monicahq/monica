@extends('layouts.skeleton')

@section('content')
  <div class="changelog">

    <section class="ph3 ph0-ns pv4 cf w-100 bg-gray-monica">
      <div class="mt4 mw7 center mb3">
        <h3>{{ trans('changelog.title') }}</h3>

        @if (\App\Helpers\LocaleHelper::getLocale() != 'en')
        <p>{{ trans('changelog.note') }}</p>
        @endif
      </div>

      @foreach ($changelogs as $changelog)
      <div class="mw7 center br3 ba b--gray-monica bg-white mb4 pa4">
        <div class="f7 mb2">
          {{ $changelog->created_at }}
          @if ($changelog->pivot->read == 0)
          <span class="f6 br-pill ph2 pv1 mb2 dib white bg-green">{{ trans('app.new') }}</span>
          @endif
        </div>
        <div>
          {!! $changelog->description !!}
        </div>
      </div>
      @endforeach
    </section>

  </div>
@endsection
