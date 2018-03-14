@extends('layouts.skeleton')

@section('content')

<section class="ph3 ph0-ns">

  {{-- Breadcrumb --}}
  <div class="mt4 mw7 center mb3">
    <p><a href="{{ url('/people/'.$contact->id) }}">< {{ $contact->getCompleteName() }}</a></p>
    <div class="mt4 mw7 center mb3">
      <h3 class="f3 fw5">Add a new relationship</h3>
    </div>
  </div>

  <div class="mw7 center br3 ba b--gray-monica bg-white mb6">

    @if (session('status'))
    <div class="alert alert-success">
        {{ session('status') }}
    </div>
    @endif

    @include('partials.errors')

    <form action="/people/{{ $contact->id }}/relationships/store" method="POST">
      {{ csrf_field() }}

      <div class="pa4-ns ph3 pv2 mb3 mb0-ns bb b--gray-monica">
        <form-select
          :options="{{ $relationshipTypes }}"
          v-bind:required="true"
          v-bind:title="'This person is {{ $contact->getCompleteName() }}\'s...'"
          v-bind:id="'relationship_type_id'">
        </form-select>
      </div>

      {{-- New contact / link existing --}}
      <div class="pa4-ns ph3 pv2 mb3 mb0-ns bb b--gray-monica">
        <p class="mb2 b">Who's the relationship with?</p>
        <div class="dt dt--fixed">
          <div class="dtc pr2">
            <input type="radio" id="new" name="relationship_type" value="new" @click="global_relationship_form_new_contact = true" checked>
            <label for="new" class="pointer">Create a new contact</label>
          </div>
          <div class="dtc">
            <input type="radio" id="existing" name="relationship_type" value="existing" @click="global_relationship_form_new_contact = false">
            <label for="existing" class="pointer">An existing contact</label>
          </div>
        </div>
      </div>

      <div v-if="global_relationship_form_new_contact">
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
                  v-bind:id="'first_name'"
                  v-bind:required="true"
                  v-bind:title="'{{ trans('people.people_add_firstname') }}'">
                </form-input>
              </div>
              <div class="dtc">
                <form-input
                  value="{{ $contact->last_name }}"
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
                  value="{{ $contact->last_name }}"
                  v-bind:input-type="'text'"
                  v-bind:id="'lastname'"
                  v-bind:required="false"
                  v-bind:title="'{{ trans('people.people_add_lastname') }}'">
                </form-input>
              </div>
              <div class="dtc">
                <form-input
                  value="{{ $contact->first_naem }}"
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
        <div class="pa4-ns ph3 pv2 mb3 mb0-ns bb b--gray-monica">
          <form-select
            :options="{{ $genders }}"
            v-bind:required="true"
            v-bind:title="'{{ trans('people.people_add_gender') }}'"
            v-bind:id="'gender_id'">
          </form-select>
        </div>

        {{-- Birthdate --}}
        <div class="pa4-ns ph3 pv2 bb b--gray-monica">
          <div class="mb3 mb0-ns">
            <form-specialdate
              v-bind:months="{{ $months }}"
              v-bind:days="{{ $days }}"
              v-bind:month="{{ $month }}"
              v-bind:day="{{ $day }}"
              v-bind:age="'{{ $age }}'"
              v-bind:default-date="'{{ $birthdate }}'"
              v-bind:locale="'{{ auth()->user()->locale }}'"
              :value="'{{ $birthdayState }}'"
            ></form-specialdate>
          </div>
        </div>
      </div>

      <div v-if="!global_relationship_form_new_contact">
        <div class="pa4-ns ph3 pv2 mb3 mb0-ns bb b--gray-monica">
          <form-select
            :options="{{ $existingContacts }}"
            v-bind:required="true"
            v-bind:title="'Select an existing contact from the dropdown below'"
            v-bind:id="'existing_contact_id'">
          </form-select>
        </div>
      </div>

      {{-- Form actions --}}
      <div class="ph4-ns ph3 pv3 bb b--gray-monica">
        <div class="flex-ns justify-between">
          <div class="">
            <a href="/people" class="btn btn-secondary w-auto-ns w-100 mb2 pb0-ns">{{ trans('app.cancel') }}</a>
          </div>
          <div class="">
            <button class="btn btn-primary w-auto-ns w-100 mb2 pb0-ns" name="save" type="submit">{{ trans('people.people_add_cta') }}</button>
          </div>
        </div>
      </div>

    </form>
  </div>
</section>

@endsection
