@extends('layouts.skeleton')

@section('content')
<div class="people-list">
  @csrf

  {{-- Breadcrumb --}}
  <div class="breadcrumb">
    <div class="{{ Auth::user()->getFluidLayout() }}">
      <div class="row">
        <div class="col-12">
          <ul class="horizontal">
            <li>
              <a href="{{ route('dashboard.index') }}">{{ trans('app.breadcrumb_dashboard') }}</a>
            </li>
            <li>
              {{ trans('app.breadcrumb_list_contacts') }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <section class="ph3 ph5-ns cf w-100 bg-gray-monica">
    <div class="mw9 center">
      <!-- left column -->
      <div class="fl w-70-ns w-100 pa2">

        <div class="">
          You have {{ $totalRecords }} contacts.
        </div>

        <ul class="dt w-100 contact-list-page">

          @foreach ($contacts as $contact)
          <li class="dt-row w-100">
            <!-- checkbox -->
            <div class="dtc bt bg-white pl-2 pr-0 v-mid bl">
              <input type="checkbox" name="members[]" value="asbiin" aria-labelledby="member-asbiin" class="js-bulk-actions-toggle" data-check-all-item="">
            </div>

            <!-- photo -->
            <div class="dtc bt bg-white pv2 ph2 avatar relative v-mid">
              <img src="{{ $contact['avatar'] }}" width="40" height="40" class="dib">
            </div>

            <!-- name -->
            <div class="dtc bt bg-white pl-2 pr-0 pt-2 w-100 v-mid br">
              <div class="names relative">
                <a href="{{ $contact['route']}}" class="v-top">{{ $contact->name }}</a>
                <span class="db">{{ $contact->description }} | {{ $contact['career.job'] }}</span>
              </div>
            </div>
          </li>
          @endforeach
        </ul>
      </div>

      <!-- right column -->
      <div class="fl w-30-ns w-100 pa2">

      </div>
    </div>
  </section>

</div>
@endsection
