@extends('layouts.skeleton')

@section('content')

<section class="ph3 ph0-ns">

  {{-- Breadcrumb --}}
  <div class="mt4 mw7 center mb3">
    <p><a href="{{ route('people.show', $contact) }}">&lt; {{ $contact->name }}</a></p>
    <div class="mt4 mw7 center mb3">
      <h3 class="f3 fw5">{{ trans('people.work_edit_title', ['name' => $contact->first_name]) }}</h3>
    </div>
  </div>

  <div class="mw7 center br3 ba b--gray-monica bg-white mb6">
    <form method="POST" action="{{ route('people.work.update', $contact) }}" enctype="multipart/form-data">
      @csrf
      @include('partials.errors')

      {{-- Job --}}
      <div class="pa4-ns ph3 pv2 mb3 mb0-ns bb b--gray-monica">
        <label for="job">{{ trans('people.work_edit_job') }}</label>
        <input type="text" class="form-control" name="job" id="job" value="{{ $contact->job }}" autofocus>
      </div>

      {{-- Company --}}
      <div class="pa4-ns ph3 pv2 mb3 mb0-ns bb b--gray-monica">
        <label for="existingCompany">Company</label>
        <select class="form-control" name="existingCompany" id="existingCompany">
          <option value="">Choose an existing company</option>
          @foreach ($companies as $company)
            <option value="{{ $company['name'] }}">
              {{ $company['name'] }}
            </option>
          @endforeach
        </select>

        {{-- Create a new company --}}
        <label for="newCompany">or {{ trans('people.work_edit_company') }}</label>
        <input type="text" class="form-control" name="newCompany" id="newCompany" value="{{ $contact->company }}">
      </div>

      {{-- Form actions --}}
      <div class="ph4-ns ph3 pv3">
        <div class="flex-ns justify-between">
          <div>
            <a href="{{ route ('people.show', $contact) }}" class="btn btn-secondary w-auto-ns w-100 mb2 pb0-ns">{{ trans('app.cancel') }}</a>
          </div>
          <div>
            <button class="btn btn-primary w-auto-ns w-100 mb2 pb0-ns" name="save" type="submit">{{ trans('app.save') }}</button>
          </div>
        </div>
      </div>
    </form>
  </div>
</section>

@endsection
