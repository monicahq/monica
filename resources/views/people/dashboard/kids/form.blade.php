<form method="POST" action="{{ $action }}">
  {{ method_field($method) }}
  {{ csrf_field() }}

  @include('partials.errors')

  {{-- First name --}}
  <div class="form-group">
    <label for="first_name">{{ trans('people.kids_add_firstname') }}</label>
    <input type="text" id="first_name" class="form-control" name="first_name" maxlength="254" value="{{ old('first_name') ?? $kid->first_name }}" autofocus required>
  </div>

  <div class="form-group">
    <label for="last_name">{{ trans('people.kids_add_lastname') }}</label>
    <input type="text" class="form-control" name="last_name" id="last_name" maxlength="254" value="{{ old('last_name') ?? $kid->last_name }}">
  </div>

  {{-- Gender --}}
  <label>{{ trans('people.people_add_gender') }}</label>
  <fieldset class="form-group">
    <label class="form-check-inline" for="genderNone">
      <input type="radio" class="form-check-input" name="gender" id="genderNone" value="none" @if(! in_array(old('gender'), ['male', 'female']) || ! in_array($kid->gender, ['male', 'female'])) checked @endif>
      {{ trans('app.gender_none') }}
    </label>

    <label class="form-check-inline" for="genderMale">
      <input type="radio" class="form-check-input" name="gender" id="genderMale" value="male" @if(old('gender') === 'male' || $kid->gender === 'male') checked @endif>
      {{ trans('people.kids_add_boy') }}
    </label>

    <label class="form-check-inline" for="genderFemale">
      <input type="radio" class="form-check-input" name="gender" id="genderFemale" value="female" @if(old('gender') === 'female' || $kid->gender === 'female') checked @endif>
      {{ trans('people.kids_add_girl') }}
    </label>
  </fieldset>

  <fieldset class="form-group dates">

    {{-- Don't know the birthdate --}}
    <div class="form-check">
      <label class="form-check-label" for="is_birthdate_approximate_unknown">
        <input type="radio" class="form-check-input" name="is_birthdate_approximate" id="is_birthdate_approximate_unknown" value="unknown"
        @if(! in_array(old('is_birthdate_approximate'), ['approximate', 'exact']) || ! in_array($kid->is_birthdate_approximate, ['approximate', 'exact'])) checked @endif
        >

        <div class="form-inline">
          {{ trans('people.significant_other_add_unknown') }}
        </div>
      </label>
    </div>

    {{-- Approximate birthdate --}}
    <div class="form-check">
      <label class="form-check-label" for="is_birthdate_approximate_approximate">
        <input type="radio" class="form-check-input" name="is_birthdate_approximate" id="is_birthdate_approximate_approximate" value="approximate"
        @if(old('is_birthdate_approximate') === 'approximate' || $kid->is_birthdate_approximate === 'approximate') checked @endif
        >

        <div class="form-inline">
          {{ trans('people.kids_add_probably') }}

          <input type="number" class="form-control" id="age" name="age" value="{{ old('age') ?? $kid->getAge() ?? 1 }}" min="0" max="99">

          {{ trans('people.kids_add_probably_yo') }}
        </div>
      </label>
    </div>

    {{-- Exact birthdate --}}
    <div class="form-check">
      <label class="form-check-label" for="is_birthdate_approximate_exact">
        <input type="radio" class="form-check-input" name="is_birthdate_approximate" id="is_birthdate_approximate_exact" value="exact"
        @if(old('is_birthdate_approximate') === 'exact' || $kid->is_birthdate_approximate === 'exact') checked @endif
        >

        <div class="form-inline">
          {{ trans('people.kids_add_exact') }}
          <input type="date" name="birthdate" class="form-control" id="specificDate"
          value="{{ old('birthdate') ?? (! is_null($kid->birthdate) ? $kid->birthdate->format('Y-m-d') : \Carbon\Carbon::now(auth()->user()->timezone)->format('Y-m-d')) ?? '' }}"
          min="{{ \Carbon\Carbon::now(auth()->user()->timezone)->subYears(120)->format('Y-m-d') }}"
          max="{{ \Carbon\Carbon::now(auth()->user()->timezone)->format('Y-m-d') }}">
        </div>
      </label>
    </div>

    <div class="hint-reminder">
      <p>{{ trans('people.kids_add_help') }}</p>
    </div>
  </fieldset>

  @if (\Route::currentRouteName() == 'people.kids.add')
    <fieldset class="form-group">
      <label class="form-check-inline real-contact-checkbox" for="realContact">
        <input type="checkbox" class="form-check-input" name="realContact" id="realContact">
        {{ trans('people.kids_add_also_create') }}
        <span class="help">{{ trans('people.kids_add_also_desc') }}</span>
      </label>
    </fieldset>
    @endif

  <div class="form-group actions">
    <button type="submit" class="btn btn-primary">{{ $buttonText }}</button>
    <a href="{{ route('people.show', $contact) }}" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
  </div> <!-- .form-group -->
</form>
