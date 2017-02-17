@extends('layouts.skeleton')

@section('content')
  <div class="people-show kids">

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
            <form method="POST" action="/people/{{ $contact->id }}/kid/store">
              {{ csrf_field() }}

              @include('partials.errors')

              <h2>{{ trans('people.kids_add_title', ['name' => $contact->getLastName()]) }}</h2>

              {{-- Gender --}}
              <fieldset class="form-group">
                <label class="form-check-inline">
                  <input type="radio" class="form-check-input" name="gender" id="genderMale" value="male" checked>
                  {{ trans('people.kids_add_boy') }}
                </label>

                <label class="form-check-inline">
                  <input type="radio" class="form-check-input" name="gender" id="genderFemale" value="female">
                  {{ trans('people.kids_add_girl') }}
                </label>
              </fieldset>

              {{-- First name --}}
              <div class="form-group">
                <label for="firstname">{{ trans('people.kids_add_firstname') }}</label>
                <input type="text" class="form-control" name="firstname" autofocus required>

                @if (! is_null($contact->getLastName()))
                <small id="firstNameHelp" class="form-text text-muted">{{ trans('people.kids_add_firstname_help', ['name' => $contact->getLastName()]) }}.</small>
                @endif
              </div>

              <fieldset class="form-group dates">

                {{-- Approximate birthdate --}}
                <div class="form-check">
                  <label class="form-check-label">
                    <input type="radio" class="form-check-input" name="birthdateApproximate" value="birthdate_approximate"
                              v-model="birthdate_approximate"
                              v-bind:value="true"
                              :checked="true">

                    <div class="form-inline">
                      {{ trans('people.kids_add_probably') }}

                      <input type="number" class="form-control" name="age"
                              value="1"
                              min="1"
                              max="99"
                              :disabled="birthdate_approximate == false">

                      {{ trans('people.kids_add_probably_yo') }}
                    </div>
                  </label>
                </div>

                {{-- Exact birthdate --}}
                <div class="form-check">
                  <label class="form-check-label">
                      <input type="radio" class="form-check-input" name="birthdateApproximate" value="birthdate_exact"
                              v-model="birthdate_approximate"
                              v-bind:value="false"
                              :checked="false">

                      <div class="form-inline">
                        {{ trans('people.kids_add_exact') }}
                        <input type="date" name="specificDate" class="form-control"
                              value="{{ \Carbon\Carbon::now(Auth::user()->timezone)->format('Y-m-d') }}"
                              min="{{ \Carbon\Carbon::now(Auth::user()->timezone)->subYears(50)->format('Y-m-d') }}"
                              max="{{ \Carbon\Carbon::now(Auth::user()->timezone)->format('Y-m-d') }}"
                              :disabled="birthdate_approximate == true">
                      </div>
                  </label>
                </div>
              </fieldset>

              <div class="classname" v-show="birthdate_approximate == false">
                <p>{{ trans('people.kids_add_help') }}</p>
              </div>

              <div class="form-group actions">
                <button type="submit" class="btn btn-primary">{{ trans('people.kids_add_cta') }}</button>
                <a href="/people/{{ $contact->id }}" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
              </div> <!-- .form-group -->
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
@endsection
