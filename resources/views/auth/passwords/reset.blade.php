@extends('marketing.skeleton')

@section('content')
  <body class="marketing register">
    <div class="container">

        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="card">
                  <div class="card-block">
                    <div class="card-title">{{ trans('auth.password_reset_title') }}</div>

                    <div class="panel-body">
                        <form class="form-horizontal" role="form" method="POST" action="{{ '/password/reset' }}">
                            {{ csrf_field() }}

                            <input type="hidden" name="token" value="{{ $token }}">

                            <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                                <label for="email" class="col-md-4 control-label">{{ trans('auth.password_reset_email') }}</label>

                                <div class="col-md-6">
                                    <input id="email" type="email" class="form-control" name="email" value="{{ $email or old('email') }}">

                                    @if ($errors->has('email'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('email') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                                <label for="password" class="col-md-4 control-label">{{ trans('auth.password_reset_password') }}</label>

                                <div class="col-md-6">
                                    <input id="password" type="password" class="form-control" name="password">

                                    @if ($errors->has('password'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('password') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group{{ $errors->has('password_confirmation') ? ' has-error' : '' }}">
                                <label for="password-confirm" class="col-md-4 control-label">{{ trans('auth.password_reset_password_confirm') }}</label>
                                <div class="col-md-6">
                                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation">

                                    @if ($errors->has('password_confirmation'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('password_confirmation') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-md-6 col-md-offset-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-btn fa-refresh"></i>&nbsp;{{ trans('auth.password_reset_action') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
              </div>
            </div>
        </div>

    </div>
  </body>
@endsection
