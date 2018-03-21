@extends('layouts.skeleton')

@section('content')
<script src="{{ mix('js/u2f-api.js') }}" type="text/javascript"></script>
<body class="marketing register">
    <div class="container">
          <div class="row">
            <div class="col-xs-12 col-md-6 col-md-offset-3">
                <div class="signup-box">
      
              <img class="logo" src="/img/small-logo.png" alt="">
              <h2>{{ trans('auth.2fa_title') }}</h2>

              @include ('partials.errors')

              <u2f-connector
                :authdatas="{{ json_encode($authenticationData) }}"
                :method="'login'">
              </u2f-connector>

              </div>
            </div>
          </div>
      </div>
    </div>

</body>
@endsection
