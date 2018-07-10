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
                <a href="/dashboard">{{ trans('app.breadcrumb_dashboard') }}</a>
              </li>
              <li>
                <a href="/people">{{ trans('app.breadcrumb_list_contacts') }}</a>
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
    <div class="main-content central-form">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12 col-sm-6 col-sm-offset-3 col-sm-offset-3-right">
            <form method="POST" action="/people/{{ $contact->id }}/work/update" enctype="multipart/form-data">
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

              {{-- LinkedIn --}}
              <div class="form-group">
                <label for="linkedin">{{ trans('people.information_edit_linkedin') }}</label>
                <input class="form-control" name="linkedin" id="linkedin" value="{{ $contact->linkedin_profile_url }}" placeholder="https://linkedin.com/john.doe">
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
