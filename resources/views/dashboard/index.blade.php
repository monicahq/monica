@extends('layouts.skeleton')

@section('content')
  <div class="dashboard">

    <section class="ph3 ph5-ns pv4 cf w-100 bg-gray-monica">
      <div class="mw9 center flex justify-center items-center">
        <div class="pr2">
          Last consulted
        </div>
        @foreach($lastUpdatedContacts as $contact)
        <div class="pr2 pointer">
          <avatar v-bind:contact="{{ $contact }}"></avatar>
        </div>
        @endforeach
      </div>
    </section>

    {{-- Main section --}}
    <section class="ph3 ph5-ns pv4 cf w-100 bg-gray-monica">
      <div class="mw9 center">
        <div class="fl w-50-ns w-100 pa2">
          <div class="br3 ba b--gray-monica bg-white pa3 mb4">
            <h2 class="normal f3">Events in the next 3 months</h2>

            {{-- Current month --}}
            @include('dashboard._monthReminder', ['month' => 0])

            {{-- Current month + 1 --}}
            @include('dashboard._monthReminder', ['month' => 1])

            {{-- Current month + 2 --}}
            @include('dashboard._monthReminder', ['month' => 2])

          </div>
        </div>
        <div class="fl w-50-ns w-100 pa2">
          <div class="br3 ba b--gray-monica bg-white pa3 mb4">
            sdafs
          </div>
        </div>
      </div>
    </section>

  </div>

  <div class="dashboard">


  </div>
@endsection
