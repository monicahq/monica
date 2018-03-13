@extends('marketing.skeleton')

@section('content')
  <body class="marketing register">
    <div class="container">
      <div class="row">
        <div class="col-xs-12 col-md-6 col-md-offset-3">

          <div class="signup-box">
            <img class="logo" src="/img/small-logo.png" alt="">
            @if($first)
              <h1>Welcome to your newly installed Monica instance</h1>
              <h2>You need to create an account to use Monica</h2>
            @else
              <h2>Create your Monica account</h2>
              <h3><a href="/login">Log in</a> if you already have an account.</h3>
            @endif

            @include ('partials.errors')

            <form class="" action="/register" method="post">
              {{ csrf_field() }}

              <div class="form-group">
                <label for="email">Enter a valid email address</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="you@home" value="{{ old('email') }}" required>
              </div>

              <div class="row">
                <div class="col-xs-12 col-sm-6">
                  <div class="form-group">
                    <label for="first_name">First name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" placeholder="eg. John" value="{{ old('first_name') }}" required>
                  </div>
                </div>
                <div class="col-xs-12 col-sm-6">
                  <div class="form-group">
                    <label for="last_name">Last name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" placeholder="eg. Doe" value="{{ old('last_name') }}" required>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter a secure password" required>
              </div>

              <div class="form-group">
                <label for="password_confirmation">Password confirmation</label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
              </div>

              <div class="form-group actions">
                <button type="submit" class="btn btn-primary">Register</button>
              </div>

              <div class="help">
                Signing up signifies you’ve read and agree to our <a href="https://monicahq.com/privacy">Privacy Policy</a>.
              </div>

            </form>
          </div>

        </div>
      </div>
    </div>
  </body>
@endsection
