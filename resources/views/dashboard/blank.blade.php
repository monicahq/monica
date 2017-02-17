@extends('layouts.skeleton')

@section('content')
  <div class="people-show activities">

    <!-- Page content -->
    <div class="main-content activities">

      <div class="activities-list blank-people-state">
        <div class="{{ Auth::user()->getFluidLayout() }}">
          <div class="row">
            <div class="col-xs-12">
              <h3>{{ trans('dashboard.blank_title') }}</h3>
              <div class="cta-blank">
                <a href="/people/add" class="btn btn-primary">{{ trans('dashboard.blank_cta') }}</a>
              </div>
              <div class="illustration-blank">
                <img src="/img/people/blank.svg">
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

  </div>
@endsection
