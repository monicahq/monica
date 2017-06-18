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
                <a href="/settings/users">{{ trans('app.breadcrumb_settings_users') }}</a>
                </li>
                <li>
                  {{ trans('app.breadcrumb_settings_users_add') }}
                </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Page content -->
    <div class="main-content modal">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12 col-sm-6 col-sm-offset-3">
            <form method="POST" action="/settings/users/save">
              {{ csrf_field() }}

              <h2>Invite a new user to your account</h2>

              <p>This person will have the same rights as you do, including inviting other users and deleting them (including you). Therefore, make sure you trust this person.</p>

              @include('partials.errors')

              {{-- Email --}}
              <fieldset class="form-group">
                <div class="form-group">
                  <label for="email">Enter the email of the person you want to invite</label>
                  <input type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>
                </div>
              </fieldset>

              {{-- Explicit confirmation --}}

              <div class="warning-zone">
                <label class="form-check-label">
                  <input class="form-check-input" type="checkbox" name="confirmation" value="1" v-model="accept_invite_user">
                    I confirm that I want to invite this user to my account. This person will access ALL my data and see exactly what I see.
                </label>
              </div>

              <div class="form-group actions">
                <button type="submit" class="btn btn-primary" :disabled="!accept_invite_user">Invite user</button>
                <a href="/settings/users" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
              </div>
            </form>

          </div>
        </div>
      </div>
    </div>

  </div>
@endsection
