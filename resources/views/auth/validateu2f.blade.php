@extends('marketing.auth')

@section('content')
  <body class="marketing register">
    <div class="container">
      <form class="" action="/validate2fa" method="post">
        <input type="hidden" name="url" value="{{ urlencode(url()->current()) }}" />
      
        <div class="row">
          <div class="col-xs-12 col-md-6 col-md-offset-3 col-md-offset-3-right">
            <div class="signup-box">
      
              <div class="dt w-100">
                <div class="dtc tc">
                  <img class="" src="/img/monica.svg" width="97" height="88" alt="">
                </div>
              </div>
              <h2>{{ trans('auth.2fa_title') }}</h2>
          
              @include ('partials.errors')
          
              {{ csrf_field() }}

              <img class="logo" src="/img/small-logo.png" alt="">

              <u2f-connector
                :authdatas="{{ json_encode($authenticationData) }}"
                :method="'login'"
                :callbackurl="{{ json_encode(url()->current()) }}">
              </u2f-connector>

            </div>
          </div>
        </div>

        @if (app('pragmarx.google2fa')->isActivated())
          <div class="row">
            <div class="col-xs-12 col-md-6 col-md-offset-3 col-md-offset-3-right">
              <div class="signup-box">
                @include ('partials.auth.validate2fa')
              </div>
            </div>
          </div>
        @endif

      </form>
    </div>

  </body>
  <script src="{{ mix('js/u2f-api.js') }}" type="text/javascript"></script>
@endsection
