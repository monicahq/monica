@extends('marketing.skeleton')

@section('content')
  <body class="marketing register">
    <div class="container">
      <div class="row">
        <div class="col-12 col-md-6 offset-md-3 offset-md-3-right">

          <div class="signup-box">
            <div class="dt w-100">
              <div class="dtc tc">
                <img src="img/monica.svg" width="97" height="88" alt="">
              </div>
            </div>
            <h2>{{ trans('auth.login_to_account') }}</h2>

            @include ('partials.errors')

            <form action="{{ route('oauth.login') }}" method="post">
              @csrf

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

              @if (isset($errors))
                @if (count($errors) > 0)
                  <div class="form-group links">
                    <ul>
                      <li>{{ trans('auth.password_forget') }}&nbsp;<a href="{{ route('password.request') }}">{{ trans('auth.password_reset') }}</a></li>
                    </ul>
                  </div>
                @endif
              @endif
              <div class="form-group links">
                <ul>
                  @if(! config('monica.disable_signup'))
                    <li>{{ trans('auth.signup_no_account') }}&nbsp;<a href="register">{{ trans('auth.signup') }}</a></li>
                  @elseif(! \App\Helpers\InstanceHelper::hasAtLeastOneAccount())
                    <li>{!! trans('auth.create_account', ['url' => 'register']) !!}</li>
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
