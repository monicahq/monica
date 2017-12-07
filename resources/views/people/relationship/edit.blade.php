@extends('layouts.skeleton')

@section('content')
  <div class="people-show significantother">

    {{-- Breadcrumb --}}
    <div class="breadcrumb">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12">
            <ul class="horizontal">
              <li>
                <a href="/dashboard">{{ trans('app.breadcrumb_dashboard') }}</a>
              </li>
              <li>
                <a href="/people">{{ trans('app.breadcrumb_list_contacts') }}</a>
              </li>
              <li>
                <a href="{{ route('people.show', $contact) }}">{{ $contact->getCompleteName(auth()->user()->name_order) }}</a>
              </li>
              <li>
                {{ trans('app.breadcrumb_edit_significant_other') }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Page content -->
    <div class="main-content central-form">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12 col-sm-6 col-sm-offset-3">
            <h2>{{ trans('people.significant_other_add_title', ['name' => $contact->first_name]) }}</h2>

            @include('people.relationship.form', [
              'method' => 'PUT',
              'action' => route('people.relationships.update', [$contact, $partner]),
              'buttonText' => trans('people.significant_other_edit_cta')
            ])
          </div>
        </div>
      </div>
    </div>

  </div>
@endsection
