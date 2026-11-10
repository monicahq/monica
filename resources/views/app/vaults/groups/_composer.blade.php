{{-- The box that writes something about the group: an activity with who was there, or a note. --}}
{{-- @var \App\ViewModels\Vaults\GroupOverviewViewModel $viewModel --}}
@php
  $types = $viewModel->types();
  $participants = $viewModel->participants();
  $kind = $viewModel->kind();
  $text = $kind === 'note' ? old('body') : old('narrative');
  $segment = 'flex h-7.5 cursor-pointer items-center gap-1.75 rounded-md border border-transparent px-2.5 font-medium text-body hover:bg-hover has-checked:border-ink has-checked:bg-card has-checked:text-ink has-focus-visible:ring-[3px] has-focus-visible:ring-focus-ring/45';
@endphp
<section
  aria-labelledby="group-composer-title"
  class="overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel"
  x-data="{ kind: @js($kind), noteUrl: @js($viewModel->noteUrl()), lifeEventUrl: @js($viewModel->lifeEventUrl()) }"
>
  <h2 id="group-composer-title" class="sr-only">{{ __('Write something about the group') }}</h2>

  <x-form method="post" :action="$viewModel->lifeEventUrl()" x-bind:action="kind === 'note' ? noteUrl : lifeEventUrl">
    <fieldset class="flex gap-1 border-b border-hairline p-2">
      <legend class="sr-only">{{ __('What you are writing') }}</legend>

      <label class="{{ $segment }}">
        <input type="radio" name="kind" value="activity" x-model="kind" class="sr-only" @checked($kind === 'activity') />
        <x-icon name="activity" :size="15" />
        {{ __('Activity') }}
      </label>

      <label class="{{ $segment }}">
        <input type="radio" name="kind" value="note" x-model="kind" class="sr-only" @checked($kind === 'note') />
        <x-icon name="note" :size="15" />
        {{ __('Note') }}
      </label>
    </fieldset>

    <div class="flex flex-col gap-4 px-4 py-3.5">
      <fieldset x-show="kind === 'activity'" x-bind:disabled="kind !== 'activity'" class="flex flex-col gap-4" @disabled($kind !== 'activity')>
        <legend class="sr-only">{{ __('The activity') }}</legend>

        @if ($types === [])
          <x-notice id="group-activity-no-type">{{ __('There is no life event type to record an activity as. Add one in the settings first.') }}</x-notice>
        @else
          <div class="grid gap-3 sm:grid-cols-2">
            <x-select id="group-activity-type" name="life_event_type_id" :label="__('What it was')" :error="$errors->get('life_event_type_id')" required>
              @foreach ($types as $type)
                <option value="{{ $type['id'] }}" @selected($viewModel->chosenTypeId() === $type['id'])>{{ $type['name'] }}</option>
              @endforeach
            </x-select>

            <x-input type="date" id="group-activity-date" name="happened_on" :label="__('When')" :value="old('happened_on', $viewModel->today())" :error="$errors->get('happened_on')" required />
          </div>

          <x-input id="group-activity-title" name="title" :label="__('Title')" :value="old('title')" :placeholder="__('Thanksgiving dinner')" :error="$errors->get('title')" maxlength="255" optional />

          <x-textarea id="group-activity-narrative" name="narrative" :label="__('What did the group do?')" :value="old('narrative')" :placeholder="__('A match, a dinner, a trip. Where it was and how it went.')" :error="$errors->get('narrative')" optional />

          <fieldset class="flex flex-col gap-2">
            <legend class="mb-2 font-medium text-ink">{{ __('Who was there') }} <span class="font-normal text-muted">· {{ __('at least one person') }}</span></legend>

            @if ($participants === [])
              <p class="text-sm text-pretty text-muted">
                {{ __('An activity needs at least one person who was there, and nobody is in this group yet.') }}
                <x-link :href="$viewModel->addMemberUrl()" :turbo="true">{{ __('Add somebody') }}</x-link>
              </p>
            @else
              <div class="grid gap-x-4 gap-y-2 sm:grid-cols-2">
                @foreach ($participants as $participant)
                  <x-checkbox
                    id="group-activity-participant-{{ $participant['id'] }}"
                    name="participants[]"
                    :value="$participant['id']"
                    :checked="$participant['isTicked']"
                    :aria-invalid="$errors->has('participants') ? 'true' : null"
                    :aria-describedby="$errors->has('participants') ? 'group-activity-participants-error' : null"
                  >{{ $participant['name'] }}@unless ($participant['isCurrent']) <span class="text-muted">· {{ __('Left') }}</span>@endunless</x-checkbox>
                @endforeach
              </div>
            @endif

            <x-error id="group-activity-participants-error" :messages="$errors->get('participants')" />
          </fieldset>
        @endif
      </fieldset>

      <fieldset x-show="kind === 'note'" x-bind:disabled="kind !== 'note'" x-cloak @disabled($kind !== 'note')>
        <legend class="sr-only">{{ __('The note') }}</legend>

        <x-textarea id="group-note-body" name="body" :label="__('What do you want to remember about the group?')" :value="old('body')" :placeholder="__('Where they usually meet, who has the spare key, what to bring.')" :error="$errors->get('body')" required />
      </fieldset>
    </div>

    <div class="flex flex-wrap items-center gap-3 border-t border-hairline bg-card px-4 py-2.5">
      <p class="max-w-[46ch] text-xs text-pretty text-muted" x-show="kind === 'activity'" @if ($kind !== 'activity') x-cloak @endif>{{ __('Saved here, and on the page of each person you ticked.') }}</p>
      <p class="max-w-[46ch] text-xs text-pretty text-muted" x-show="kind === 'note'" @if ($kind !== 'note') x-cloak @endif>{{ __('Saved on the group only. It does not appear on anybody’s own page.') }}</p>

      @if ($types === [])
        <x-button size="small" class="ml-auto" x-show="kind === 'activity'" aria-disabled="true" aria-describedby="group-activity-no-type" x-on:click.prevent>{{ __('Save the activity') }}</x-button>
        <x-button size="small" class="ml-auto" x-show="kind === 'note'" x-cloak>{{ __('Save the note') }}</x-button>
      @else
        <x-button size="small" class="ml-auto">
          <span x-show="kind === 'activity'" @if ($kind !== 'activity') x-cloak @endif>{{ __('Save the activity') }}</span>
          <span x-show="kind === 'note'" @if ($kind !== 'note') x-cloak @endif>{{ __('Save the note') }}</span>
        </x-button>
      @endif
    </div>
  </x-form>
</section>
