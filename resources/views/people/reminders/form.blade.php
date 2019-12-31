<form method="POST" action="{{ $action }}">
    @method($method)
    @csrf

    <h2>{{ trans('people.reminders_add_title', ['name' => $contact->first_name]) }}</h2>

    @include('partials.errors')

    <p>{{ trans('people.reminders_add_description') }}</p>

    {{-- Nature of reminder --}}
    <fieldset class="form-group nature">
        <div class="form-group">
            <input type="text" class="form-control" name="title" value="{{ old('title') ?? $reminder->title }}" required>
        </div>
    </fieldset>

    {{-- Date --}}
    <div class="form-group">
        <label for="initial_date">{{ trans('people.reminders_add_next_time') }}</label>

        @if (is_null($reminder->initial_date))
        <input type="date" id="initial_date" name="initial_date" class="form-control"
               value="{{ old('initial_date') ?? now(\App\Helpers\DateHelper::getTimezone())->toDateString() }}"
               min="{{ now(\App\Helpers\DateHelper::getTimezone())->toDateString() }}"
               max="{{ now(\App\Helpers\DateHelper::getTimezone())->addYears(10)->toDateString() }}"
        >
        @else
        <input type="date" id="initial_date" name="initial_date" class="form-control"
               value="{{ old('initial_date') ?? $reminder->initial_date->toDateString() }}"
               min="{{ now(\App\Helpers\DateHelper::getTimezone())->toDateString() }}"
               max="{{ now(\App\Helpers\DateHelper::getTimezone())->addYears(10)->toDateString() }}"
        >
        @endif

        <fieldset class="form-group frequency{{ $errors->has('frequency_type') ? ' has-error' : '' }}">

            {{-- One time reminder --}}
            <div class="form-check">
                <label class="form-check-label" for="frequency_type_once">
                    <input type="radio" id="frequency_type_once" class="form-check-input" name="frequency_type" value="one_time"
                           v-model="reminders_frequency"
                           :value="'one_time'"
                           :checked="'one_time'"
                    >
                    {{ trans('people.reminders_add_once') }}
                </label>
            </div>

            {{-- Recurring reminder --}}
            <div class="form-check">
                <label class="form-check-label">
                    <input type="radio" class="form-check-input" name="reminderRecurringFrequency" value="recurring"
                           v-model="reminders_frequency"
                           :value="'recurrent'"
                           :checked="'recurrent'"
                    >

                    {{ trans('people.reminders_add_recurrent') }}

                    <input type="number" class="form-control frequency-type" name="frequency_number"
                           value="1"
                           min="1"
                           max="115"
                           :disabled="reminders_frequency == 'one_time'">

                    <select name="frequency_type" :disabled="reminders_frequency == 'one_time'">
                        <option value="week">{{ trans('people.reminders_type_week') }}</option>
                        <option value="month">{{ trans('people.reminders_type_month') }}</option>
                        <option value="year">{{ trans('people.reminders_type_year') }}</option>
                    </select>

                    {{ trans('people.reminders_add_starting_from') }}

                </label>
            </div>
        </fieldset>
    </div>

    <div class="form-group">
        <label for="description">{{ trans('people.reminders_add_optional_comment') }}</label>
        <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') ?? $reminder->description }}</textarea>
    </div>

    <div class="form-group actions">
        <button type="submit" class="btn btn-primary">
            @if($update_or_add == 'add')
            {{ trans('people.reminders_add_cta') }}
            @elseif ($update_or_add == 'edit')
            {{ trans('people.reminders_edit_update_cta') }}
            @endif
        </button>
        <a href="{{ route('people.show', $contact) }}" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
    </div>
</form>
