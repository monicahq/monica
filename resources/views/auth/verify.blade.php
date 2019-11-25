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
            <h2>{{ trans('auth.confirmation_title') }}</h2>

            <div class="card-body">
              @if (session('resent'))
                <div class="alert alert-success" role="alert">
                  {{ trans('auth.confirmation_fresh') }}
                </div>
              @endif

              {{ trans('auth.confirmation_check') }}<br/>
              {!! trans('auth.confirmation_request_another', ['url' => route('verification.resend')]) !!}<br/>
              {!! trans('auth.confirmation_again', ['url' => 'settings/emailchange1']) !!}
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
@endsection
