@extends('layouts.skeleton')

@section('title', $contact->name )

@section('content')

{{-- BREADCRUMB --}}
<div class="ph5 cf w-100 mb3 dn db-m db-l">
  <div class="mw9 center dt w-100">
    <ul class="list ma0 pa0 breadcrumb">
      <li class="di">
        <a href="{{ route('dashboard.index') }}">{{ trans('app.breadcrumb_dashboard') }}</a>
      </li>
      <li class="di">
        @if ($contact->is_active)
        <a href="{{ route('people.index') }}">{{ trans('app.breadcrumb_list_contacts') }}</a>
        @else
        <a href="{{ route('people.archived') }}">{{ trans('app.breadcrumb_archived_contacts') }}</a>
        @endif
      </li>
      <li class="di">
        {{ $contact->name }}
      </li>
    </ul>
  </div>
</div>

{{-- BREADCRUMB MOBILE --}}
<div class="ph2 ph5-ns cf w-100 mb3 dn-ns">
  <div class="mw9 center dt w-100">
    <ul class="list ma0 pa0 breadcrumb">
      <li class="di">
        <a href="{{ route('people.index') }}">Back to the list of contacts</a>
      </li>
    </ul>
  </div>
</div>

@include('people._header')

<div class="ph2 ph5-ns cf w-100 mb3">
  <div class="mw9 center dt w-100">
    <div class="cf">

      {{-- LEFT SIDEBAR --}}
      <div class="fl w-20 pl3 pr2 dn db-m db-l contact-left-sidebar">
        <ul class="list pa0 mt0">
          <li class="mb4">
            <a class="no-underline no-color" href="">
              <img class="mr1" src="/img/people/sidebar/menu_summary.svg">
              Summary
            </a>
          </li>
        </ul>

        <p class="ttu normal f7 mb3">Information</h3>
        <ul class="list pa0 mt0">
          <li class="mb2">
            <a class="no-underline no-color" href="">
              <img class="mr1" src="/img/people/sidebar/menu_relationships.svg">
              General
            </a>
          </li>
          <li class="mb2">
            <a class="no-underline no-color" href="">
              <img class="mr1" src="/img/people/sidebar/menu_activities.svg">
              Activities
            </a>
          </li>
          <li class="mb2">
            <a class="no-underline no-color" href="">
              <img class="mr1" src="/img/people/sidebar/menu_reminders.svg">
              Reminders
            </a>
          </li>
          <li class="mb2">
            <a class="no-underline no-color" href="">
              <img class="mr1" src="/img/people/sidebar/menu_gifts.svg">
              Gifts & debts
            </a>
          </li>
          <li class="mb2">
            <a class="no-underline no-color" href="">
              <img class="mr1" src="/img/people/sidebar/menu_documents.svg">
              Documents & photos
            </a>
          </li>
          <li class="mb4">
            <a class="no-underline no-color" href="">
            <img class="mr1" src="/img/people/sidebar/menu_calendar.svg">
              Calendar
            </a>
          </li>
        </ul>

        <p class="ttu normal f7 mb3">Profile actions</p>
        <ul class="list pa0 mt0">
          <li class="mb2">
            <a class="no-underline no-color" href="">
              <img class="mr1" src="/img/people/sidebar/menu_edit.svg">
              Edit information
            </a>
          </li>
          <li class="mb2">
            <a class="no-underline no-color" href="">
            <img class="mr1" src="/img/people/sidebar/menu_archive.svg">
              Archive
            </a>
          </li>
          <li class="mb2">
            <a class="no-underline no-color" href="">
            <img class="mr1" src="/img/people/sidebar/menu_export.svg">
              Export
            </a>
          </li>
          <li class="mb2">
            <a class="no-underline no-color" href="">
              <img class="mr1" src="/img/people/sidebar/menu_public.svg">
              Public link
            </a>
          </li>
          <li class="mb2">
            <a class="no-underline no-color" href="">
              <img class="mr1" src="/img/people/sidebar/menu_delete.svg">
              Delete
            </a>
          </li>
        </ul>
      </div>

      {{-- RIGHT CONTENT --}}
      <div class="fl w-80-ns w-100 pl2-ns pr0">

        {{-- GROUP TABS --}}
        <div class="flex items-center justify-center flex-column mb4">
          <div class="cf dib btn-group">
            <span class="f6 fl ph3 pv2 dib pointer">
              Summary
            </span>
            <span class="f6 fl ph3 pv2 pointer dib">
              Life events
            </span>
            <span class="f6 fl ph3 pv2 dib selected">
              Notes
            </span>
          </div>
        </div>

        <contact-note hash={{ $contact->hashID() }}></contact-note>

      </div>
    </div>
  </div>
</div>

@endsection
