@extends('layouts.skeleton')

@section('content')

<div class="settings">

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
              <a href="/settings">{{ trans('app.breadcrumb_settings') }}</a>
            </li>
            <li>
              {{ trans('app.breadcrumb_settings_tags') }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">

      @include('settings._sidebar')

      <div class="col-xs-12 col-sm-9 tags-list">

        @if (auth()->user()->account->tags->count() == 0)

          <div class="col-xs-12 col-sm-9 blank-screen">

          <img src="/img/settings/tags/tags.png">

          <h2>{{ trans('settings.tags_blank_title') }}</h2>

          <p>{{ trans('settings.tags_blank_description') }}</p>

        </div>

        @else

          <h3 class="with-actions">
            {{ trans('settings.tags_list_title') }}
          </h3>

          <p>{{ trans('settings.tags_list_description') }}</p>

          @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
          @endif

          <ul class="table">
          @foreach (auth()->user()->account->tags as $tag)
            <li class="table-row" data-tag-id="{{ $tag->id }}">
              <div class="table-cell">
                {{ $tag->name }}
                <span class="tags-list-contact-number">({{ trans('settings.tags_list_contact_number', ['count' => $tag->contacts()->count()]) }})</span>
              </div>
              <div class="table-cell actions">
                <a href="/settings/tags/{{ $tag->id }}/delete" onclick="return confirm('{{ trans('settings.tags_list_delete_confirmation') }}')">
                  <i class="fa fa-trash-o" aria-hidden="true"></i>
                </a>
              </div>
            </li>
          @endforeach
          </ul>

        @endif

      </div>
    </div>
  </div>
</div>

@endsection
