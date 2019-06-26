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
        {{ csrf_field() }}

        @include('partials.errors')

        {{-- Adorable --}}
        <div class="pa4-ns ph3 pv2 bb b--gray-monica">
            <p>{{ trans('people.avatar_question') }}</p>
            <div class="mb3 mb0-ns">
                <!-- Default avatar -->
                <form-radio
                  :name="'avatar'"
                  :value="'default'"
                  :modelValue="'{{ $contact->avatar_source }}'"
                  :dclass="'flex mb1'"
                  :iclass="'{{ htmldir() == 'ltr' ? 'mr2' : 'ml2' }}'"
                >
                  <template slot="label">
                    {{ trans('people.avatar_default_avatar') }}
                  </template>
                  <div slot="extra">
                    <img class="mb4 pa2 ba b--gray-monica br3" style="width: 150px" src="{{ $contact->getAvatarDefaultURL() }}" alt="" />
                  </div>
                </form-radio>

                <!-- Adorable avatar -->
                <form-radio
                  :name="'avatar'"
                  :value="'adorable'"
                  :modelValue="'{{ $contact->avatar_source }}'"
                  :dclass="'flex mb1'"
                  :iclass="'{{ htmldir() == 'ltr' ? 'mr2' : 'ml2' }}'"
                >
                  <template slot="label">
                    {{ trans('people.avatar_adorable_avatar') }}
                  </template>
                  <div slot="extra">
                    <img class="mb4 pa2 ba b--gray-monica br3" style="width: 150px" src="{{ $contact->avatar_adorable_url }}" alt="" />
                  </div>
                </form-radio>

                <!-- Gravatar -->
                @if (!is_null($contact->avatar_gravatar_url))
                <form-radio
                  :name="'avatar'"
                  :value="'gravatar'"
                  :modelValue="'{{ $contact->avatar_source }}'"
                  :dclass="'flex mb1'"
                  :iclass="'{{ htmldir() == 'ltr' ? 'mr2' : 'ml2' }}'"
                >
                  <template slot="label">
                    {{ trans('people.avatar_gravatar') }}
                  </template>
                  <div slot="extra">
                    <img class="mb4 pa2 ba b--gray-monica br3" style="width: 150px" src="{{ $contact->avatar_gravatar_url }}" alt="" />
                  </div>
                </form-radio>
                @endif

                <!-- Existing avatar -->
                @if ($contact->avatar_source == 'photo')
                <form-radio
                  :name="'avatar'"
                  :value="'photo'"
                  :modelValue="'{{ $contact->avatar_source }}'"
                  :dclass="'flex mb1'"
                  :iclass="'{{ htmldir() == 'ltr' ? 'mr2' : 'ml2' }}'"
                >
                  <template slot="label">
                    {{ trans('people.avatar_current') }}
                  </template>
                  <div slot="extra">
                    <img class="mb4 pa2 ba b--gray-monica br3" style="width: 150px"
                      src="{{ $contact->getAvatarURL() }}" alt="" />
                  </div>
                </form-radio>
                @endif

                <!-- Upload avatar -->
                <form-radio
                  :name="'avatar'"
                  :value="'upload'"
                  :modelValue="'{{ $contact->avatar_source }}'"
                  :disabled="{{ \Safe\json_encode($contact->account->hasReachedAccountStorageLimit()) }}"
                  :dclass="'flex mb1'"
                  :iclass="'{{ htmldir() == 'ltr' ? 'mr2' : 'ml2' }}'"
                >
                  <template slot="label">
                    {{ trans('people.avatar_photo') }}
                    <span class="{{ $contact->account->hasReachedAccountStorageLimit() ? '' : 'hidden' }}"><a href="{{ route('settings.subscriptions.index') }}">{{ trans('app.upgrade') }}</a></span>
                  </template>
                  <div slot="extra">
                    <input type="file" class="form-control-file" name="photo">
                    <small class="form-text text-muted">{{ trans('people.information_edit_max_size', ['size' => config('monica.max_upload_size')]) }}</small>
                  </div>
                </form-radio>
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
