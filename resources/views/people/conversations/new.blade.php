@extends('layouts.skeleton')

@section('content')

<section class="ph3 ph0-ns">

  {{-- Breadcrumb --}}
  <div class="mt4 mw7 center mb3">
    <p><a href="{{ route('people.show', $contact) }}">< {{ $contact->name }}</a></p>
    <div class="mt4 mw7 center mb3">
      <h3 class="f3 fw5">Record a new conversation</h3>
    </div>
  </div>

  <div class="mw7 center br3 ba b--gray-monica bg-white mb6">

    @if (session('status'))
    <div class="alert alert-success">
        {{ session('status') }}
    </div>
    @endif

    @include('partials.errors')

    <form action="{{ route('people.relationships.store', $contact) }}" method="POST">
      {{ csrf_field() }}

      {{-- When did it take place --}}
      <div class="pa4-ns ph3 pv2 mb3 mb0-ns bb b--gray-monica">
        <p class="mb2 b">When did you have this conversation?</p>
        <div class="dt dt--fixed">
          <div class="dtc pr2">
            <input type="radio" id="new" name="relationship_type" value="new" checked>
            <label for="new" class="pointer">Today</label>
          </div>
          <div class="dtc pr2">
            <input type="radio" id="existing" name="relationship_type" value="existing">
            <label for="existing" class="pointer">Yesterday</label>
          </div>
          <div class="dtc">
            <input type="radio" id="existing" name="relationship_type" value="existing">
            <label for="existing" class="pointer">Another day</label>
          </div>
        </div>
      </div>

      {{-- What tool did you use --}}
      <div class="pa4-ns ph3 pv2 mb3 mb0-ns bb b--gray-monica">
        <form-select
          :options="{{ $contactFieldTypes }}"
          v-bind:required="true"
          v-bind:title="'How did you communicate?'"
          v-bind:id="'gender'">
        </form-select>
      </div>

      {{-- Conversation --}}
      <conversation participant-name="{{ $contact->first_name }}"></conversation>

    </form>
  </div>
</section>

@endsection
