@extends('marketing.skeleton')

@section('content')
  <body class="marketing register">
    <div class="container">
      <div class="row">
        <div class="col-12 col-md-6 offset-md-3 offset-md-3-right">

          <div class="signup-box">
            <h2>{{ trans('auth.password_reset_title') }}</h2>

            @include ('partials.errors')
            @if (session('status'))
              <div class="alert alert-success">
                {{ session('status') }}
              </div>
            @endif

            <form action="{{ route('password.email') }}" method="post">
              @csrf

              <div class="form-group">
                <label for="email">{{ trans('auth.password_reset_email') }}</label>
                <input type="email" class="form-control" id="email" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>
              </div>

              <div class="form-group actions">
                <button type="submit" class="btn btn-primary">
                  <i class="fa fa-btn fa-envelope"></i>&nbsp;{{ trans('auth.password_reset_send_link') }}
                </button>
              </div>

            </form>
          </div>
        </div>
      </div>
    </div>
  </body>
@endsection
