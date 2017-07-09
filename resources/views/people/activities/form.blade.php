<form method="POST" action="{{ $action }}">
    {{ method_field($method) }}
    {{ csrf_field() }}

    <h2>{{ trans('people.activities_add_title', ['name' => $contact->getFirstName()]) }}</h2>

    {{-- Summary --}}
    <div class="form-group{{ $errors->has('summary') ? ' has-error' : '' }}">
        <label for="summary">{{ trans('people.activities_summary') }}</label>
        <input type="text" id="summary" class="form-control" name="summary" autofocus required maxlength="254" value="{{ old('summary') ?? $activity->summary }}">
        @if ($errors->has('summary'))
            <span class="help-block">
                <strong>{{ $errors->first('summary') }}</strong>
            </span>
        @endif
    </div>

    {{-- Date --}}
    <div class="form-group{{ $errors->has('date_it_happened') ? ' has-error' : '' }}">
        <label for="date_it_happened">{{ trans('people.activities_add_date_occured') }}</label>
        <input type="date" id="date_it_happened" name="date_it_happened" class="form-control"
               value="{{ old('date_it_happened') ?? $activity->date_it_happened->format('Y-m-d') ?? \Carbon\Carbon::now(Auth::user()->timezone)->format('Y-m-d') }}"
               min="{{ \Carbon\Carbon::now(Auth::user()->timezone)->subYears(10)->format('Y-m-d') }}"
               max="{{ \Carbon\Carbon::now(Auth::user()->timezone)->format('Y-m-d') }}"
        >
        @if ($errors->has('date_it_happened'))
            <span class="help-block">
                <strong>{{ $errors->first('date_it_happened') }}</strong>
            </span>
        @endif
    </div>

    {{-- Build the Activity types dropdown --}}
    <div class="form-group{{ $errors->has('activity_type_id') ? ' has-error' : '' }}">
        <label for="activity_type_id">{{ trans('people.activities_add_pick_activity') }}</label>
        <select id="activity_type_id" name="activity_type_id" class="form-control" required>

            {{-- Blank option --}}
            <option value="0" selected>
                -
            </option>

            {{-- Predefined options --}}
            @foreach (App\ActivityTypeGroup::all() as $activityTypeGroup)
                <optgroup label="{{ trans('people.activity_type_group_'.$activityTypeGroup->key) }}">
                    @foreach (App\ActivityType::where('activity_type_group_id', $activityTypeGroup->id)->get() as $activityType)
                        <option value="{{ $activityType->id }}">
                            {{ trans('people.activity_type_'.$activityType->key) }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @if ($errors->has('activity_type_id'))
            <span class="help-block">
                <strong>{{ $errors->first('activity_type_id') }}</strong>
            </span>
        @endif
    </div>

    <div class="form-group{{ $errors->has('description') ? ' has-error' : '' }}">
        <label for="description">{{ trans('people.activities_add_optional_comment') }}</label>
        <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') ?? $activity->description }}</textarea>
        @if ($errors->has('description'))
            <span class="help-block">
                <strong>{{ $errors->first('description') }}</strong>
            </span>
        @endif
    </div>

    <div class="form-group actions">
        <button type="submit" class="btn btn-primary">{{ trans('people.activities_add_cta') }}</button>
        <a href="/people/{{ $contact->id }}" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
    </div>
</form>