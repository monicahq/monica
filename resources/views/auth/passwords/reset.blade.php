@extends('marketing.skeleton')

@section('content')
  <body class="marketing register">
    <div class="container">
      <div class="row">
        <div class="col-12 col-md-6 offset-md-3 offset-md-3-right">

          <div class="signup-box">
            <h2>{{ trans('auth.password_reset_title') }}</h2>

            @include ('partials.errors')

            <form action="{{ route('password.update') }}" method="post">
              @csrf

              <input type="hidden" name="token" value="{{ $token }}">

              <div class="form-group">
                <label for="email">{{ trans('auth.password_reset_email') }}</label>
                <input type="email" class="form-control" id="email" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>
              </div>
      
              <div class="form-group{{ $errors->has('password') ? ' is-invalid' : '' }}">
                <label for="password">{{ trans('auth.password_reset_password') }}</label>
                <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password">
                </div>

              <div class="form-group">
                <label for="password-confirm">{{ trans('auth.password_reset_password_confirm') }}</label>
                <input type="password" class="form-control" id="password-confirm" name="password_confirmation" required autocomplete="new-password">
              </div>

              <div class="form-group actions">
                <div class="col-md-6 offset-md-4">
                  <button type="submit" class="btn btn-primary">
                    <i class="fa fa-btn fa-refresh"></i>&nbsp;{{ trans('auth.password_reset_action') }}
                  </button>
                </div>
              </div>

            </form>
          </div>
        </div>
      </div>

    </div>
  </body>
@endsection
