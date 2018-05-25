@extends('marketing.skeleton')

@section('content')
  <body class="marketing register">
    <div class="container">
      <div class="row">
        <div class="col-xs-12 col-md-6 col-md-offset-3 col-md-offset-3-right">

          <div class="signup-box">
            <div class="dt w-100">
              <div class="dtc tc">
                <img class="" src="/img/monica.svg" width="97" height="88" alt="">
              </div>
            </div>
            <h2>{{ trans('auth.login_to_account') }}</h2>

            @include ('partials.errors')
            @if (session('status'))
              <div class="alert alert-success">
                {{ session('status') }}
              </div>
            @endif

            <form class="" action="/settings/emailchange" method="post">
              {{ csrf_field() }}

              <div class="form-group">
                <label>{{ trans('auth.email') }}</label>
                <input type="hidden" class="form-control" id="email" name="email" value="{{ $email }}">
                {{ $email }}
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
