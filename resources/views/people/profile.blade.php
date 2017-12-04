@extends('layouts.skeleton')

@section('title', $contact->getCompleteName(auth()->user()->name_order) )

@section('content')
  <div class="people-show" data-contact-id="{{ $contact->id }}">
    {{ csrf_field() }}

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
                <a href="/people">{{ trans('app.breadcrumb_list_contacts') }}</a>
              </li>
              <li>
                {{ $contact->getCompleteName(auth()->user()->name_order) }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    {{-- Page header --}}
    @include('people._header')

    {{-- Page content --}}
    <div class="main-content profile">

      <div class="{{ Auth::user()->getFluidLayout() }}">

        <div class="row">
          <div class="col-xs-12 col-sm-3 profile-sidebar">
            @include('people.dashboard.index')
          </div>

          <div class="col-xs-12 col-sm-9">
            <div class="row section notes">
              <div class="col-xs-12 section-title">
                <contact-note v-bind:contact-id="{!! $contact->id !!}"></contact-note>
              </div>
            </div>

            <div class="row section calls">
              @include('people.calls.index')
            </div>

            <div class="row section activities">
              @include('activities.index')
            </div>

            <div class="row section reminders">
              @include('people.reminders.index')
            </div>

            <div class="row section tasks">
              @include('people.tasks.index')
            </div>

            <div class="row section gifts">
              @include('people.gifts.index')
            </div>

            <div class="row section debts">
              @include('people.debt.index')
            </div>
          </div>
        </div>

      </div>

    </div>
  </div>

  @include('people.modal.log_call')

@endsection
