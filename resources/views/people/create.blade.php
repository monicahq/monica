@extends('layouts.skeleton')

@section('content')

<div class="mt4 mw7 center mb3">
  <h3 class="f3 fw5">{{ trans('people.people_add_title') }}</h3>

  @if (! auth()->user()->account->hasLimitations())
    <p class="import">{!! trans('people.people_add_import') !!}</p>
  @endif
</div>
<div class="mw7 center pa4 br3 ba b--gray-monica bg-white mb6">

  @if (session('status'))
  <div class="alert alert-success">
      {{ session('status') }}
  </div>
  @endif

  @include('partials.errors')

  <form action="/people" method="POST">
    {{ csrf_field() }}
    {{-- This check is for the cultures that are used to say the last name first --}}
    @if (auth()->user()->name_order == 'firstname_first')

    <div class="mb4">
      <form-input
        v-bind:id="'first_name'"
        v-bind:required="true"
        v-bind:title="'{{ trans('people.people_add_firstname') }}'">
      </form-input>
    </div>
    <div class="mb4">
      <form-input
        v-bind:id="'last_name'"
        v-bind:required="false"
        v-bind:title="'{{ trans('people.people_add_lastname') }}'">
      </form-input>
    </div>

    @else

    <div class="mb4">
      <form-input
        v-bind:id="'last_name'"
        v-bind:required="false"
        v-bind:title="'{{ trans('people.people_add_lastname') }}'">
      </form-input>
    </div>
    <div class="mb4">
      <form-input
        v-bind:id="'first_name'"
        v-bind:required="true"
        v-bind:title="'{{ trans('people.people_add_firstname') }}'">
      </form-input>
    </div>

    @endif

    <div class="mb4">
      <form-select
        :options="{{ $genders }}"
        v-bind:required="true"
        v-bind:title="'{{ trans('people.people_add_gender') }}'"
        v-bind:id="'gender'">
      </form-select>
    </div>
    <button class="btn btn-primary" name="save" type="submit">{{ trans('people.people_add_cta') }}</button>
    <button class="btn btn-secondary" name="save_and_add_another" type="submit">{{ trans('people.people_save_and_add_another_cta') }}</button>
    <a href="/people" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
  </form>
</div>

@endsection
