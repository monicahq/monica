@extends('layouts.skeleton')

@section('content')
  <div class="people-show significantother">

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
                {{ trans('app.breadcrumb_add_significant_other') }}
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
            <h2>{{ trans('people.significant_other_add_title', ['name' => $contact->getFirstName()]) }}</h2>

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

                @include('people.relationship.form', [
                  'method' => 'POST',
                  'action' => route('people.relationships.store', $contact),
                  'actionExisting' => route('people.relationships.storeexisting', $contact),
                  'buttonText' => trans('people.significant_other_add_cta')
                ])

              </div>

              {{-- Existing contact entry --}}
              <div class="tab-pane" id="existing" role="tabpanel">

                @if (count($contact->getPotentialContacts()) == 0)

                  <div class="significant-other-blank-state">
                    <img src="/img/people/no_record_found.svg">
                    <p>{{ trans('people.significant_other_add_no_existing_contact', ['name' => $contact->getFirstName()]) }}</p>
                  </div>

                @else

                  <form method="POST" action="{{ route('people.relationships.storeexisting', $contact) }}">
                    {{ csrf_field() }}

                    @include('partials.errors')

                    <div class="form-group">
                      <label for="existingPartner">{{ trans('people.significant_other_add_existing_contact', ['name' => $contact->getFirstName()]) }}</label>
                      <select class="form-control" name="existingPartner" id="existingPartner">
                        @foreach ($contact->getPotentialContacts() as $partner)

                          <option value="{{ $partner->id }}">{{ $partner->getCompleteName(auth()->user()->name_order) }}</option>

                        @endforeach
                      </select>
                    </div>

                    <div class="form-group actions">
                      <button type="submit" class="btn btn-primary">{{ trans('people.significant_other_add_cta') }}</button>
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
