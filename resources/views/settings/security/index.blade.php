@extends('layouts.skeleton')

@section('content')

<div class="settings">

  {{-- Breadcrumb --}}
  <div class="breadcrumb">
    <div class="{{ auth()->user()->getFluidLayout() }}">
      <div class="row">
        <div class="col-xs-12">
          <ul class="horizontal">
            <li>
              <a href="{{ route('dashboard.index') }}">{{ trans('app.breadcrumb_dashboard') }}</a>
            </li>
            <li>
              <a href="{{ route('settings.index') }}">{{ trans('app.breadcrumb_settings') }}</a>
            </li>
            <li>
              {{ trans('app.breadcrumb_settings_security') }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="{{ auth()->user()->getFluidLayout() }}">
    <div class="row">

      @include('settings._sidebar')

      <div class="col-xs-12 col-md-9">

        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">
            @include('partials.errors')

            @if (session('status'))
              <div class="alert alert-success">
                  {{ session('status') }}
              </div>
            @endif

            <h3 class="with-actions">{{ trans('settings.security_title') }}</h3>
            <p>{{ trans('settings.security_help') }}</p>

            <form method="POST" action="{{ route('settings.security.passwordChange') }}" class="settings-reset">
              {{ csrf_field() }}

              <h2>{{ trans('settings.password_change') }}</h2>

              <div class="form-group">
                <label for="password_current">{{ trans('settings.password_current') }}</label>
                <input type="password" class="form-control" name="password_current" id="password_current" placeholder="{{ trans('settings.password_current_placeholder') }}" required />
              </div>
              <div class="form-group">
                <label for="password">{{ trans('settings.password_new1') }}</label>
                <input type="password" class="form-control" name="password" id="password" placeholder="{{ trans('settings.password_new1_placeholder') }}" required />
              </div>
              <div class="form-group">
                <label for="password_confirmation">{{ trans('settings.password_new2') }}</label>
                <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" placeholder="{{ trans('settings.password_new2_placeholder') }}" required />
              </div>

              <button type="submit" class="btn">{{ trans('settings.password_btn') }}</button>
            </form>

            @if (config('google2fa.enabled')===true || config('u2f.enable')===true)
            <form class="settings-reset">
              <h2>{{ trans('settings.2fa_title') }}</h2>

              <recovery-codes></recovery-codes>

              @if (config('google2fa.enabled')===true)
                <mfa-activate
                  :activated="{{ \Safe\json_encode($is2FAActivated) }}"
                >
                </mfa-activate>
              @endif

              @if (config('webauthn.enable')===true)
                <webauthn-connector
                  :method="'register-modal'"
                  :keys="{{ json_encode($webauthnKeys) }}"
                  :timezone="{{ json_encode(auth()->user()->timezone) }}"
                >
                @if (config('u2f.enable')===true)
                  <u2f-connector
                    :method="'register-modal'"
                    :currentkeys="{{ json_encode($currentkeys) }}"
                    :timezone="{{ json_encode(auth()->user()->timezone) }}"
                    :enable-register="{{ json_encode(false) }}"
                  >
                  </u2f-connector>
                @endif
                </webauthn-connector>
              @elseif (config('u2f.enable')===true)
                <u2f-connector
                  :method="'register-modal'"
                  :currentkeys="{{ \Safe\json_encode($currentkeys) }}"
                  :timezone="{{ \Safe\json_encode(auth()->user()->timezone) }}"
                  :enable-register="{{ \Safe\json_encode(true) }}"
                >
                </u2f-connector>
                  
                <script src="{{ asset(mix('js/u2f-api.js')) }}" type="text/javascript"></script>
              @endif

            </form>

            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
