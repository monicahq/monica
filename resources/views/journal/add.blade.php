@extends('layouts.skeleton')

@section('content')
  <div class="people-show journal">

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
                <a href="/journal">{{ trans('app.breadcrumb_journal') }}</a>
              </li>
              <li>
                {{ trans('journal.journal_add') }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Page content -->
    <div class="main-content modal">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12 col-sm-6 col-sm-offset-3">
            <form method="POST" action="/journal/create">
              {{ csrf_field() }}

              @include('partials.errors')

              <h2>{{ trans('journal.journal_add') }}</h2>

              {{-- Optional title --}}
              <div class="form-group">
                <label for="field-title">{{ trans('journal.journal_add_title') }}</label>
                <input type="text" id="field-title" class="form-control" name="title" autofocus>
              </div>

              <div class="form-group">
                <label for="field-entry">{{ trans('journal.journal_add_post') }}</label>
                <textarea class="form-control" id="field-entry" name="entry" required></textarea>
              </div>

              <div class="form-group actions">
                <button type="submit" class="btn btn-primary">{{ trans('journal.journal_add_cta') }}</button>
                <a href="/journal" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
              </div> <!-- .form-group -->
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
@endsection
