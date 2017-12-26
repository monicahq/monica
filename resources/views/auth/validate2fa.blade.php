@extends('marketing.skeleton')

@section('content')
  <body class="marketing register">
    <div class="container">
      <div class="row">
        <div class="col-xs-12 col-md-6 col-md-offset-3">

          <div class="signup-box">
            <img class="logo" src="/img/small-logo.png" alt="">
            <h2>Two Factor Authentication</h2>

            @include ('partials.errors')

            <form class="" action="/dashboard" method="post">
              {{ csrf_field() }}

              @if ($errors->has('totp'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('totp') }}</strong>
                                </span>
                                @endif
              <div class="form-group">
                <label for="one_time_password">Enter code</label>
                <input type="number" class="form-control" id="one_time_password" name="one_time_password" />
              </div>

              <div class="checkbox">
                <label for="remember">Remember me on this nrowser</label>
                <input type="checkbox" name="remember" id="remember" />
              </div>

              <div class="form-group actions">
                <button type="submit" class="btn btn-primary">Validate</button>
              </div>

              <div class="form-group">
                 Use recuperation code ..
              </div>

            </form>
          </div>
        </div>
      </div>
    </div>
  </body>
@endsection
