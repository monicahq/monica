@extends('marketing.auth')

@section('content')
  <body class="marketing register">
    <div class="container">
      <form class="" action="/validate2fa" method="post">
        <input type="hidden" name="url" value="{{ urlencode(url()->current()) }}" />
        @if (app('pragmarx.google2fa')->isActivated())
        <div class="row">
          <div class="col-xs-12 col-md-6 col-md-offset-3 col-md-offset-3-right">
            <div class="signup-box">

              <div class="dt w-100">
                <div class="dtc tc">
                  <img class="" src="/img/monica.svg" width="97" height="88" alt="">
                </div>
              </div>
              <h2>{{ trans('auth.2fa_title') }}</h2>

              @include ('partials.errors')

              {{ csrf_field() }}

              @if ($errors->has('totp'))
                <span class="help-block">
                  <strong>{{ $errors->first('totp') }}</strong>
                </span>
              @endif
              <div class="form-group">
                <label for="one_time_password">{{ trans('auth.2fa_one_time_password') }}</label>
                <input type="number" class="form-control" id="one_time_password" name="one_time_password" required />
              </div>

              {{-- TODO
              <div class="form-group checkbox">
                <input type="checkbox" name="remember" id="remember" />
                <label for="remember">Remember me on this browser</label>
              </div>
              --}}

              {{-- TODO
              <div class="form-group">
                 {{ trans('auth.2fa_recuperation_code') }}
              </div>
              --}}

              <div class="row">
                <div class="col-xs-12 col-md-6">
                  <div class="form-group actions">
                    <button type="submit" name="verify" class="btn btn-primary">{{ trans('app.verify') }}</button>
                  </div>
                </div>
                <div class="col-xs-12 col-md-6">
                  <div class="form-group actions">
                    <a href="/logout" class="btn action">{{ trans('app.cancel') }}</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        @endif
      
        <div class="row">
          <div class="col-xs-12 col-md-6 col-md-offset-3 col-md-offset-3-right">
            <div class="signup-box">
      
              <img class="logo" src="/img/small-logo.png" alt="">

              <u2f-connector
                :authdatas="{{ json_encode($authenticationData) }}"
                :method="'login'"
                :callbackurl="{{ json_encode(url()->current()) }}">
              </u2f-connector>

            </div>
          </div>
        </div>
      </form>
    </div>

  </body>
  <script src="{{ mix('js/u2f-api.js') }}" type="text/javascript"></script>
@endsection
