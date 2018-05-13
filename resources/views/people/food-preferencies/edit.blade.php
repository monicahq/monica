@extends('layouts.skeleton')

@section('content')
  <div class="people-show">

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
                {{ $contact->getCompleteName(auth()->user()->name_order) }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Page header -->
    @include('people._header')

    <!-- Page content -->
    <div class="main-content food-preferencies central-form">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12 col-sm-6 col-sm-offset-3 col-sm-offset-3-right">
            <form method="POST" action="/people/{{ $contact->id }}/food/save">
              {{ csrf_field() }}

              <h2>{{ trans('people.food_preferencies_edit_title') }}</h2>

              @include('partials.errors')

              <p>
                @if (is_null($contact->last_name))
                {{ trans('people.food_preferencies_edit_description_no_last_name', ['firstname' => $contact->first_name]) }}</p>
                @else
                {{ trans('people.food_preferencies_edit_description', ['firstname' => $contact->first_name, 'family' => $contact->last_name]) }}</p>
                @endif

              <div class="form-group">
                <textarea class="form-control" id="food" name="food" rows="3">{{ $contact->food_preferencies }}</textarea>
              </div>

              <div class="form-group actions">
                <button type="submit" class="btn btn-primary">{{ trans('people.food_preferencies_edit_cta') }}</button>
                <a href="{{ route('people.show', $contact) }}" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
@endsection
