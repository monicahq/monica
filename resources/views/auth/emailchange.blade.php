@extends('marketing.skeleton')

@section('content')
  <body class="marketing register">
    <div class="container">
      <div class="row">
        <div class="col-xs-12 col-md-6 col-md-offset-3 col-md-offset-3-right">

            @include('partials.errors')

            @if (session('status'))
              <div class="alert alert-success">
                  {{ session('status') }}
              </div>
            @endif

            <form action="/settings/emailchange" method="POST">
              {{ csrf_field() }}

              <div class="form-group">
                <label for="email">{{ trans('auth.current_email') }}</label>
                <input type="email" class="form-control" id="email" name="email" required>
              </div>

              <div class="form-group">
                <label for="password">{{ trans('auth.password') }}</label>
                <input type="password" class="form-control" id="password" name="password" required>
              </div>

              {{-- email address --}}
              <div class="form-group">
                <label for="newmail">{{ trans('settings.email') }}</label>
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
