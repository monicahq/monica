@extends('layouts.skeleton')

@section('content')

<div class="settings">

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
              <a href="{{ route('settings.index') }}">{{ trans('app.breadcrumb_settings') }}</a>
            </li>
            <li>
              {{ trans('app.breadcrumb_settings_users') }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">

      @include('settings._sidebar')

      <div class="col-12 col-sm-9 users-list">

        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">

            <h3>{{ trans('settings.storage_title') }}</h3>

            <p>{{ trans('settings.storage_account_info', ['accountLimit' => $accountLimit, 'currentAccountSize' => $currentAccountSize, 'percentUsage' => $percentUsage,]) }}</p>

            <p>{{ trans('settings.storage_description') }}</p>

            <ul class="table">
                @foreach($documents as $document)
                  <li class="table-row">
                    <div class="table-cell">
                        {{ $document->created_at }}
                    </div>
                    <div class="table-cell">
                        {{ $document->original_filename }} ({{ round($document->filesize / 1000) }} Kb)
                    </div>
                    <div class="table-cell">
                        <a href="people/{{ $document->contact->hashID() }}">{{ $document->contact->name }}</a>
                    </div>
                  </li>
                @endforeach
                @foreach($photos as $photo)
                  <li class="table-row">
                    <div class="table-cell">
                        {{ $photo->created_at }}
                    </div>
                    <div class="table-cell">
                        {{ $photo->original_filename }} ({{ round($photo->filesize / 1000) }} Kb)
                    </div>
                    <div class="table-cell">
                        <a href="people/{{ $photo->contact()->hashID() }}">{{ $photo->contact()->name }}</a>
                    </div>
                  </li>
                @endforeach
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
