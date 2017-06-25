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
                {{ $contact->getCompleteName(auth()->user()->name_order) }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Page header -->
    @include('people._header')

    <!-- Page content -->
    <div class="main-content tasks modal">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12 col-sm-6 col-sm-offset-3">
            <form method="POST" action="/people/{{ $contact->id }}/tasks/store">
              {{ csrf_field() }}

              <h2>{{ trans('people.tasks_add_title_page', ['name' => $contact->getFirstName()]) }}</h2>

              @include('partials.errors')

              {{-- First name --}}
              <div class="form-group">
                <label for="title">{{ trans('people.tasks_add_title') }}</label>
                <input type="text" class="form-control" name="title" id="title" value="{{ old('title') ?? $task->description }}" autofocus required>
              </div>

              <div class="form-group">
                <label for="description">{{ trans('people.tasks_add_optional_comment') }}</label>
                <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') ?? $task->description }}</textarea>
              </div>

              <div class="form-group actions">
                <button type="submit" class="btn btn-primary">{{ trans('people.tasks_add_cta') }}</button>
                <a href="/people/{{ $contact->id }}" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
@endsection
