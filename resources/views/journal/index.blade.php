@extends('layouts.skeleton')

@section('content')

  <div class="journal">

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
                {{ trans('app.breadcrumb_journal') }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="main-content">

      @if ($entries->count() == 0)

        <div class="blank-people-state">
          <div class="{{ Auth::user()->getFluidLayout() }}">
            <div class="row">
              <div class="col-xs-12">
                <h3>{{ trans('journal.journal_blank_description') }}</h3>
                <div class="cta-blank">
                  <a href="/journal/add" class="btn btn-primary">{{ trans('journal.journal_blank_cta') }}</a>
                </div>
                <div class="illustration-blank">
                  <img src="/img/people/blank.svg">
                </div>
              </div>
            </div>
          </div>
        </div>

      @else

        <div class="{{ Auth::user()->getFluidLayout() }}">
          <div class="row">
            <div class="col-xs-12">
              @include ('partials.notification')
            </div>
          </div>
          <div class="row">

            <div class="col-md-9">

              @foreach ($entries as $entry)
                <div class="row entry-row">
                  <div class="col-xs-12 col-sm-2">
                    <div class="entry-information">
                      <ul>
                        <li>{{ \App\Helpers\DateHelper::getShortDate($entry->created_at) }}</li>
                        <li>
                          <a href="/journal/{{ $entry->id }}/delete" onclick="return confirm('{{ trans('people.gifts_delete_confirmation') }}')">{{ trans('journal.journal_entry_delete') }}</a>
                        </li>
                      </ul>
                    </div>
                  </div>

                  <div class="col-xs-12 col-sm-10">
                    @if (! is_null($entry->getTitle()))
                    <h2>{{ $entry->getTitle() }}</h2>
                    @endif
                    <div class="entry-content">{{ $entry->getPost() }}</div>
                  </div>
                </div>
              @endforeach
            </div>

			
            <div class="col-md-3">
              <a class="btn btn-primary btn-add-people" href="/journal/add">{{ trans('journal.journal_add') }}</a>
            </div>

          </div>
        </div>

      @endif
    </div>
  </div>
@endsection
