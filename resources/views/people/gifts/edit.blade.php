@extends('layouts.skeleton')

@section('content')
  <div class="people-show">

    {{-- Breadcrumb --}}
    <div class="breadcrumb">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12">
            <ul class="horizontal">
              <li>
                <a href="{{ route('dashboard.index') }}">{{ trans('app.breadcrumb_dashboard') }}</a>
              </li>
              <li>
                <a href="{{ route('people.index') }}">{{ trans('app.breadcrumb_list_contacts') }}</a>
              </li>
              <li>
                {{ $contact->name }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Page header -->
    @include('people._header')

    <!-- Page content -->
    <div class="main-content gifts central-form">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12 col-sm-6 col-sm-offset-3 col-sm-offset-3-right">
            @include('people.gifts.form', [
              'method' => 'PUT',
              'action' => route('people.gifts.update', [$contact, $gift])
            ])
          </div>
        </div>
      </div>
    </div>

  </div>
@endsection
