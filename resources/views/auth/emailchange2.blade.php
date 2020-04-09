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

            @include ('partials.errors')

            @if (session('status'))
              <div class="alert alert-success">
                  {{ session('status') }}
              </div>
            @endif

            <form action="settings/emailchange2" method="POST">
              @csrf

              <div class="form-group">
                <label>{{ trans('auth.email_change_current_email') }}</label>
                {{ $email }}
              </div>

              {{-- email address --}}
              <div class="form-group">
                <label for="newmail">{{ trans('auth.email_change_new') }}</label>
                <input type="email" class="form-control" name="newmail" id="newmail" placeholder="{{ trans('settings.email_placeholder') }}" required>
                <small id="emailHelp" class="form-text text-muted">{{ trans('settings.email_help') }}</small>
              </div>

              <button type="submit" class="btn btn-primary">{{ trans('app.save') }}</button>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

@endsection
