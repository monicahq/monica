@extends('layouts.skeleton')

@section('title', $contact->name )

@section('content')
  <div class="people-show" >
    {{ csrf_field() }}

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
          <div class="col-xs-12 col-sm-3 profile-sidebar">

            @include('people.relationship.index')

            @include('people.sidebar')

            <ul class="mb2">
              <li>
                <a href="{{ route('people.vcard', $contact) }}">{{ trans('people.people_export') }}</a>
              </li>
              <li>
                <contact-archive hash="{{ $contact->hashID() }}" :active="{{ json_encode($contact->is_active) }}"></contact-archive>
              </li>
              <li>
                <a id="link-delete-contact" class="pointer" onclick="if (confirm('{{ trans('people.people_delete_confirmation') }}')) { $('#contact-delete-form').submit(); } return false;">{{ trans('people.people_delete_message') }}</a>
                <form method="POST" action="{{ route('people.destroy', $contact) }}" id="contact-delete-form" class="hidden">
                  {{ method_field('DELETE') }}
                  {{ csrf_field() }}
                </form>
              </li>
            </ul>
          </div>

          <div class="col-xs-12 col-sm-9">
<emotion></emotion>
            <div class="flex items-center justify-center flex-column">
              <div class='cf dib'>
                <span @click="updateDefaultProfileView('life-events')" :class="[global_profile_default_view == 'life-events' ? 'f6 fl bb bt br bl ph3 pv2 dib b br2 br--left bl mb4 b--gray-monica' : 'f6 fl bb bt br ph3 pv2 dib bg-gray-monica br2 br--left bl pointer mb4 b--gray-monica']">
                  @if (auth()->user()->profile_new_life_event_badge_seen == false)
                  <span class="bg-light-green f7 mr2 ph2 pv1 br2">{{ trans('app.new') }}</span>
                  @endif
                  {{ trans('people.life_event_list_tab_life_events') }}
                </span>
                <span @click="updateDefaultProfileView('notes')" :class="[global_profile_default_view == 'notes' ? 'f6 fl bb bt ph3 pv2 dib b br--right br mb4 b--gray-monica' : 'f6 fl bb bt ph3 pv2 dib bg-gray-monica br--right br pointer mb4 b--gray-monica']">{{ trans('people.life_event_list_tab_other') }}</span>
                <span @click="updateDefaultProfileView('photos')" :class="[global_profile_default_view == 'photos' ? 'f6 fl bb bt ph3 pv2 dib b br2 br--right br mb4 b--gray-monica' : 'f6 fl bb bt ph3 pv2 dib bg-gray-monica br2 br--right br pointer mb4 b--gray-monica']">Photos</span>
              </div>
            </div>

            <div v-if="global_profile_default_view == 'life-events'">
              <div class="row section">
                @include('people.life-events.index')
              </div>
            </div>

            <div v-if="global_profile_default_view == 'notes'">
              @if ($modules->contains('key', 'notes'))
              <div class="row section notes">
                <div class="col-xs-12 section-title">
                  <contact-note hash={{ $contact->hashID() }}></contact-note>
                </div>
              </div>
              @endif

              @if ($modules->contains('key', 'conversations'))
              <div class="row section">
                @include('people.conversations.index')
              </div>
              @endif

              @if ($modules->contains('key', 'phone_calls'))
              <div class="row section calls">
                @include('people.calls.index')
              </div>
              @endif

              @if ($modules->contains('key', 'activities'))
              <div class="row section activities">
                @include('activities.index')
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

              @if ($modules->contains('key', 'gifts'))
              <div class="row section">
                @include('people.gifts.index')
              </div>
              @endif

              @if ($modules->contains('key', 'debts'))
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
