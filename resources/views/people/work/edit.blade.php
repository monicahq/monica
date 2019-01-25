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

    <!-- Page content -->
    <div class="main-content central-form">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12 col-sm-6 col-sm-offset-3 col-sm-offset-3-right">
            <form method="POST" action="{{ route('people.work.update', $contact) }}" enctype="multipart/form-data">
              {{ csrf_field() }}

              @include('partials.errors')

              <h2>{{ trans('people.work_edit_title', ['name' => $contact->first_name]) }}</h2>

              {{-- Job --}}
              <div class="form-group">
                <label for="job">{{ trans('people.work_edit_job') }}</label>
                <input type="text" class="form-control" name="job" id="job" value="{{ $contact->job }}" autofocus>
              </div>

              {{-- Company --}}
              <div class="form-group">
                <label for="company">{{ trans('people.work_edit_company') }}</label>
                <input type="text" class="form-control" name="company" id="company" value="{{ $contact->company }}">
              </div>

              <div class="form-group actions">
                <button type="submit" class="btn btn-primary">{{ trans('app.save') }}</button>
                <a href="{{ route('people.show', $contact) }}" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
              </div> <!-- .form-group -->
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
@endsection
