@extends('layouts.skeleton')

@section('title', $contact->name )

@section('content')
<div class="people-show">
  @csrf

  {{-- Breadcrumb --}}
  <div class="breadcrumb">
    <div class="{{ Auth::user()->getFluidLayout() }}">
      <div class="row">
        <div class="col-12">
          <ul class="horizontal">
            <li>
              <a href="{{ route('dashboard.index') }}">{{ trans('app.breadcrumb_dashboard') }}</a>
            </li>
            <li>
              @if ($contact->is_active)
              <a href="{{ route('people.index') }}">{{ trans('app.breadcrumb_list_contacts') }}</a>
              @else
              <a href="{{ route('people.archived') }}">{{ trans('app.breadcrumb_archived_contacts') }}</a>
              @endif
            </li>
            <li>
              {{ $contact->name }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  {{-- Page header --}}
  @include('people._header')

  {{-- Page content --}}
  <div class="main-content profile">

    <div class="{{ Auth::user()->getFluidLayout() }}">

      <div class="row">
        <div class="col-12 col-sm-3 profile-sidebar">

          @if (! is_null($weather))
          <div class="ba b--near-white br2 bg-gray-monica pa3 mb3 f6">
            <div class="w-100 dt">
              <div class="dtc">
                <h3 class="f6 ttu normal">{{ trans('app.weather_current_title') }}</h3>
              </div>
            </div>

            <p class="mb0">
              {{ $weather->getEmoji() }} {{ trans('app.weather_'.$weather->summary_icon) }} / {{ trans('app.weather_current_temperature_'.auth()->user()->temperature_scale, ['temperature' => $weather->temperature(auth()->user()->temperature_scale)]) }}
            </p>
          </div>
          @endif

          @include('people.relationship.index')

          @include('people.sidebar')

          <ul class="mb2">
            <li>
              <a href="{{ route('people.auditlogs', $contact) }}">{{ trans('people.auditlogs_link') }}</a>
            </li>
            <li>
              <a href="{{ route('people.vcard', $contact) }}">{{ trans('people.people_export') }}</a>
            </li>
            <li>
              <contact-archive hash="{{ $contact->hashID() }}" :active="{{ \Safe\json_encode($contact->is_active) }}"></contact-archive>
            </li>
            <li>
              <form method="POST" action="{{ route('people.destroy', $contact) }}">
                @method('DELETE')
                @csrf
                <confirm id="link-delete-contact" message="{{ trans('people.people_delete_confirmation') }}">
                  {{ trans('people.people_delete_message') }}
                </confirm>
              </form>
            </li>
          </ul>
        </div>

        <div class="col-12 col-sm-9">

          <div class="flex items-center justify-center flex-column">
            <div class='cf dib'>
              @if (! $contact->isMe())
              <span @click="updateDefaultProfileView('life-events')" :class="[global_profile_default_view == 'life-events' ? 'f6 fl bb bt br bl ph3 pv2 dib b br2 br--left bl mb4 b--gray-monica' : 'f6 fl bb bt br ph3 pv2 dib bg-gray-monica br2 br--left bl pointer mb4 b--gray-monica']">
                @if (auth()->user()->profile_new_life_event_badge_seen == false)
                <span class="bg-light-green f7 mr2 ph2 pv1 br2">{{ trans('app.new') }}</span>
                @endif
                {{ trans('people.life_event_list_tab_life_events') }} ({{ $contact->lifeEvents()->count() }})
              </span>
              @endif
              <span @click="updateDefaultProfileView('notes')" :class="[global_profile_default_view == 'notes' ? 'f6 fl bb bt ph3 pv2 dib b br--right br mb4 b--gray-monica' : 'f6 fl bb bt ph3 pv2 dib bg-gray-monica br--right br pointer mb4 b--gray-monica']">{{ trans('people.life_event_list_tab_other') }}</span>
              <span @click="updateDefaultProfileView('photos')" :class="[global_profile_default_view == 'photos' ? 'f6 fl bb bt ph3 pv2 dib b br2 br--right br mb4 b--gray-monica' : 'f6 fl bb bt ph3 pv2 dib bg-gray-monica br2 br--right br pointer mb4 b--gray-monica']">Photos</span>
            </div>
          </div>

          @if (! $contact->isMe())
          <div v-if="global_profile_default_view == 'life-events'">
            <div class="row section">
              @include('people.life-events.index')
            </div>
          </div>
          @endif

          <div v-if="global_profile_default_view == 'notes'">
            @if ($modules->contains('key', 'notes'))
            <div class="row section notes">
              <div class="col-12 section-title">
                <contact-note hash={{ $contact->hashID() }}></contact-note>
              </div>
            </div>
            @endif

            @if ($modules->contains('key', 'conversations') && ! $contact->isMe())
            <div class="row section">
              @include('people.conversations.index')
            </div>
            @endif

            @if ($modules->contains('key', 'phone_calls') && ! $contact->isMe())
            <div class="row section calls">
              @include('people.calls.index')
            </div>
            @endif

            @if ($modules->contains('key', 'activities') && ! $contact->isMe())
            <div class="row section activities">
              @include('people.activities.index')
            </div>
            @endif

            @if ($modules->contains('key', 'reminders'))
            <div class="row section reminders">
              @include('people.reminders.index')
            </div>
            @endif

            @if ($modules->contains('key', 'tasks'))
            <div class="row section">
              @include('people.tasks.index')
            </div>
            @endif

            @if ($modules->contains('key', 'gifts') && ! $contact->isMe())
            <div class="row section">
              @include('people.gifts.index')
            </div>
            @endif

            @if ($modules->contains('key', 'debts') && ! $contact->isMe())
            <div class="row section debts">
              @include('people.debt.index')
            </div>
            @endif

            @if ($modules->contains('key', 'documents'))
            <div class="row section">
              @include('people.documents.index')
            </div>
            @endif

          </div>

          <div v-if="global_profile_default_view == 'photos'">
            <div class="row section">
              @include('people.photos.index')
            </div>
          </div>
        </div>
      </div>

    </div>

  </div>
</div>

@endsection
