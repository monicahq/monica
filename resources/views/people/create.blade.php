@extends('layouts.skeleton')

@section('content')

<div class="mt4 mw7 center pa4 br3 ba b--gray-monica bg-white mb4">

  @if (session('status'))
  <div class="alert alert-success">
      {{ session('status') }}
  </div>
  @endif

  @include('partials.errors')

  <form action="/people" method="POST">
    {{ csrf_field() }}
    <div class="tc bb b--black-20 mb3">
      <h3 class="f3">{{ trans('people.people_add_title') }}</h3>

      @if (! auth()->user()->account->hasLimitations())
        <p class="import">{!! trans('people.people_add_import') !!}</p>
      @endif
    </div>

    {{-- This check is for the cultures that are used to say the last name first --}}
    @if (auth()->user()->name_order == 'firstname_first')

    <div class="mb4">
      <form-input
        v-bind:id="'first_name'"
        v-bind:title="'{{ trans('people.people_add_firstname') }}'">
      </form-input>
    </div>
    <div class="mb4">
      <form-input
        v-bind:id="'last_name'"
        v-bind:title="'{{ trans('people.people_add_lastname') }}'">
      </form-input>
    </div>

    @else

    <div class="mb4">
      <form-input
        v-bind:id="'last_name'"
        v-bind:title="'{{ trans('people.people_add_lastname') }}'">
      </form-input>
    </div>
    <div class="mb4">
      <form-input
        v-bind:id="'first_name'"
        v-bind:title="'{{ trans('people.people_add_firstname') }}'">
      </form-input>
    </div>

    @endif

    <div class="mb4">
      <label>{{ trans('people.people_add_gender') }}</label>
      @include('partials.components.gender-select', ['selectionID' => null ])
    </div>
    <button class="btn btn-primary" name="save" type="submit">{{ trans('people.people_add_cta') }}</button>
    <button class="btn btn-secondary" name="save_and_add_another" type="submit">{{ trans('people.people_save_and_add_another_cta') }}</button>
    <a href="/people" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
  </form>
</div>

@endsection
