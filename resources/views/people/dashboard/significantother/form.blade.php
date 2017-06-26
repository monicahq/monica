<form method="POST" action="{{ $action }}">
    {{ method_field($method) }}
    {{ csrf_field() }}

    @include('partials.errors')

    <h2>{{ trans('people.significant_other_add_title', ['name' => $contact->getFirstName()]) }}</h2>

    {{-- First name --}}
    <div class="form-group">
        <label for="first_name">{{ trans('people.significant_other_add_firstname') }}</label>
        <input type="text" class="form-control" name="first_name" id="first_name" maxlength="254" value="{{ old('first_name') ?? $significant_other->first_name }}" autofocus required>
    </div>

    {{-- Gender --}}
    <label>{{ trans('people.people_add_gender') }}</label>
    <fieldset class="form-group">
        <label class="form-check-inline" for="genderNone">
            <input type="radio" class="form-check-input" name="gender" id="genderNone" value="none" @if(! in_array(old('gender'), ['male', 'female']) || ! in_array($significant_other->gender, ['male', 'female'])) checked @endif>
            {{ trans('app.gender_none') }}
        </label>

        <label class="form-check-inline" for="genderMale">
            <input type="radio" class="form-check-input" name="gender" id="genderMale" value="male" @if(old('gender') === 'male' || $significant_other->gender === 'male') checked @endif>
            {{ trans('app.gender_male') }}
        </label>

        <label class="form-check-inline" for="genderFemale">
            <input type="radio" class="form-check-input" name="gender" id="genderFemale" value="female" @if(old('gender') === 'female' || $significant_other->gender === 'female') checked @endif>
            {{ trans('app.gender_female') }}
        </label>
    </fieldset>

    <fieldset class="form-group dates">

        {{-- Don't know the birthdate --}}
        <div class="form-check" for="is_birthdate_approximate_unknown">
            <label class="form-check-label">
                <input type="radio" class="form-check-input" name="is_birthdate_approximate" id="is_birthdate_approximate_unknown" value="unknown"
                       @if(! in_array(old('is_birthdate_approximate'), ['approximate', 'exact']) || ! in_array($significant_other->is_birthdate_approximate, ['approximate', 'exact'])) checked @endif
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
                       @if(old('is_birthdate_approximate') === 'approximate' || $significant_other->is_birthdate_approximate === 'approximate') checked @endif
                >

                <div class="form-inline">
                    {{ trans('people.significant_other_add_probably') }}

                    <input type="number" class="form-control" name="age" value="{{ old('age') ?? $significant_other->age ?? 1 }}" min="1" max="99">

                    {{ trans('people.significant_other_add_probably_yo') }}
                </div>
            </label>
        </div>

        {{-- Exact birthdate --}}
        <div class="form-check">
            <label class="form-check-label" for="is_birthdate_approximate_exact">
                <input type="radio" class="form-check-input" name="is_birthdate_approximate" id="is_birthdate_approximate_exact" value="exact"
                       @if(old('is_birthdate_approximate') === 'exact' || $significant_other->is_birthdate_approximate === 'exact') checked @endif
                >

                <span class="form-inline">
                    {{ trans('people.significant_other_add_exact') }}
                    <input type="date" name="birthdate" class="form-control"
                           value="{{ old('birthdate') ?? $significant_other->birthdate->format('Y-m-d') ?? \Carbon\Carbon::now(Auth::user()->timezone)->format('Y-m-d') }}"
                           min="{{ \Carbon\Carbon::now(Auth::user()->timezone)->subYears(120)->format('Y-m-d') }}"
                           max="{{ \Carbon\Carbon::now(Auth::user()->timezone)->format('Y-m-d') }}">
                </span>
            </label>
        </div>
    </fieldset>

    <div class="classname">
        <p>{{ trans('people.significant_other_add_help') }}</p>
    </div>

    <div class="form-group actions">
        <button type="submit" class="btn btn-primary">{{ $buttonText }}</button>
        <a href="/people/{{ $contact->id }}" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
    </div> <!-- .form-group -->
</form>
