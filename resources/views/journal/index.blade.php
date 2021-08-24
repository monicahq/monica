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
        <div class="w-30-ns">
          <!-- Entries -->
          <div v-if="journalEntries.data" class="ba b--gray-monica br3 bg-white">
            <div v-for="entry in journalEntries.data" :key="entry.id" class="pa3 bb b--gray-monica journal-list">
              <p class="mb1 f7 gray">{{ entry.written_at }}</p>

              <h3 class="mb1">
                {{ entry.title }}
              </h3>

              <span dir="auto" class="markdown" v-html="entry.post"></span>
            </div>
          </div>

          <!-- load more button -->
          <div v-if="(journalEntries.per_page * journalEntries.current_page) <= journalEntries.total" class="br3 ba b--gray-monica bg-white pr3 pb3 pt3 mb3 tc">
            <p class="mb0 pointer" @click="loadMore()">
              <span v-if="!loadingMore">
                {{ $t('app.load_more') }}
              </span>
              <span v-else class="black-50">
                {{ $t('app.loading') }}
              </span>
            </p>
          </div>

          <!-- blank state -->
          <div v-if="journalEntries.total === 0" class="br3 ba b--gray-monica bg-white pr3 pb3 pt3 mb3 tc">
            <div class="tc mb4">
              <img src="img/journal/blank.svg" :alt="$t('journal.journal_empty')" />
            </div>
            <h3>
              {{ $t('journal.journal_blank_cta') }}
            </h3>
            <p>{{ $t('journal.journal_blank_description') }}</p>
          </div>
        </div>

        <div class="w-70-ns pl3">
          <div v-html="{{ post }}" class="bg-white"></div>
        </div>
      </div>

      <!-- Right sidebar -->
      <div class="w-30 pa2">
        <a v-cy-name="'add-entry-button'" href="journal/add" class="btn btn-primary w-100 mb4">
          {{ $t('journal.journal_add') }}
        </a>
        <p>{{ $t('journal.journal_description') }}</p>
      </div>
    </div>
  </section>

</div>
@endsection
