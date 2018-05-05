@extends('layouts.skeleton')

@section('title', $contact->getCompleteName(auth()->user()->name_order) )

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

    {{-- Page header --}}
    @include('people._header')

    {{-- Page content --}}
    <div class="main-content profile">

      <div class="{{ Auth::user()->getFluidLayout() }}">

        <div class="row">
          <div class="col-xs-12 col-sm-3 profile-sidebar">

            @include('people.relationship.index')

            @include('people.sidebar')

            <p><a href="{{ url('/people/'.$contact->hashID().'/vcard') }}">{{ trans('people.people_export') }}</a></p>
            <p>
              {{ trans('people.people_delete_message') }}
              <a href="#" onclick="if (confirm('{{ trans('people.people_delete_confirmation') }}')) { $('#contact-delete-form').submit(); } return false;">{{ trans('people.people_delete_click_here') }}</a>.
              <form method="POST" action="{{ action('ContactsController@delete', $contact) }}" id="contact-delete-form" class="hidden">
                {{ method_field('DELETE') }}
                {{ csrf_field() }}
              </form>
            </p>
          </div>

          <div class="col-xs-12 col-sm-9">

            @if ($modules->contains('key', 'notes'))
            <div class="row section notes">
              <div class="col-xs-12 section-title {{ \App\Helpers\LocaleHelper::getDirection() }}">
                <contact-note hash={!! $contact->hashID() !!}></contact-note>
              </div>
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
          </div>
        </div>

      </div>

    </div>
  </div>

  @include('people.modal.log_call')

@endsection
