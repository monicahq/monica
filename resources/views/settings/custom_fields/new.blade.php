@extends('layouts.skeleton')

@section('content')
  <section class="ph3 ph0-ns">

    {{-- Breadcrumb --}}
    <div class="mt4 mw7 center mb3">
      <p><a href="{{ url('/settings/customfields') }}">< Back to Settings</a></p>
      <h3 class="f3 fw5">Add a new custom field</h3>
    </div>

    <custom-fields custom-field-types="{{ $customFieldTypes }}"></custom-fields>

  </div>
@endsection
