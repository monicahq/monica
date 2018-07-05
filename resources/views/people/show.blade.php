@extends('layouts.skeleton')

@section('title', $contact->name )

@section('content')
  <div class="people-show" >
    {{ csrf_field() }}

    <div class="dashboard">

    <section class="ph3 ph5-ns pv4 cf w-100 bg-gray-monica">
      <div class="mw9 center">
        sdfasd
      </div>
  </section>

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

      </div>

    </div>
  </div>

@endsection
