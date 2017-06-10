@extends('layouts.skeleton')

@section('content')
  <div class="people-show debt">

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

    <!-- Page header -->
    @include('people._header')

    <!-- Page content -->
    <div class="main-content modal">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12 col-sm-6 col-sm-offset-3">
            <form method="POST" action="/people/{{ $contact->id }}/debt/store">
              {{ csrf_field() }}

              @include('partials.errors')

              <h2>{{ trans('people.debt_add_title') }}</h2>

              {{-- Gender --}}
              <fieldset class="form-group">
                <label class="form-check-inline">
                  <input type="radio" class="form-check-input" name="in-debt" id="youowe" value="yes" checked>
                  {{ trans('people.debt_add_you_owe', ['name' => $contact->getFirstName()]) }}
                </label>

                <label class="form-check-inline">
                  <input type="radio" class="form-check-input" name="in-debt" id="theyowe" value="no">
                  {{ trans('people.debt_add_they_owe', ['name' => $contact->getFirstName()]) }}
                </label>
              </fieldset>

              {{-- Amount --}}
              <div class="form-group">
                <label for="amount">{{ trans('people.debt_add_amount') }} ({{ Auth::user()->currency->symbol}})</label>
                <input type="number" class="form-control" name="amount" maxlength="254" autofocus required>
              </div>

              {{-- Amount --}}
              <div class="form-group">
                <label for="reason">{{ trans('people.debt_add_reason') }}</label>
                <input type="text" class="form-control" name="reason" maxlength="2500">
              </div>

              <div class="form-group actions">
                <button type="submit" class="btn btn-primary">{{ trans('people.debt_add_add_cta') }}</button>
                <a href="/people/{{ $contact->id }}" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
              </div> <!-- .form-group -->
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
@endsection
