@extends('layouts.skeleton')

@section('content')
  <section class="ph3 ph0-ns">

    {{-- Breadcrumb --}}
    <div class="mt4 mw7 center mb3">
      <p><a href="{{ route('people.show', $contact) }}">< {{ $contact->name }}</a></p>
      <h3 class="f3 fw5">Change your avatar</h3>
    </div>

    <div class="mw7 center br3 ba b--gray-monica bg-white mb5">
      <form method="POST" action="{{ route('people.avatar.update', $contact) }}" enctype="multipart/form-data">
        {{ csrf_field() }}

        @include('partials.errors')

        {{-- Adorable --}}
        <div class="pa4-ns ph3 pv2 bb b--gray-monica">
            <p>Which avatar would you like to use?</p>
            <div class="mb3 mb0-ns">
                <!-- Adorable avatar -->
                <div class="flex mb1">
                    <div class="[dirltr ? 'mr2' : 'ml2']">
                        <label class="pointer">
                            <input type="radio" id="" name="avatar" {{ $contact->avatar_source == 'adorable' ? 'checked' : '' }} value="adorable">
                            The default avatar
                        </label>
                    </div>
                </div>
                <img class="mb4 pa2 ba b--gray-monica br3 ml4" src="{{ $contact->avatar_adorable_url }}" alt="">

                <!-- Gravatar -->
                @if (!is_null($contact->avatar_gravatar_url))
                <div class="flex mb1">
                    <div class="[dirltr ? 'mr2' : 'ml2']">
                        <label class="pointer">
                            <input type="radio" id="" name="avatar" {{ $contact->avatar_source == 'gravatar' ? 'checked' : '' }} value="gravatar">
                            The Gravatar associated with the email address of this person. <a href="https://gravatar.com/">Gravatar</a> is a global system that lets users associate email addresses with photos.
                        </label>
                    </div>
                </div>
                <img class="mb4 pa2 ba b--gray-monica br3 ml4" src="{{ $contact->avatar_gravatar_url }}" alt="">
                @endif

                <!-- Existing avatar -->
                @if ($contact->avatar_source == 'photo')
                <div class="flex mb1">
                    <div class="[dirltr ? 'mr2' : 'ml2']">
                        <label class="pointer">
                            <input type="radio" id="" name="avatar" {{ $contact->avatar_source == 'photo' ? 'checked' : '' }} value="gravatar">
                            Keep this current avatar. If you choose another avatar, this photo will be deleted.
                        </label>
                    </div>
                </div>
                <img class="mb4 pa2 ba b--gray-monica br3 ml4" style="max-width: 200px;" src="{{ $contact->getAvatarURL() }}" alt="">
                @endif

                <!-- Upload avatar -->
                <div class="flex mb3">
                    <div class="[dirltr ? 'mr2' : 'ml2']">
                        <label class="pointer">
                            <input type="radio" id="" name="avatar" value="upload" {{ $contact->account->hasReachedAccountStorageLimit() ? 'disabled' : '' }}>
                            From a photo that you upload
                            <span class="{{ $contact->account->hasReachedAccountStorageLimit() ? '' : 'hidden' }}"><a href="/settings/subscriptions">{{ trans('app.upgrade') }}</a></span>
                        </label>
                    </div>
                </div>
                <div class="ml4">
                    <input type="file" class="form-control-file" name="photo">
                    <small class="form-text text-muted">{{ trans('people.information_edit_max_size', ['size' => config('monica.max_upload_size')]) }}</small>
                </div>
            </div>
        </div>

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
