@extends('layouts.skeleton')

@section('content')
  <div class="people-show">
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
                {{ $contact->getCompleteName() }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    {{-- Page header --}}
    @include('people._header')

    {{-- Page content --}}
    <div class="main-content dashboard">

      <div class="{{ Auth::user()->getFluidLayout() }}">

        <div class="row">

          <div class="col-xs-12 col-sm-3">

            {{-- Section address, email, phone, contact --}}
            @include('people.dashboard.people-information.index')

          </div>

          <div class="col-xs-12 col-sm-9">

            {{-- Significant Other --}}
            @include('people.dashboard.significantother.index')

            {{-- Kids --}}
            @include('people.dashboard.kids.index')

            {{-- Food preferences --}}
            @include('people.dashboard.food-preferencies.index')

            {{-- Notes --}}
            @include('people.dashboard.notes.index')

          </div>

        </div>
      </div>

    </div>
  </div>
@endsection

