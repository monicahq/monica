@extends('layouts.skeleton')

@section('content')
  <div class="people-show introductions">

    {{-- Breadcrumb --}}
    <div class="breadcrumb">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-12">
            <ul class="horizontal">
              <li>
                <a href="{{ route('dashboard.index') }}">{{ trans('app.breadcrumb_dashboard') }}</a>
              </li>
              <li>
                <a href="{{ route('people.index') }}">{{ trans('app.breadcrumb_list_contacts') }}</a>
              </li>
              <li>
                <a href="{{ route('people.show', $contact) }}">{{ $contact->name }}</a>
              </li>
              <li>
                {{ trans('app.breadcrumb_edit_introductions') }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Page content -->
    <div class="main-content central-form">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-12 col-sm-6 offset-sm-3 offset-sm-3-right">
            <h2>{{ trans('people.introductions_title_edit', ['name' => $contact->first_name]) }}</h2>

              <form method="POST" action="{{ route('people.introductions.update', $contact) }}">
                @csrf

                @include('partials.errors')

                {{-- How did they meet --}}
                <div class="form-group">
                  <label for="first_met_additional_info">{{ trans('people.introductions_additional_info') }}</label>
                  <textarea class="form-control" id="first_met_additional_info" name="first_met_additional_info" rows="3">{{ old('first_met_additional_info') ?? $contact->first_met_additional_info }}</textarea>
                </div>

                <div class="form-group">
                  <label for="metThroughId">{{ trans('people.introductions_edit_met_through') }}</label>
                  <select class="form-control" name="metThroughId" id="metThroughId">
                    <option value="">{{ trans('people.introductions_no_met_through') }}</option>
                    @foreach (auth()->user()->account->contacts()->real()->active()->get() as $metThroughContact)

                      @if ($metThroughContact->id != $contact->id)
                      <option value="{{ $metThroughContact->id }}" {{ (is_null($contact->first_met_through_contact_id)) ? '' : (($metThroughContact->id == $contact->first_met_through_contact_id) ? 'selected' : '') }}>
                        {{ $metThroughContact->name }}
                      </option>
                      @endif

                    @endforeach
                  </select>
                </div>

                <label>{{ trans('people.introductions_first_met_date') }}</label>
                <fieldset class="form-group dates">

                  {{-- You don't know the date you've met --}}
                  <div class="form-check">
                    <label class="form-check-label" for="is_first_met_date_unknown">
                      <input type="radio" class="form-check-input" name="is_first_met_date_known" id="is_first_met_date_unknown" value="unknown"
                      v-model="date_met_the_contact" value="unknown"
                      {{ (is_null($contact->first_met)) ? 'checked' : '' }}
                      >

                      <div class="form-inline">
                        {{ trans('people.introductions_no_first_met_date') }}
                      </div>
                    </label>
                  </div>

                  {{-- You know the date you've met --}}
                  <div class="form-check">
                    <label class="form-check-label" for="is_first_met_date_known">
                      <input type="radio" class="form-check-input" name="is_first_met_date_known" id="is_first_met_date_known" value="known" v-model="date_met_the_contact" value="known"
                      {{ (! is_null($contact->first_met)) ? 'checked' : '' }}
                      >

                      {{ trans('people.introductions_first_met_date_known') }}
                      @include('partials.components.date-select', ['contact' => $contact, 'specialDate' => $contact->firstMetDate ?? now(\App\Helpers\DateHelper::getTimezone()), 'class' => 'first_met'])
                    </label>
                  </div>
                </fieldset>

                <fieldset class="form-group" v-if="date_met_the_contact == 'known'">
                  <form-checkbox
                    :name="'addReminder'"
                    :dclass="'form-check form-check-label'"
                    :iclass="'form-check-input'"
                  >
                    {{ trans('people.introductions_add_reminder') }}
                  </form-checkbox>
                </fieldset>

                <div class="form-group actions">
                  <button type="submit" class="btn btn-primary">{{ trans('app.save') }}</button>
                  <a href="{{ route('people.show', $contact) }}" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
                </div>
              </form>

            </div>

          </div>
        </div>
      </div>
    </div>

  </div>
@endsection
