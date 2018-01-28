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
                <a href="/settings/users">{{ trans('app.breadcrumb_settings_users') }}</a>
                </li>
                <li>
                  {{ trans('app.breadcrumb_settings_users_add') }}
                </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Page content -->
    <div class="main-content central-form">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12 col-sm-6 col-sm-offset-3">
            <div class="br3 ba b--gray-monica bg-white mb4">
              <div class="pa3 bb b--gray-monica">
                <form method="POST" action="/settings/users/save">
                  {{ csrf_field() }}

                  <h2>{{ trans('settings.users_add_title') }}</h2>

                  <p>{{ trans('settings.users_add_description') }}</p>

                  @include('partials.errors')

                  {{-- Email --}}
                  <fieldset class="form-group">
                    <div class="form-group">
                      <label for="email">{{ trans('settings.users_add_email_field') }}</label>
                      <input type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>
                    </div>
                  </fieldset>

                  {{-- Explicit confirmation --}}
                  <div class="warning-zone">
                    <label class="form-check-label">
                      <input class="form-check-input" type="checkbox" name="confirmation" value="1" v-model="accept_invite_user">
                        {{ trans('settings.users_add_confirmation') }}
                    </label>
                  </div>

                  <div class="form-group actions">
                    <button type="submit" class="btn btn-primary" :disabled="!accept_invite_user">{{ trans('settings.users_add_cta') }}</button>
                    <a href="/settings/users" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
@endsection
