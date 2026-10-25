{{-- What is due about somebody: the deadlines waiting for an answer and the next date of each reminder. --}}
{{-- @var \App\ViewModels\Vaults\ContactDeadlinesBlock $deadlines --}}
@php
  $form = $deadlines->form();
  $state = [
      'filter' => 'all',
      'adding' => $deadlines->isAdding(),
      'cadence' => old('cadence', $form->cadence()),
      'time' => old('time', $form->time()),
      'zone' => old('timezone', $form->timezone()),
      'timeOpen' => $errors->has('time') || $errors->has('timezone'),
      'formUrl' => $deadlines->isFormRendered() ? null : $deadlines->formUrl(),
  ];
@endphp
<div id="contact-reminder-panel" x-merge="replace" x-data="@js($state)"
  @if ($deadlines->isAskedFor()) x-init="$nextTick(() => document.getElementById('contact-reminder-name')?.focus())" @endif
>
  <x-panel :title="__('Reminders')" icon="reminder" :flush="true" :clip="false" x-show="!adding">
    @if ($deadlines->canAdd())
      <x-slot:action>
        <x-button.secondary
          type="button"
          size="small"
          x-ref="addButton"
          :aria-controls="$deadlines->isFormRendered() ? 'contact-reminder-form' : null"
          x-bind:aria-expanded="adding ? 'true' : 'false'"
          x-on:click="formUrl ? $ajax(formUrl, { target: 'contact-reminder-panel' }) : (adding = true, $nextTick(() => document.getElementById('contact-reminder-name').focus()))"
        >{{ __('Add') }}</x-button.secondary>
      </x-slot:action>
    @endif

    @if ($deadlines->holdsNothing())
      <x-empty-state :title="$deadlines->blankTitle()">
        {{ $deadlines->blankLine() }}

        <x-slot:icon>
          <x-icon name="reminder" :size="24" :stroke-width="1.5" />
        </x-slot:icon>
      </x-empty-state>
    @else
      <x-tabs class="px-2">
        @foreach ($deadlines->filters() as $filter)
          <x-tabs.item
            id="contact-deadlines-tab-{{ $filter['key'] }}"
            aria-controls="contact-deadlines-list"
            :selected="$filter['key'] === 'all'"
            :count="$filter['count']"
            x-on:click="filter = '{{ $filter['key'] }}'"
            x-bind:aria-selected="filter === '{{ $filter['key'] }}' ? 'true' : 'false'"
          >{{ $filter['label'] }}</x-tabs.item>
        @endforeach
      </x-tabs>

      <div
        id="contact-deadlines-list"
        role="tabpanel"
        aria-labelledby="contact-deadlines-tab-all"
        x-bind:aria-labelledby="'contact-deadlines-tab-' + filter"
        class="-mt-px"
      >
        @foreach ($deadlines->rows() as $deadline)
          @php
            $row = $deadline['row'];
          @endphp

          <div
            x-show="filter === 'all' || filter === '{{ $deadline['category'] }}'"
            class="flex flex-col gap-2.5 border-t border-hairline px-4 py-3.5"
          >
            <div class="flex items-start gap-3">
              <div class="flex min-w-0 flex-1 flex-col gap-1.25">
                <div class="flex flex-wrap items-center gap-2">
                  @if ($row->reminderUrl() !== null)
                    <a href="{{ $row->reminderUrl() }}" data-turbo="true" class="font-semibold text-pretty text-ink no-underline hover:underline focus-ring rounded-xs">{{ $row->text() }}</a>
                  @else
                    <span class="font-semibold text-pretty text-ink">{{ $row->text() }}</span>
                  @endif

                  @if ($deadline['category'] === 'overdue')
                    <x-badge tone="danger" x-show="filter !== 'overdue'">{{ __('Overdue') }}</x-badge>
                  @endif
                </div>

                <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted">
                  <span class="flex items-center gap-1.5">
                    <x-icon name="clock" :size="15" :stroke-width="1.5" />
                    {{ $row->whenLine() }}
                  </span>

                  <span class="flex items-center gap-1.5">
                    <x-icon name="calendar-repeat" :size="15" :stroke-width="1.5" />
                    {{ $row->cadence() }}
                  </span>
                </div>
              </div>

              @include('app.vaults.contacts.reminders._menu', ['row' => $row])
            </div>

            @if ($row->canRemember() || $row->canForget())
              <div class="flex flex-wrap gap-2">
                @include('app.vaults.reminders._answers', ['row' => $row])
              </div>
            @endif
          </div>
        @endforeach

        @foreach ($deadlines->filters() as $filter)
          @if ($filter['count'] === 0)
            <div x-show="filter === '{{ $filter['key'] }}'" x-cloak class="flex flex-col gap-1 px-4 py-7 text-center">
              <span class="font-semibold text-ink">{{ $deadlines->emptyTitle($filter['key']) }}</span>

              <span class="text-sm text-pretty text-muted">{{ $deadlines->emptyLine($filter['key']) }}</span>
            </div>
          @endif
        @endforeach
      </div>
    @endif

    @unless ($deadlines->holdsNothing())
      <x-slot:footer class="flex justify-end">
        <a
          href="{{ $deadlines->remindersUrl() }}"
          data-turbo="true"
          class="flex items-center gap-1 rounded-xs font-medium text-ink underline decoration-hairline-strong underline-offset-3 hover:decoration-ink focus-ring"
        >
          {{ $deadlines->allLabel() }}

          <x-icon name="chevron-right" :size="15" :stroke-width="1.6" />
        </a>
      </x-slot:footer>
    @endunless
  </x-panel>

  @if ($deadlines->isFormRendered())
    <x-form
      id="contact-reminder-form"
      :action="$form->actionUrl()"
      :method="$form->method()"
      x-ref="form"
      x-target="contact-reminder-panel"
      x-show="adding"
      x-cloak
    >
      <x-panel :title="__('Add a reminder')" :subtitle="$deadlines->aboutLine()" icon="reminder">
        <input type="hidden" name="return_to" value="contact" />

        <div class="flex flex-col gap-4">
          <x-input
            id="contact-reminder-name"
            name="name"
            :label="__('What should we remind you about?')"
            :value="old('name')"
            :placeholder="__('Ask how the new job is going')"
            :error="$errors->get('name')"
            maxlength="255"
            required
            :required-mark="true"
          />

          <div class="grid gap-3 sm:grid-cols-2">
            <x-input
              type="date"
              id="contact-reminder-date"
              name="starts_on"
              :label="__('Date')"
              :value="old('starts_on')"
              :error="$errors->get('starts_on')"
              required
              :required-mark="true"
            />

            <x-select id="contact-reminder-cadence" name="cadence" :label="__('Repeat')" x-model="cadence" :error="$errors->get('cadence')">
              @foreach ($deadlines->repeats() as $value => $label)
                <option value="{{ $value }}" @selected(old('cadence', $form->cadence()) === $value)>{{ $label }}</option>
              @endforeach
            </x-select>
          </div>

          <fieldset
            x-show="cadence !== 'once'"
            x-bind:disabled="cadence === 'once'"
            x-data="reminderInterval(@js(['locale' => str_replace('_', '-', app()->getLocale()), 'interval' => (int) old('interval', 1), 'wordings' => $deadlines->intervalWordings()]))"
            class="flex flex-col gap-1.5"
          >
            <div class="flex flex-wrap items-center gap-2 text-sm text-ink">
              <span x-text="parts(cadence).before"></span>

              <div class="w-18">
                <x-input
                  type="number"
                  id="contact-reminder-interval"
                  name="interval"
                  size="small"
                  :value="(string) old('interval', 1)"
                  min="1"
                  :max="$form->maximumInterval()"
                  :aria-label="__('Every so many')"
                  x-model="interval"
                />
              </div>

              <span x-text="parts(cadence).after"></span>
            </div>

            @if ($errors->has('interval'))
              <p class="text-sm text-error">{{ $errors->first('interval') }}</p>
            @endif
          </fieldset>

          <div class="flex flex-col gap-3 border-t border-hairline pt-4">
            <div class="flex items-center gap-3">
              <p class="min-w-0 flex-1 text-sm text-body">
                {{ __('Notification time:') }}
                <strong class="font-semibold text-ink" x-text="time"></strong>
                ·
                <strong class="font-semibold text-ink" x-text="zone"></strong>
              </p>

              <x-button.ghost
                type="button"
                size="small"
                aria-controls="contact-reminder-time"
                x-bind:aria-expanded="timeOpen ? 'true' : 'false'"
                x-on:click="timeOpen = ! timeOpen"
              >
                <span x-show="! timeOpen">{{ __('Change') }}</span>
                <span x-show="timeOpen" x-cloak>{{ __('Done') }}</span>
              </x-button.ghost>
            </div>

            <div id="contact-reminder-time" x-show="timeOpen" x-cloak class="grid gap-3 sm:grid-cols-2">
              <x-input
                type="time"
                id="contact-reminder-time-of-day"
                name="time"
                :label="__('Time')"
                :value="old('time', $form->time())"
                :error="$errors->get('time')"
                x-model="time"
                required
              />

              <x-select id="contact-reminder-timezone" name="timezone" :label="__('Time zone')" x-model="zone" :error="$errors->get('timezone')">
                @foreach ($form->zones() as $zone)
                  <option value="{{ $zone['timezone'] }}" @selected(old('timezone', $form->timezone()) === $zone['timezone'])>{{ $zone['label'] }}</option>
                @endforeach
              </x-select>
            </div>

            <p class="text-xs text-muted">{{ __('We’ll notify you on this date. No advance notifications.') }}</p>
          </div>
        </div>

        <x-slot:footer class="flex justify-end gap-2">
          <x-button.secondary
            type="button"
            size="small"
            x-on:click="$refs.form.reset(); cadence = $refs.form.cadence.value; time = $refs.form.time.value; zone = $refs.form.timezone.value; timeOpen = false; adding = false; $nextTick(() => $refs.addButton.focus())"
          >{{ __('Cancel') }}</x-button.secondary>

          <x-button type="submit" size="small">{{ __('Add reminder') }}</x-button>
        </x-slot:footer>
      </x-panel>
    </x-form>
  @endif
</div>
