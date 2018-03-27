@extends('layouts.skeleton')

@section('content')

<div class="create-people central-form">

  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">
      <div class="col-xs-12 col-sm-6 col-sm-offset-3">

        @if (session('status'))
          <div class="alert alert-success">
              {{ session('status') }}
          </div>
      @endif

        <h2>{{ trans('people.people_add_missing') }}</h2>

        @if (! auth()->user()->account->hasLimitations())
          <p class="import">{!! trans('people.people_add_import', ['url' => '/settings/import']) !!}</p>
        @endif

        <form action="/people" method="POST">
          {{ csrf_field() }}

          {{-- This check is for the cultures that are used to say the last name first --}}
          @if (auth()->user()->name_order == 'firstname_first')

          <dl class="form-group {{ $errors->has('first_name') ? ' errored' : '' }}">
            <dt><label for="first_name">{{ trans('people.people_add_firstname') }}</label></dt>
            <dd><input type="text" id="first_name" class="form-control" name="first_name" placeholder="" autofocus required value="{{ old('first_name') }}" autocomplete="off"></dd>
            @if ($errors->has('first_name'))
            <dd class="error">{{ $errors->first('first_name') }}</dd>
            @endif

            <dt><label for="last_name">{{ trans('people.people_add_lastname') }}</label></dt>
            <dd><input type="text" id="last_name" class="form-control" name="last_name" placeholder="" value="{{ old('last_name') }}" autocomplete="off"></dd>
            @if ($errors->has('last_name'))
            <dd class="error">{{ $errors->first('last_name') }}</dd>
            @endif
          </dl>

          @else

          <dl class="form-group {{ $errors->has('first_name') ? ' errored' : '' }}">
            <dt><label for="last_name">{{ trans('people.people_add_lastname') }}</label></dt>
            <dd><input type="text" id="last_name" class="form-control" name="last_name" placeholder="" value="{{ old('last_name') }}" autocomplete="off"></dd>
            @if ($errors->has('last_name'))
            <dd class="error">{{ $errors->first('last_name') }}</dd>
            @endif

            <dt><label for="first_name">{{ trans('people.people_add_firstname') }}</label></dt>
            <dd><input type="text" id="first_name" class="form-control" name="first_name" placeholder="" autofocus  value="{{ old('first_name') }}" autocomplete="off"></dd>
            @if ($errors->has('first_name'))
            <dd class="error">{{ $errors->first('first_name') }}</dd>
            @endif
          </dl>

          @endif

          <label>{{ trans('people.people_add_gender') }}</label>

          <fieldset class="form-group">
            <label class="form-check-inline" for="none">
              <input type="radio" class="form-check-input" name="gender" id="none" value="none" checked>
              {{ trans('app.gender_none') }}
            </label>

            <label class="form-check-inline" for="male">
              <input type="radio" class="form-check-input" name="gender" id="male" value="male">
              {{ trans('app.gender_male') }}
            </label>

            <label class="form-check-inline" for="female">
              <input type="radio" class="form-check-input" name="gender" id="female" value="female">
              {{ trans('app.gender_female') }}
            </label>
          </fieldset>

          <button class="btn btn-primary" name="save" type="submit">{{ trans('people.people_add_cta') }}</button>
          <button class="btn btn-secondary" name="save_and_add_another" type="submit">{{ trans('people.people_save_and_add_another_cta') }}</button>
          <a href="/people" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection
