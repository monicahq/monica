@extends('layouts.skeleton')

@section('content')

<div class="journal">

  {{-- Breadcrumb --}}
  <section class="ph3 ph5-ns pv3 w-100 f6 bb b--gray-monica">
    <div class="mw9 center pa2">
      <ul>
        <li class="di">
          <a href="{{ route('dashboard.index') }}">{{ trans('app.breadcrumb_dashboard') }}</a>
        </li>
        <li class="di">
          &gt; {{ trans('app.breadcrumb_journal') }}
        </li>
      </ul>
    </div>
  </section>

  {{-- Main section --}}
  <section class="ph3 ph5-ns pv3 cf w-100 bg-gray-monica">
    <div class="mw9 center">
      <!-- Left sidebar -->
      <div class="w-70-ns w-100 pa2 cf">
        <div class="">
          <!-- Entries -->
          <div class="ba b--gray-monica br3 bg-white">

          @foreach ($entries as $entry)
            <div class="pa3 bb b--gray-monica journal-list">
              <p class="mb1 f7 gray">{{ $entry['written_at'] }}</p>

              <h3 class="mb1">
                {{ $entry['title'] }}
              </h3>

              <span dir="auto" class="markdown">{{ $entry['post'] }}</span>
            </div>
          @endforeach

          </div>

          <!-- blank state -->
          <!-- <div v-if="journalEntries.total === 0" class="br3 ba b--gray-monica bg-white pr3 pb3 pt3 mb3 tc">
            <div class="tc mb4">
              <img src="img/journal/blank.svg" alt="trans('journal.journal_empty')" />
            </div>
            <h3>
              {{ trans('journal.journal_blank_cta') }}
            </h3>
            <p>{{ trans('journal.journal_blank_description') }}</p>
          </div> -->
        </div>
      </div>

      <!-- Right sidebar -->
      <div class="w-30 pa2">
        <a v-cy-name="'add-entry-button'" href="journal/add" class="btn btn-primary w-100 mb4">
          {{ trans('journal.journal_add') }}
        </a>
        <p>{{ trans('journal.journal_description') }}</p>
      </div>
    </div>
  </section>

</div>
@endsection
