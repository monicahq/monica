@extends('marketing.skeleton')

@section('content')
  <body class="marketing register">
    <div class="container">
      <div class="row">
        <div class="col-xs-12 col-md-6 col-md-offset-3">

          <div class="signup-box">
            <img class="logo" src="/img/small-logo.png" alt="">
            <h2>Login to your account</h2>

            @include ('partials.errors')

            <form class="" action="/login" method="post">
              {{ csrf_field() }}

              <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}">
              </div>

              <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password">
              </div>

              <div class="form-group actions">
                <button type="submit" class="btn btn-primary">Login</button>
              </div>

              <div class="checkbox">
                <label for="remember">
                  <input type="checkbox" name="remember" id="remember" checked> Remember Me
                </label>
              </div>

              <div class="form-group links">
                <ul>
                  <li>Forget your password? <a href="/password/reset">Reset your password</a></li>
                  @if(! config('monica.disable_signup'))
                    <li>Don't have an account? <a href="/register">Sign up</a></li>
                  @elseif(! \App\Account::hasAny())
                    <li>Create the first account by <a href="/register">signing up</a></li>
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
