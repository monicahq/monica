@extends('layouts.skeleton')

@section('content')

<section class="ph3 ph0-ns">
  <div class="mt4 mw7 center mb3">
    @if ($isContactMissing)
      <h2 class="f2 fw5">{{ trans('people.people_add_missing') }}</h2>
    @else
      <h2 class="f3 fw5">{{ trans('people.people_add_title') }}</h2>
    @endif

    @if (! $accountHasLimitations)
      <p class="import">{!! trans('people.people_add_import', ['url' => route('settings.import')]) !!}</p>
    @endif
  </div>

  <div class="mw7 center br3 ba b--gray-monica bg-white mb6">

    @if (session('status'))
    <div class="alert alert-success">
        {{ session('status') }}
    </div>
    @endif

    @include('partials.errors')

    <form action="{{ route('people.store') }}" method="POST">
      @csrf

      <div class="pa4-ns ph3 pv2 bb b--gray-monica">
        {{-- This check is for the cultures that are used to say the last name first --}}
        @if ($formNameOrder == 'firstname')

        <div class="mb3">
          <form-input
            :id="'first_name'"
            :input-type="'text'"
            :required="true"
            :title="'{{ trans('people.people_add_firstname') }}'"
            :value="'{{ $firstName }}'">
          </form-input>
        </div>

        <div class="mb3">
          <form-input
            :id="'middle_name'"
            :input-type="'text'"
            :required="false"
            :title="'{{ trans('people.people_add_middlename') }}'">
          </form-input>
        </div>

        <div class="mb3">
          <form-input
            :id="'last_name'"
            :input-type="'text'"
            :required="false"
            :title="'{{ trans('people.people_add_lastname') }}'"
            :value="'{{ $lastName }}'">
          </form-input>
        </div>

        <div class="mb3 mb0-ns">
          <form-input
            :id="'nickname'"
            :input-type="'text'"
            :required="false"
            :title="'{{ trans('people.people_add_nickname') }}'">
          </form-input>
        </div>

        @else

        <div class="mb3">
          <form-input
            :id="'last_name'"
            :input-type="'text'"
            :required="false"
            :title="'{{ trans('people.people_add_lastname') }}'"
            :value="'{{ $lastName }}'">
          </form-input>
        </div>

        <div class="mb3">
          <form-input
            :id="'first_name'"
            :input-type="'text'"
            :required="true"
            :title="'{{ trans('people.people_add_firstname') }}'"
            :value="'{{ $firstName }}'">
          </form-input>
        </div>

        <div class="mb3">
          <form-input
            :id="'middle_name'"
            :input-type="'text'"
            :required="false"
            :title="'{{ trans('people.people_add_middlename') }}'">
          </form-input>
        </div>

        <div class="mb3 mb0-ns">
          <form-input
            :id="'nickname'"
            :input-type="'text'"
            :required="false"
            :title="'{{ trans('people.people_add_nickname') }}'">
          </form-input>
        </div>

        @endif
      </div>

      <div class="pa4-ns ph3 pv2 mb3 mb0-ns bb b--gray-monica">
        <form-select
          :options="{{ $genders }}"
          :required="false"
          :title="'{{ trans('people.people_add_gender') }}'"
          :id="'gender'"
          :value="'{{ $defaultGender }}'">
        </form-select>
      </div>

      {{-- Form actions --}}
      <div class="ph4-ns ph3 pv3 bb b--gray-monica">
        <div class="flex-ns justify-between">
          <div>
            <a href="{{ route('people.index') }}" class="btn btn-secondary w-auto-ns w-100 mb2 pb0-ns">{{ trans('app.cancel') }}</a>
          </div>
          <div>
            <button class="btn btn-secondary w-auto-ns w-100 mb2 pb0-ns" name="save_and_add_another" type="submit">{{ trans('people.people_save_and_add_another_cta') }}</button>
            <button class="btn btn-primary w-auto-ns w-100 mb2 pb0-ns" name="save" value="true" type="submit">{{ trans('people.people_add_cta') }}</button>
          </div>
        </div>
      </div>

    </form>
  </div>
</section>

@endsection
