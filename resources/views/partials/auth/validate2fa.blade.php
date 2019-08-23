@if ($errors->has('totp'))
  <span class="help-block">
    <strong>{{ $errors->first('totp') }}</strong>
  </span>
@endif
<div class="form-group">
  <label for="one_time_password">{{ trans('auth.2fa_one_time_password') }}</label>
  <form-input :input-type="'number'"
            :id="'one_time_password'"
            :width="100"
            :required="true"></form-input>
  <small>{{ trans('auth.2fa_otp_help') }}</small>
</div>

{{-- TODO
<div class="form-group checkbox">
  <input type="checkbox" name="remember" id="remember" />
  <label for="remember">Remember me on this browser</label>
</div>
--}}

{{-- TODO
<div class="form-group">
  {{ trans('auth.2fa_recuperation_code') }}
</div>
--}}

<div class="row">
  <div class="col-12 col-md-6">
    <div class="form-group actions">
      <button type="submit" name="verify" class="btn btn-primary">{{ trans('app.verify') }}</button>
    </div>
  </div>
  <div class="col-12 col-md-6">
    <div class="form-group actions">
      <a href="logout" class="btn action">{{ trans('app.cancel') }}</a>
    </div>
  </div>
</div>
