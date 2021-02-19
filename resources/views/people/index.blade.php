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

  <!-- Page content -->
  <div class="main-content">

    <div class="{{ auth()->user()->getFluidLayout() }}">
      <div class="row">

        <div class="col-12 col-md-9 mb4">

          <h3>View all contacts</h3>
          <ul class="mb4">
            <li><a href="">View all contacts</a></li>
            <li><a href="">View all contacts (including deceased)</a></li>
          </ul>

          <h3>Recently consulted</h3>
          <ul class="mb4 bg-white">
            @foreach ($contacts as $contact)
            <li class="pa2 bb br bl b--gray-monica">
              <img src="{{ $contact['avatar'] }}" alt="">
              <span class="db"><a href="{{ $contact['url'] }}">{{ $contact['name'] }}</a> <span class="ml2 f6 i">{{ $contact['description'] }}</span></span>
            </li>
            @endforeach
          </ul>

          <h3>All the tags</h3>
          <ul>
            @foreach ($tags as $tag)
            <li class="di bg-white ph2 pb1 pt0 dib br3 b--light-gray ba mb2">
              <a href="{{ $tag['url'] }}">{{ $tag['name'] }}</a> <span class="f7">({{ $tag['contact_count'] }})</span>
            </li>
            @endforeach
          </ul>

        </div>

        <div class="col-12 col-md-3 sidebar">
          <a href="{{ route('people.create') }}" id="button-add-contact" class="btn btn-primary sidebar-cta">
            {{ trans('people.people_list_blank_cta') }}
          </a>
          </ul>
        </div>

      </div>
    </div>

  </div>

</div>
@endsection
