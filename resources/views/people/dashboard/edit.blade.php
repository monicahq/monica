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
            <form method="POST" action="/people/{{ $contact->id }}/update" enctype="multipart/form-data">
              {{ csrf_field() }}

              @include('partials.errors')

              <h2>{{ trans('people.information_edit_title', ['name' => $contact->getFirstName()]) }}</h2>

              {{-- Gender --}}
              <fieldset class="form-group">
                <label class="form-check-inline">
                  @if ($contact->gender == 'male')
                  <input type="radio" class="form-check-input" name="gender" id="genderMale" value="male" checked>
                  @else
                  <input type="radio" class="form-check-input" name="gender" id="genderMale" value="male">
                  @endif
                  {{ trans('people.information_edit_male') }}
                </label>

                <label class="form-check-inline">
                  @if ($contact->gender == 'female')
                  <input type="radio" class="form-check-input" name="gender" id="genderFemale" value="female" checked>
                  @else
                  <input type="radio" class="form-check-input" name="gender" id="genderFemale" value="female">
                  @endif
                  {{ trans('people.information_edit_female') }}
                </label>
              </fieldset>

              {{-- Avatar --}}
              <div class="form-group">
                <label for="avatar">Photo/avatar of the contact</label>
                <input type="file" class="form-control-file" name="avatar">
                <small id="fileHelp" class="form-text text-muted">Max 10Mb.</small>
              </div>

              {{-- First name --}}
              <div class="form-group">
                <label for="firstname">{{ trans('people.information_edit_firstname') }}</label>
                <input type="text" class="form-control" name="firstname" value="{{ $contact->getFirstName() }}" autofocus required>
              </div>

              {{-- Last name --}}
              <div class="form-group">
                <label for="firstname">{{ trans('people.information_edit_lastname') }}</label>
                <input type="text" class="form-control" name="lastname" value="{{ $contact->getLastName() }}">
              </div>

              {{-- Address --}}
              <div class="form-group">
                <label for="street">{{ trans('people.information_edit_street') }}</label>
                <input type="text" class="form-control" name="street" value="{{ $contact->getStreet() }}" autofocus>
                <label for="province">{{ trans('people.information_edit_province') }}</label>
                <input type="text" class="form-control" name="province" value="{{ $contact->getProvince() }}">
                <label for="postalcode">{{ trans('people.information_edit_postalcode') }}</label>
                <input type="text" class="form-control" name="postalcode" value="{{ $contact->getPostalCode() }}">
                <label for="city">{{ trans('people.information_edit_city') }}</label>
                <input type="text" class="form-control" name="city" value="{{ $contact->getCity() }}">
                <label for="country">{{ trans('people.information_edit_country') }}</label>
                <select name="country" class="form-control" required>
                  @foreach (App\Country::all() as $country)
                    @if ($country->id == $contact->getCountryID())
                    <option value="{{ $country->id }}" selected>{{ $country->country }}</option>
                    @else
                    <option value="{{ $country->id }}" >{{ $country->country }}</option>
                    @endif
                  @endforeach
                </select>
              </div>

              {{-- Email address --}}
              <div class="form-group">
                <label for="email">{{ trans('people.information_edit_email') }}</label>
                <input type="email" class="form-control" name="email" value="{{ $contact->getEmail() }}">
              </div>

              {{-- Email address --}}
              <div class="form-group">
                <label for="phone">{{ trans('people.information_edit_phone') }}</label>
                <input type="number" class="form-control" name="phone" value="{{ $contact->getPhone() }}">
              </div>

              <fieldset class="form-group dates">

                {{-- Don't know the birthdate --}}
                <div class="form-check">
                  <label class="form-check-label">
                    <input type="radio" class="form-check-input" name="birthdateApproximate" value="unknown" {{ ($contact->is_birthdate_approximate == 'unknown')?'checked':'' }}>

                    <div class="form-inline">
                      {{ trans('people.significant_other_add_unknown') }}
                    </div>
                  </label>
                </div>

                {{-- Approximate birthdate --}}
                <div class="form-check">
                  <label class="form-check-label">
                    <input type="radio" class="form-check-input" name="birthdateApproximate" value="approximate" {{ ($contact->is_birthdate_approximate == 'approximate')?'checked':'' }}>

                    <div class="form-inline">
                      {{ trans('people.information_edit_probably') }}

                      <input type="number" class="form-control" name="age"
                              value="{{ (is_null($contact->getAge())) ? 1 : $contact->getAge() }}"
                              min="0"
                              max="99">

                      {{ trans('people.information_edit_probably_yo') }}
                    </div>
                  </label>
                </div>

                {{-- Exact birthdate --}}
                <div class="form-check">
                  <label class="form-check-label">
                      <input type="radio" class="form-check-input" name="birthdateApproximate" value="exact" {{ ($contact->is_birthdate_approximate == 'exact')?'checked':'' }}>

                      <div class="form-inline">
                        {{ trans('people.information_edit_exact') }}

                        <input type="date" name="specificDate" class="form-control"
                              value="{{ (is_null($contact->getBirthdate())) ? \Carbon\Carbon::now(Auth::user()->timezone)->format('Y-m-d') : $contact->getBirthdate()->format('Y-m-d') }}"
                              min="{{ \Carbon\Carbon::now(Auth::user()->timezone)->subYears(120)->format('Y-m-d') }}"
                              max="{{ \Carbon\Carbon::now(Auth::user()->timezone)->format('Y-m-d') }}">
                      </div>
                  </label>
                </div>
              </fieldset>

              <div class="classname">
                <p>{{ trans('people.information_edit_help') }}</p>
              </div>

              <div class="form-group actions">
                <button type="submit" class="btn btn-primary">{{ trans('app.save') }}</button>
                <a href="/people/{{ $contact->id }}" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
              </div> <!-- .form-group -->
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
@endsection
