@extends('layouts.skeleton')

@section('content')

<section class="ph3 ph0-ns">

  {{-- Breadcrumb --}}
  <div class="mt4 mw7 center mb3">
    <p><a href="{{ route('people.show', $contact) }}">< {{ $contact->name }}</a></p>
    <div class="mt4 mw7 center mb3">
      <h3 class="f3 fw5">{{ trans('people.relationship_form_edit') }}</h3>
    </div>
  </div>

  <div class="mw7 center br3 ba b--gray-monica bg-white mb6">

    @if (session('status'))
    <div class="alert alert-success">
        {{ session('status') }}
    </div>
    @endif

    @include('partials.errors')

    <form action="{{ route('people.relationships.update', [$contact, $partner]) }}" method="POST">
      {{ csrf_field() }}
      <input type="hidden" name="type" value="{{ $type }}">

      {{-- Nature of relationship --}}
      <div class="pa4-ns ph3 pv2 mb3 mb0-ns bb b--gray-monica">
        <form-select
          :options="{{ $relationshipTypes }}"
          value="{{ $type }}"
          v-bind:required="true"
          v-bind:title="'{{ trans('people.relationship_form_is_with', ['name' => $contact->name]) }}'"
          v-bind:id="'relationship_type_id'">
        </form-select>
      </div>

      {{-- Name --}}
      <div class="pa4-ns ph3 pv2 bb b--gray-monica">
        {{-- This check is for the cultures that are used to say the last name first --}}
        <div class="mb3 mb0-ns">
          @if (auth()->user()->getNameOrderForForms() == 'firstname')

          <div class="dt dt--fixed">
            <div class="dtc pr2">
              <form-input
                value="{{ $partner->first_name }}"
                v-bind:input-type="'text'"
                v-bind:id="'first_name'"
                v-bind:required="true"
                v-bind:title="'{{ trans('people.people_add_firstname') }}'">
              </form-input>
            </div>
            <div class="dtc">
              <form-input
                value="{{ $partner->last_name }}"
                v-bind:input-type="'text'"
                v-bind:id="'last_name'"
                v-bind:required="false"
                v-bind:title="'{{ trans('people.people_add_lastname') }}'">
              </form-input>
            </div>
          </div>

          @else

          <div class="dt dt--fixed">
            <div class="dtc pr2">
              <form-input
                value="{{ $partner->last_name }}"
                v-bind:input-type="'text'"
                v-bind:id="'last_name'"
                v-bind:required="false"
                v-bind:title="'{{ trans('people.people_add_lastname') }}'">
              </form-input>
            </div>
            <div class="dtc">
              <form-input
                value="{{ $partner->first_name }}"
                v-bind:input-type="'text'"
                v-bind:id="'first_name'"
                v-bind:required="true"
                v-bind:title="'{{ trans('people.people_add_firstname') }}'">
              </form-input>
            </div>
          </div>

          @endif
        </div>
      </div>

      {{-- Gender --}}
      <div class="pa4-ns ph3 pv2 mb3 mb0-ns bb b--gray-monica">
        <form-select
          :options="{{ $genders }}"
          v-bind:required="true"
          value="{{ $partner->gender->id }}"
          v-bind:title="'{{ trans('people.people_add_gender') }}'"
          v-bind:id="'gender_id'">
        </form-select>
      </div>

      {{-- Birthdate --}}
      <form-specialdate
        v-bind:months="{{ $months }}"
        v-bind:days="{{ $days }}"
        v-bind:locale="'{{ auth()->user()->locale }}'"
        v-bind:month="{{ $month }}"
        v-bind:day="{{ $day }}"
        v-bind:age="'{{ $age }}'"
        v-bind:default-date="'{{ $birthdate }}'"
        v-bind:reminder={{ $hasBirthdayReminder }}
        :value="'{{ $birthdayState }}'"
      ></form-specialdate>

      <div class="pa4-ns ph3 pv2 bb b--gray-monica">
        <div class="mb3 mb0-ns">
          <label class="pa0 ma0 lh-copy pointer" for="realContact">
            <input type="checkbox" id="realContact" name="realContact"> {{ trans('people.relationship_form_also_create_contact') }} <span class="silver">{{ trans('people.relationship_form_add_description') }}</span>
          </label>
        </div>
      </div>

      {{-- Form actions --}}
      <div class="ph4-ns ph3 pv3 bb b--gray-monica">
        <div class="flex-ns justify-between">
          <div>
            <a href="{{ route ('people.show', $contact) }}" class="btn btn-secondary w-auto-ns w-100 mb2 pb0-ns">{{ trans('app.cancel') }}</a>
          </div>
          <div>
            <button class="btn btn-primary w-auto-ns w-100 mb2 pb0-ns" v-if="global_relationship_form_new_contact" name="save" type="submit">{{ trans('app.save') }}</button>
          </div>
        </div>
      </div>

    </form>
  </div>
</section>

@endsection
