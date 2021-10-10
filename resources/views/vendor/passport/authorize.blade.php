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
            <h2>Authorization Request</h2>
            <p><strong>{{ $client->name }}</strong> is requesting permission to access your account.</p>

            <!-- Scope List -->
            @if (count($scopes) > 0)
              <div class="scopes">
                <p><strong>This application will be able to:</strong></p>

                <ul>
                  @foreach ($scopes as $scope)
                    <li>{{ $scope->description }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <!-- Authorize Button -->
            <form method="post" action="{{ route('passport.authorizations.approve') }}">
              @csrf

              <input type="hidden" name="state" value="{{ $request->state }}">
              <input type="hidden" name="client_id" value="{{ $client->id }}">
              <input type="hidden" name="auth_token" value="{{ $authToken }}">
              <button type="submit" class="btn btn-success btn-approve">Authorize</button>
            </form>

            <!-- Cancel Button -->
            <form method="post" action="{{ route('passport.authorizations.deny') }}">
              @csrf
              @method('DELETE')

              <input type="hidden" name="state" value="{{ $request->state }}">
              <input type="hidden" name="client_id" value="{{ $client->id }}">
              <input type="hidden" name="auth_token" value="{{ $authToken }}">
              <button class="btn btn-danger">Cancel</button>
            </form>

          </div>
        </div>
      </div>
    </div>
  </body>
@endsection

