@extends('marketing.skeleton')

@section('content')
  <body class="marketing register">
    <div class="container">
        <form class="" action="/validate2fa" method="post">
          <div class="row">
            <div class="col-xs-12 col-md-6 col-md-offset-3">
                <div class="signup-box">
      
              <img class="logo" src="/img/small-logo.png" alt="">
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
        </form>
      </div>
    </div>
  </body>
@endsection
