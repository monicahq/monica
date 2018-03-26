@extends('marketing.skeleton')

@section('content')
  <body class="marketing register">
    <div class="container">
      <div class="row">
        <div class="col-xs-12 col-md-6 col-md-offset-3">

          <div class="signup-box">
            <img class="logo" src="/img/small-logo.png" alt="">
            <h2>{{ trans('auth.login_to_account') }}</h2>

            @include ('partials.errors')

            <form class="" action="/login" method="post">
              {{ csrf_field() }}

              <div class="form-group">
                <label for="email">{{ trans('auth.email') }}</label>
                <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}">
              </div>

              <div class="form-group">
                <label for="password">{{ trans('auth.password') }}</label>
                <input type="password" class="form-control" id="password" name="password">
              </div>

              <div class="form-group actions">
                <button type="submit" class="btn btn-primary">{{ trans('auth.login') }}</button>
              </div>

              <div class="checkbox">
                <label for="remember">
                  <input type="checkbox" name="remember" id="remember" checked>&nbsp;{{ trans('auth.button_remember') }}
                </label>
              </div>

              <div class="form-group links">
                <ul>
                  <li>{{ trans('auth.password_forget') }}&nbsp;<a href="/password/reset">{{ trans('auth.password_reset') }}</a></li>
                  @if(! config('monica.disable_signup'))
                    <li>{{ trans('auth.signup_no_account') }}&nbsp;<a href="/register">{{ trans('auth.signup') }}</a></li>
                  @elseif(! \App\Account::hasAny())
                    <li>{!! trans('auth.create_account', ['url' => '/register']) !!}</li>
                  @endif
                </ul>
              </div>

            </form>
          </div>
        </div>
      </div>
    </div>
  </body>
@endsection
