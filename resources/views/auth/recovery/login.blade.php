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
            <h2>{{ trans('auth.login_with_recovery') }}</h2>

            @include ('partials.errors')
            @if (session('status'))
              <div class="alert alert-success">
                {{ session('status') }}
              </div>
            @endif

            <form action="{{ route('recovery.login') }}" method="post">
              {{ csrf_field() }}

              <div class="form-group">
                <label for="recovery">{{ trans('auth.recovery') }}</label>
                <input type="recovery" class="form-control" id="recovery" name="recovery">
              </div>

              <div class="form-group actions">
                <div class="row">
                  <div class="col-12 col-md-6">
                    <button type="submit" class="btn btn-primary">{{ trans('auth.login') }}</button>
                  </div>
                  <div class="col-12 col-md-6">
                    <a href="login" class="btn btn-secondary w-100 mb2 pb0-ns tc" style="margin-top: 10px">{{ trans('app.cancel') }}</a>
                  </div>
                </div>
              </div>

            </form>
          </div>
        </div>
      </div>
    </div>
  </body>
@endsection
