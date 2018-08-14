@extends('layouts.skeleton')

@section('content')
  <div class="people-show journal">

    {{-- Breadcrumb --}}
    <section class="ph3 ph5-ns pv3 w-100 f6 bb b--gray-monica">
      <div class="mw9 center pa2">
          <ul>
            <li class="di">
              <a href="{{ route('dashboard.index') }}">{{ trans('app.breadcrumb_dashboard') }}</a>
            </li>
            <li class="di">
              &gt; <a href="{{ route('journal.index') }}">{{ trans('app.breadcrumb_journal') }}</a>
            </li>
            <li class="di">
              &gt; {{ trans('journal.journal_add') }}
            </li>
          </ul>
      </div>
    </section>

    <!-- Page content -->
    <div class="main-content central-form">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12 col-sm-6 col-sm-offset-3 col-sm-offset-3-right">
            <form method="POST" action="{{ route('journal.save') }}">
              {{ csrf_field() }}

              @include('partials.errors')

              <h2>{{ trans('journal.journal_add') }}</h2>

              {{-- Optional title --}}
              <div class="form-group">
                <label for="field-title">{{ trans('journal.journal_add_title') }}</label>
                <input type="text" id="field-title" class="form-control" name="title" autofocus>
              </div>

              <div class="form-group">
                <label for="field-entry">{{ trans('journal.journal_add_date') }}</label>
                <input type="date" id="field-date" name="date" class="form-control" value="{{ now(\App\Helpers\DateHelper::getTimezone())->toDateString() }}">
              </div>

              <div class="form-group">
                <label for="field-entry">{{ trans('journal.journal_add_post') }}</label>
                <textarea class="form-control" id="field-entry" name="entry" rows="15" required></textarea>
                <p class="f6">{{ trans('app.markdown_description')}} <a href="https://guides.github.com/features/mastering-markdown/" target="_blank">{{ trans('app.markdown_link') }}</a></p>
              </div>

              <div class="form-group actions">
                <button type="submit" cy-name="save-entry-button" class="btn btn-primary">{{ trans('journal.journal_add_cta') }}</button>
                <a href="{{ route('journal.index') }}" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
              </div> <!-- .form-group -->
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
@endsection
