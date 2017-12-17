@extends('layouts.skeleton')

@section('content')

  <div class="journal">

    {{-- Breadcrumb --}}
    <section class="ph3 ph5-ns pv3 w-100 f6 bb b--gray-monica">
      <div class="mw9 center pa2">
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
      </div>
    </section>

    {{-- Main section --}}
    <section class="ph3 ph5-ns pv3 cf w-100 bg-gray-monica">
      <journal-list></journal-list>
    </section>

    {{-- Breadcrumb --}}
    <div class="breadcrumb">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12">
            <ul class="horizontal">
              <li>
                <a href="/dashboard">{{ trans('app.breadcrumb_dashboard') }}</a>
              </li>
              <li>
                {{ trans('app.breadcrumb_journal') }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="main-content">

      @foreach ($journalEntries as $journalEntry)

      {{-- @include($journalEntry->getLayout(), ['object' => $journalEntry->getObjectData() ]) --}}

      @endforeach
    </div>
  </div>
@endsection
