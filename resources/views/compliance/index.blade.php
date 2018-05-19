@extends('layouts.skeleton')

@section('content')
  <section class="ph3 ph0-ns">

    <div class="mw7 center br3 ba b--gray-monica bg-white mb5">
      <p>Sorry for the interruption.</p>
      <p>We have changed our terms of use and privacy policy.</p>
      <p>Please take some time to review them and either accept them or reject them below.</p>
      <p>If you reject them, you won't be able to use your account anymore. In that case, click here to export your data.</p>
      <form action="/compliance/sign" method="POST">
        {{ csrf_field() }}
        <button class="btn btn-primary" name="save" type="submit">Accept policy</button>
      </form>
    </div>
  </div>
@endsection
