@extends('layouts.skeleton')

@section('content')
  <div class="people-show kid">

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
                <a href="/people">{{ trans('app.breadcrumb_list_contacts') }}</a>
              </li>
              <li>
                <a href="{{ route('people.show', $contact) }}">{{ $contact->getCompleteName(auth()->user()->name_order) }}</a>
              </li>
              <li>
                  {{ trans('people.kid_add_title') }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Page content -->
    <div class="main-content central-form">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12 col-sm-6 col-sm-offset-3">

            <h2>{{ trans('people.kids_add_title', ['name' => $contact->last_name]) }}</h2>

            <ul class="nav nav-tabs" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#new" role="tab">
                  {{ trans('people.significant_other_add_person') }}
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#existing" role="tab">
                  {{ trans('people.significant_other_link_existing_contact') }}
                </a>
              </li>
            </ul>

            <div class="tab-content">

              {{-- New contact entry --}}
              <div class="tab-pane active" id="new" role="tabpanel">

                @include('people.dashboard.kids.form', [
                  'method' => 'POST',
                  'action' => route('people.kids.store', $contact),
                  'buttonText' => trans('people.kids_add_cta')
                ])

              </div>

              {{-- Existing contact entry --}}
              <div class="tab-pane" id="existing" role="tabpanel">

                @if (count($contact->getPotentialContacts()) == 0)

                  <div class="significant-other-blank-state">
                    <img src="/img/people/no_record_found.svg">
                    <p>{{ trans('people.kids_add_no_existing_contact', ['name' => $contact->first_name]) }}</p>
                  </div>

                @else

                  <form method="POST" action="{{ route('people.kids.storeexisting', $contact) }}">
                    {{ csrf_field() }}

                    @include('partials.errors')

                    <div class="form-group">
                      <label for="existingKid">{{ trans('people.kids_add_existing_contact', ['name' => $contact->first_name]) }}</label>
                      <select class="form-control" name="existingKid" id="existingKid">
                        @foreach ($contact->getPotentialContacts() as $kid)

                          <option value="{{ $kid->id }}">{{ $kid->getCompleteName(auth()->user()->name_order) }}</option>

                        @endforeach
                      </select>
                    </div>

                    <div class="form-group actions">
                      <button type="submit" class="btn btn-primary">{{ trans('people.kids_add_cta') }}</button>
                      <a href="{{ route('people.show', $contact) }}" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
                    </div>
                  </form>

                @endif
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
@endsection
