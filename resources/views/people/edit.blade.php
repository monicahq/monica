@extends('layouts.skeleton')

@section('content')
  <section class="ph3 ph0-ns">

    {{-- Breadcrumb --}}
    <div class="mt4 mw7 center mb3">
      <p><a href="{{ url('/people/'.$contact->hashID()) }}">< {{ $contact->getCompleteName() }}</a></p>
      <h3 class="f3 fw5">{{ trans('people.information_edit_title', ['name' => $contact->first_name]) }}</h3>

      @if (! auth()->user()->account->hasLimitations())
      <p class="import">{!! trans('people.people_add_import', ['url' => '/settings/import']) !!}</p>
      @endif
    </div>

    <div class="mw7 center br3 ba b--gray-monica bg-white mb5">
      <form method="POST" action="/people/{{ $contact->id }}/update" enctype="multipart/form-data">
        {{ csrf_field() }}

        @include('partials.errors')

        {{-- Name --}}
        <div class="pa4-ns ph3 pv2 bb b--gray-monica">
          {{-- This check is for the cultures that are used to say the last name first --}}
          <div class="mb3 mb0-ns">
            @if (auth()->user()->name_order == 'firstname_first')

            <div class="dt dt--fixed">
              <div class="dtc pr2">
                <form-input
                  value="{{ $contact->first_name }}"
                  v-bind:input-type="'text'"
                  v-bind:id="'firstname'"
                  v-bind:required="true"
                  v-bind:title="'{{ trans('people.people_add_firstname') }}'">
                </form-input>
              </div>
              <div class="dtc">
                <form-input
                  value="{{ $contact->last_name }}"
                  v-bind:input-type="'text'"
                  v-bind:id="'lastname'"
                  v-bind:required="false"
                  v-bind:title="'{{ trans('people.people_add_lastname') }}'">
                </form-input>
              </div>
            </div>

            @else

            <div class="dt dt--fixed">
              <div class="dtc pr2">
                <form-input
                  value="{{ $contact->last_name }}"
                  v-bind:input-type="'text'"
                  v-bind:id="'lastname'"
                  v-bind:required="false"
                  v-bind:title="'{{ trans('people.people_add_lastname') }}'">
                </form-input>
              </div>
              <div class="dtc">
                <form-input
                  value="{{ $contact->first_name }}"
                  v-bind:input-type="'text'"
                  v-bind:id="'firstname'"
                  v-bind:required="true"
                  v-bind:title="'{{ trans('people.people_add_firstname') }}'">
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
              v-bind:required="true"
              v-bind:title="'{{ trans('people.people_add_gender') }}'"
              v-bind:id="'gender'">
            </form-select>
          </div>
        </div>

        {{-- Avatar --}}
        <div class="pa4-ns ph3 pv2 bb b--gray-monica">
          <div class="mb3 mb0-ns">
            <label for="avatar">{{ trans('people.information_edit_avatar') }}</label>
            <input type="file" class="form-control-file" name="avatar" id="avatar">
            <small id="fileHelp" class="form-text text-muted">{{ trans('people.information_edit_max_size', ['size' => 10]) }}</small>
          </div>
        </div>

        {{-- Birthdate --}}
        <form-specialdate
          v-bind:months="{{ $months }}"
          v-bind:days="{{ $days }}"
          v-bind:month="{{ $month }}"
          v-bind:day="{{ $day }}"
          v-bind:age="'{{ $age }}'"
          v-bind:default-date="'{{ $birthdate }}'"
          v-bind:locale="'{{ auth()->user()->locale }}'"
          v-bind:reminder={{ $hasBirthdayReminder }}
          :value="'{{ $birthdayState }}'"
        ></form-specialdate>

        {{-- Is the contact deceased? --}}
        <div class="pa4-ns ph3 pv2 bb b--gray-monica">
          <div class="mb3 mb0-ns">
            <div class="form-check">
              <label class="form-check-label">
                <input class="form-check-input" id="markPersonDeceased" name="markPersonDeceased" type="checkbox" value="markPersonDeceased"
                {{ $contact->is_dead ? 'checked' : '' }}>
                {{ trans('people.deceased_mark_person_deceased') }}
              </label>
            </div>
            <div class="form-check {{ $contact->is_dead ? '' : 'hidden' }}" id="datePersonDeceased">
            <label class="form-check-label">
              <input class="form-check-input" id="checkboxDatePersonDeceased" name="checkboxDatePersonDeceased" type="checkbox" value="checkboxDatePersonDeceased" {{ ($contact->deceasedDate != null) ? 'checked' : '' }}>
              {{ trans('people.deceased_know_date') }}

              @include('partials.components.date-select', ['contact' => $contact, 'specialDate' => $contact->deceasedDate, 'class' => 'deceased_date'])

            </div>
            <div class="form-check {{ $contact->deceasedDate == null ? 'hidden' : '' }}" id="reminderDeceased">
              <label class="form-check-label">
                <input class="form-check-input" id="addReminderDeceased" name="addReminderDeceased" type="checkbox" value="addReminderDeceased" {{ ($contact->deceasedDate != null) ? (($contact->deceasedDate->reminder_id != null) ? 'checked' : '') : '' }}>
                {{ trans('people.deceased_add_reminder') }}
              </label>
            </div>
          </div>
        </div>

        {{-- Form actions --}}
        <div class="ph4-ns ph3 pv3 bb b--gray-monica">
          <div class="flex-ns justify-between">
            <div class="">
              <a href="{{ url('/people/'.$contact->hashID()) }}" class="btn btn-secondary w-auto-ns w-100 mb2 pb0-ns">{{ trans('app.cancel') }}</a>
            </div>
            <div class="">
              <button class="btn btn-primary w-auto-ns w-100 mb2 pb0-ns" name="save" type="submit">{{ trans('app.save') }}</button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <form method="POST" action="{{ action('ContactsController@delete', $contact) }}" id="contact-delete-form" class="hidden">
    {{ method_field('DELETE') }}
    {{ csrf_field() }}
  </form>
@endsection
