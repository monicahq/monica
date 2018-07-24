@extends('layouts.skeleton')

@section('content')

  <div class="journal">

    {{-- Breadcrumb --}}
    <section class="ph3 ph5-ns pv3 w-100 f6 bb b--gray-monica">
      <div class="mw9 center pa2">
        <ul>
          <li class="di">
            <a href="{{ route('dashboard.index') }}">{{ trans('app.breadcrumb_dashboard') }}</a>
          </li>
          <li class="di">
            &gt; {{ trans('app.breadcrumb_journal') }}
          </li>
        </ul>
      </div>
    </section>

    {{-- Main section --}}
    <section class="ph3 ph5-ns pv3 cf w-100 bg-gray-monica">
      <journal-list></journal-list>
    </section>

  </div>
@endsection
