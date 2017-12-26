@extends('layouts.skeleton')

@section('content')

<div class="settings">

  {{-- Breadcrumb --}}
  <div class="breadcrumb">
    <div class="{{ Auth::user()->getFluidLayout() }}">
      <div class="row">
        <div class="col-xs-12">
          <ul class="horizontal">
            <li>
              <a href="/dashboard">{{ trans('app.breadcrumb_dashboard') }}</a>
            </li>
            <li>
              <a href="/settings">{{ trans('app.breadcrumb_settings') }}</a>
            </li>
            <li>
              2FA
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">

    <div class="panel-heading">2FA Secret Key</div>

<form action="/settings/2fa/enable" method="POST">
          {{ csrf_field() }}

          <div class="panel-body">
    Open up your 2FA mobile app and scan the following QR barcode:
    <br />
    <img alt="Image of QR barcode" src="{{ $image }}" />

    <br />
    If your 2FA mobile app does not support QR barcodes, 
    enter in the following number: <code>{{ $secret }}</code>
    <br /><br />
</div>

          {{-- code --}}
              <div class="form-group">
                <label for="one_time_password">Enter code</label>
                <input type="number" class="form-control" id="one_time_password" name="one_time_password" />
              </div>

          <button type="submit" class="btn">Validate</button>
        </form>


    </div>
  </div>
</div>

@endsection
