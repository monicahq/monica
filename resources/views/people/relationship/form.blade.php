  <form method="POST" action="{{ $action }}">
    {{ method_field($method) }}
    {{ csrf_field() }}

    @include('partials.errors')

    {{-- First name --}}
    <div class="form-group">
      <label for="first_name">{{ trans('people.significant_other_add_firstname') }}</label>
      <input type="text" class="form-control" name="first_name" id="first_name" maxlength="254" value="{{ old('first_name') ?? $partner->first_name }}" autofocus required>
    </div>

    <div class="form-group">
      <label for="last_name">{{ trans('people.information_edit_lastname') }}</label>
      <input type="text" class="form-control" name="last_name" id="last_name" maxlength="254" value="{{ old('last_name') ?? $partner->last_name }}">
    </div>

    {{-- Gender --}}
    <fieldset class="form-group">
      <div class="mb3 mb0-ns">
        <form-select
          :options="{{ $genders }}"
          v-bind:required="true"
          v-bind:title="'{{ trans('people.people_add_gender') }}'"
          v-bind:id="'gender_id'">
        </form-select>
      </div>
    </fieldset>

    <fieldset class="form-group dates">

      {{-- Don't know the birthdate --}}
      <div class="form-check">
        <label class="form-check-label" for="birthdateApproximate_unknown">
          <input type="radio" class="form-check-input" name="birthdate" id="birthdateApproximate_unknown" value="unknown" {{ is_null($partner->birthday_special_date_id) ? 'checked' : '' }}>

          <div class="form-inline">
            {{ trans('people.significant_other_add_unknown') }}
          </div>
        </label>
      </div>

      {{-- Approximate birthdate --}}
      <div class="form-check">
        <label class="form-check-label" for="birthdateApproximate_approximate">
          <input type="radio" class="form-check-input" name="birthdate" id="birthdateApproximate_approximate" value="approximate" {{ is_null($partner->birthday_special_date_id) ? '' : ($partner->birthdate->is_age_based ? 'checked' : '') }}>

          <div class="form-inline">
            {{ trans('people.information_edit_probably') }}

            <input type="number" class="form-control" name="age" id="age"
                    value="{{ (is_null($partner->birthdate)) ? 1 : $partner->birthdate->getAge() }}"
                    min="0"
                    max="120">
          </div>
        </label>
      </div>

      {{-- Exact birthdate --}}
      <div class="form-check">
        <label class="form-check-label" for="birthdateApproximate_exact">
            <input type="radio" class="form-check-input" name="birthdate" id="birthdateApproximate_exact" value="exact" {{ is_null($partner->birthday_special_date_id) ? '' : ($partner->birthdate->is_age_based ? '' : 'checked') }}>

            <div class="form-inline">
              {{ trans('people.information_edit_exact') }}

              @include('partials.components.date-select', ['contact' => $partner, 'specialDate' => $partner->birthdate, 'class' => 'birthdate'])
            </div>
        </label>
      </div>
      <p class="help">{{ trans('people.information_edit_help') }}</p>
    </fieldset>

    @if (\Route::currentRouteName() == 'people.relationships.add' or (\Route::currentRouteName() == 'people.relationships.edit' and $partner->is_partial == 1))
    <fieldset class="form-group">
      <label class="form-check-inline real-contact-checkbox" for="realContact">
        <input type="checkbox" class="form-check-input" name="realContact" id="realContact">
        {{ trans('people.contact_add_also_create_contact') }}
        <span class="help">{{ trans('people.contact_add_add_description') }}</span>
      </label>
    </fieldset>
    @endif

    <div class="form-group actions">
      <button type="submit" class="btn btn-primary">{{ $buttonText }}</button>
      <a href="{{ route('people.show', $contact) }}" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
    </div>
  </form>
