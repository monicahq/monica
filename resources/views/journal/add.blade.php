  @extends('layouts.skeleton')

  @section('content')
  <div class="people-show journal">

    {{-- Breadcrumb --}}
    <section class="ph3 ph5-ns pv3 w-100 f6 bb b--gray-monica mb3">
      <div class="mw9 center pa2">
        <ul>
          <li class="di">
            <a href="{{ route('dashboard.index') }}">{{ trans('app.breadcrumb_dashboard') }}</a>
          </li>
          <li class="di">
            &gt; <a href="{{ route('journal.index') }}">{{ trans('app.breadcrumb_journal') }}</a>
          </li>
          <li class="di">
            &gt; {{ trans('journal.journal_add') }}
          </li>
        </ul>
      </div>
    </section>

    <!-- Page content -->
    <create-journal />

  </div>
  @endsection
