@extends('layouts.skeleton')

@section('content')
  <section class="ph3 ph0-ns">

    {{-- Breadcrumb --}}
    <div class="mt4 mw7 center mb3">
      <p><a href="{{ route('people.show', $contact) }}">< {{ $contact->name }}</a></p>
      <h3 class="f3 fw5">{{ trans('people.information_edit_title', ['name' => $contact->first_name]) }}</h3>

      @if (! $accountHasLimitations)
      <p class="import">{!! trans('people.people_add_import', ['url' => 'settings/import']) !!}</p>
      @endif
    </div>

    <div class="mw7 center br3 ba b--gray-monica bg-white mb5">
      <form method="POST" action="{{ route('people.update', $contact) }}" enctype="multipart/form-data">
        @method('PUT')
        @csrf

        @include('partials.errors')

        {{-- Name --}}
        <div class="pa4-ns ph3 pv2 bb b--gray-monica">
          {{-- This check is for the cultures that are used to say the last name first --}}
          <div class="mb3 mb0-ns">
            @if ($formNameOrder == 'firstname')

            <div class="dt-ns dt--fixed di">
              <div class="dtc-ns pr2-ns pb0-ns w-100 pb3">
                <form-input
                  value="{{ $contact->first_name }}"
                  :input-type="'text'"
                  :id="'firstname'"
                  :required="true"
                  :title="'{{ trans('people.people_add_firstname') }}'">
                </form-input>
              </div>
              <div class="dtc-ns pr2-ns pb0-ns w-100 pb3">
                <form-input
                  value="{{ $contact->middle_name }}"
                  :input-type="'text'"
                  :id="'middlename'"
                  :required="false"
                  :title="'{{ trans('people.people_add_middlename') }}'">
                </form-input>
              </div>
              <div class="dtc-ns pr2-ns pb0-ns w-100 pb3">
                <form-input
                  value="{{ $contact->last_name }}"
                  :input-type="'text'"
                  :id="'lastname'"
                  :required="false"
                  :title="'{{ trans('people.people_add_lastname') }}'">
                </form-input>
              </div>
              <div class="dtc-ns pb0-ns w-100">
                <form-input
                  value="{{ $contact->nickname }}"
                  :input-type="'text'"
                  :id="'nickname'"
                  :required="false"
                  :title="'{{ trans('people.people_add_nickname') }}'">
                </form-input>
              </div>
            </div>

            @else

            <div class="dt-ns dt--fixed di">
              <div class="dtc-ns pr2-ns pb0-ns w-100 pb3">
                <form-input
                  value="{{ $contact->last_name }}"
                  :input-type="'text'"
                  :id="'lastname'"
                  :required="false"
                  :title="'{{ trans('people.people_add_lastname') }}'">
                </form-input>
              </div>
              <div class="dtc-ns pr2-ns pb0-ns w-100 pb3">
                <form-input
                  value="{{ $contact->first_name }}"
                  :input-type="'text'"
                  :id="'firstname'"
                  :required="true"
                  :title="'{{ trans('people.people_add_firstname') }}'">
                </form-input>
              </div>
              <div class="dtc-ns pb0-ns w-100">
                <form-input
                  value="{{ $contact->nickname }}"
                  :input-type="'text'"
                  :id="'nickname'"
                  :required="false"
                  :title="'{{ trans('people.people_add_nickname') }}'">
                </form-input>
              </div>
            </div>

            @endif
          </div>
        </div>

        {{-- Gender --}}
        <div class="pa4-ns ph3 pv2 bb b--gray-monica">
          <div class="mb3 mb0-ns">
            <form-select
              :options="{{ $genders }}"
              value="{{ $contact->gender_id }}"
              :required="false"
              :title="'{{ trans('people.people_add_gender') }}'"
              :id="'gender'">
            </form-select>
          </div>
        </div>

        {{-- Description --}}
        <div class="pa4-ns ph3 pv2 bb b--gray-monica">
          <div class="mb3 mb0-ns">
            <form-input
              value="{{ $contact->description }}"
              :input-type="'text'"
              :id="'description'"
              :required="false"
              :title="'{{ trans('people.information_edit_description') }}'">
            </form-input>
            <small id="emailHelp" class="form-text text-muted">{{ trans('people.information_edit_description_help') }}</small>
          </div>
        </div>

        {{-- Birthdate --}}
        <form-specialdate
          :months="{{ $months }}"
          :days="{{ $days }}"
          :month="{{ $month }}"
          :day="{{ $day }}"
          :age="{{ $age ?: 0 }}"
          :birthdate="'{{ $birthdate }}'"
          :reminder="{{ \Safe\json_encode($hasBirthdayReminder) }}"
          :value="'{{ $birthdayState }}'"
        ></form-specialdate>

        {{-- Is the contact deceased? --}}
        <form-specialdeceased
          :value="{{ \Safe\json_encode($contact->is_dead) }}"
          :date="'{{ $deceaseddate }}'"
          :reminder="{{ \Safe\json_encode($hasDeceasedReminder) }}"
        >
        </form-specialdeceased>

        {{-- Form actions --}}
        <div class="ph4-ns ph3 pv3 bb b--gray-monica">
          <div class="flex-ns justify-between">
            <div>
                <a href="{{ route('people.show', $contact) }}" class="btn btn-secondary w-auto-ns w-100 mb2 pb0-ns" style="text-align: center;">{{ trans('app.cancel') }}</a>
            </div>
            <div>
              <button class="btn btn-primary w-auto-ns w-100 mb2 pb0-ns" name="save" type="submit">{{ trans('app.save') }}</button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection
