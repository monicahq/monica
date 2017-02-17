@extends('layouts.skeleton')

@section('content')

<div class="create-people modal">

  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">
      <div class="col-xs-12 col-sm-6 col-sm-offset-3">

        <h2>{{ trans('people.people_add_title') }}</h2>

        <form action="/people" method="POST">
          {{ csrf_field() }}

          <fieldset class="form-group">
            <label class="form-check-inline">
              <input type="radio" class="form-check-input" name="gender" id="male" value="male" checked>
              {{ trans('people.significant_other_add_male') }}
            </label>

            <label class="form-check-inline">
              <input type="radio" class="form-check-input" name="gender" id="female" value="female">
              {{ trans('people.significant_other_add_female') }}
            </label>
          </fieldset>

          <dl class="form-group {{ $errors->has('first_name') ? ' errored' : '' }}">
            <dt><label for="first_name">{{ trans('people.people_add_firstname') }}</label></dt>
            <dd><input type="text" class="form-control" name="first_name" placeholder="" autofocus  value="{{ old('first_name') }}"></dd>
            @if ($errors->has('first_name'))
            <dd class="error">{{ $errors->first('first_name') }}</dd>
            @endif

            <dt><label for="middle_name">{{ trans('people.people_add_middlename') }}</label></dt>
            <dd><input type="text" class="form-control" name="middle_name" placeholder="" value="{{ old('middle_name') }}"></dd>
            @if ($errors->has('middle_name'))
            <dd class="error">{{ $errors->first('middle_name') }}</dd>
            @endif

            <dt><label for="last_name">{{ trans('people.people_add_lastname') }}</label></dt>
            <dd><input type="text" class="form-control" name="last_name" placeholder="" value="{{ old('last_name') }}"></dd>
            @if ($errors->has('last_name'))
            <dd class="error">{{ $errors->first('last_name') }}</dd>
            @endif
          </dl>

          <button class="btn btn-primary" type="submit">{{ trans('people.people_add_cta') }}</button>
          <a href="/people" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection
