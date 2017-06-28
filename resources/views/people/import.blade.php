@extends('layouts.skeleton')

@section('content')

<div class="create-people modal">

  <div class="{{ auth()->user()->getFluidLayout() }}">
    <div class="row">
      <div class="col-xs-12 col-sm-6 col-sm-offset-3">

        <h2>Vcard</h2>

        <ul>
            <li>rules</li>
            <li>supports vcard 3.0 format, from Apple Contacts and Google Contacts export</li>
            <li>For now, in case of multiple phone numbers, only the first phone number will be picked up</li>
        </ul>

        @include('partials.errors')

        <form action="/people/storeImport" method="POST" enctype="multipart/form-data">
          {{ csrf_field() }}

            <div class="form-group">
                <label for="vcard">VCARD</label>
                <input type="file" class="form-control-file" name="vcard" id="vcard">
                <small id="fileHelp" class="form-text text-muted">{{ trans('people.information_edit_max_size', ['size' => 10]) }}</small>
            </div>

            <div class="form-group actions">
                <button type="submit" class="btn btn-primary">{{ trans('app.save') }}</button>
                <a href="/people/add" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
            </div> <!-- .form-group -->
        </form>
      </div>
    </div>
  </div>
</div>

@endsection
