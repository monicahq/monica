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
            <h2>{{ trans('auth.email_change_title') }}</h2>
            <h3>{{ trans('auth.login_again') }}</h3>

            @include ('partials.errors')
            @if (session('status'))
              <div class="alert alert-success">
                {{ session('status') }}
              </div>
            @endif

            <form action="settings/emailchange1" method="post">
              @csrf

              <div class="form-group">
                <label>{{ trans('auth.email_change_current_email') }}</label>
                {{ $email }}
                <input type="hidden" class="form-control" id="email" name="email" value="{{ $email }}">
              </div>

              <div class="form-group">
                <label for="password">{{ trans('auth.password') }}</label>
                <input type="password" class="form-control" id="password" name="password">
              </div>

              <div class="form-group actions">
                <button type="submit" class="btn btn-primary">{{ trans('auth.login') }}</button>
              </div>

            </form>
          </div>
        </div>
      </div>
    </div>
  </body>
@endsection
