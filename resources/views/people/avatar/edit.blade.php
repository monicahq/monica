@extends('layouts.skeleton')

@section('content')
  <section class="ph3 ph0-ns">

    {{-- Breadcrumb --}}
    <div class="mt4 mw7 center mb3">
      <p><a href="{{ route('people.show', $contact) }}">< {{ $contact->name }}</a></p>
      <h3 class="f3 fw5">{{ trans('people.avatar_change_title') }}</h3>
    </div>

    <div class="mw7 center br3 ba b--gray-monica bg-white mb5">
      <form method="POST" action="{{ route('people.avatar.update', $contact) }}" enctype="multipart/form-data">
        @csrf

        @include('partials.errors')

        {{-- Adorable --}}
        <contact-avatar
          :avatar="'{{ $contact->avatar_source }}'"
          :default-url="'{{ $contact->getAvatarDefaultURL() }}'"
          :adorable-url="'{{ $contact->avatar_adorable_url }}'"
          :gravatar-url="'{{ $contact->avatar_gravatar_url }}'"
          :photo-url="'{{ $contact->getAvatarURL() }}'"
          :has-reached-account-storage-limit="false"
          :max-upload-size="{{ config('monica.max_upload_size') }}"
        >
        </contact-avatar>

        {{-- Form actions --}}
        <div class="ph4-ns ph3 pv3 bb b--gray-monica">
          <div class="flex-ns justify-between">
            <div>
              <a href="{{ route('people.show', $contact) }}" class="btn btn-secondary w-auto-ns w-100 mb2 pb0-ns">{{ trans('app.cancel') }}</a>
            </div>
            <div>
              <button class="btn btn-primary w-auto-ns w-100 mb2 pb0-ns" name="save" type="submit">{{ trans('app.save') }}</button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection
