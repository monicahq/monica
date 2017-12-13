@extends('layouts.skeleton')

@section('content')

  {{-- Breadcrumb --}}
  <section class="ph3 ph5-ns pv3 w-100 f6">
    <ul>
      <li class="di">
        <a href="/dashboard">{{ trans('app.breadcrumb_dashboard') }}</a>
      </li>
      <li class="di">
        > <a href="/journal">{{ trans('app.breadcrumb_journal') }}</a>
      </li>
      <li class="di">
        > {{ trans('journal.journal_add') }}
      </li>
    </ul>
  </section>

  <add-entry></add-entry>

@endsection
