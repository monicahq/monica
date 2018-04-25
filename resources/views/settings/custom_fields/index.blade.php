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
              {{ trans('app.breadcrumb_settings_personalization') }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">

      @include('settings._sidebar')

      <div class="col-xs-12 col-sm-9">

        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">
            <h3 class="mb3">
              Custom fields
              <a class="btn nt2" href="/settings/customfields/new">{{ trans('settings.personalization_genders_add') }}</a>
            </h3>
            <p>
              Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec a diam lectus. Sed sit amet ipsum mauris. Maecenas congue ligula ac quam viverra nec consectetur ante hendrerit. Donec et mollis dolor. Praesent et diam eget libero egestas mattis sit amet vitae augue. Nam tincidunt congue enim, ut porta lorem lacinia consectetur. Donec ut libero sed arcu vehicula ultricies a non tortor.
            </p>

            <div class="mt3 mb3 form-information-message br2">
              <div class="pa3 flex">
                <div class="mr3">
                  <svg viewBox="0 0 20 20"><g fill-rule="evenodd"><circle cx="10" cy="10" r="9" fill="currentColor"></circle><path d="M10 0C4.486 0 0 4.486 0 10s4.486 10 10 10 10-4.486 10-10S15.514 0 10 0m0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8m1-5v-3a1 1 0 0 0-1-1H9a1 1 0 1 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2m-1-5.9a1.1 1.1 0 1 0 0-2.2 1.1 1.1 0 0 0 0 2.2"></path></g></svg>
                </div>
                <div class="">
                  {{ trans('settings.personalisation_paid_upgrade') }}
                </div>
              </div>
            </div>

            <div class="dt dt--fixed w-100 collapse br--top br--bottom">

              <div class="dt-row">
                <div class="dtc">
                  <div class="pa2 b">
                    {{ trans('settings.personalization_contact_field_type_table_name') }}
                  </div>
                </div>
                <div class="dtc">
                  <div class="pa2 b">
                    {{ trans('settings.personalization_contact_field_type_table_actions') }}
                  </div>
                </div>
              </div>

              <div class="dt-row bb b--light-gray">
                <div class="dtc">
                  <div class="pa2">
                  </div>
                </div>
                <div class="dtc">
                  <div class="pa2">
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
