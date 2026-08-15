{{--
  The screen that makes a link onto somebody's page, and the same screen again when
  what an existing link shows is being changed.

  It is written the way the promise is. Sections arrive ticked because holding the
  whole page back would leave nothing to share; everything else arrives off, and
  the panel under the sections says what stays here whatever is ticked. Most of
  that list is not a choice anybody made: it is what the product cannot hand to a
  link at all yet, and saying so is better than letting somebody assume it went.

  Only two questions on this form change what a recipient sees, because only two of
  them can be honoured today. The five other inclusions a link stores point at
  things that do not exist, and a control for one of them would be a promise the
  page cannot keep.

  The sections are submitted as the ones going out rather than the ones held back,
  since a box nobody ticks sends nothing at all. The column stores the exclusions,
  and the controller turns one into the other.

  The count beside the submit button is the only piece of client state on the
  screen. It reads the checkboxes rather than being told what they are, so it cannot
  drift out of step with them.

  @var \App\ViewModels\Vaults\ShareLinkFormViewModel $viewModel
--}}
@php
  $pages = $viewModel->pages();
  $sectionCount = $viewModel->sectionCount();
@endphp
<x-shell :title="$viewModel->title()" wide>
  <x-slot:bar>
    <x-vault.bar
      :vault="$viewModel->vault()"
      :items="[
        ['label' => __('People'), 'icon' => 'people', 'url' => $viewModel->peopleUrl()],
        ['label' => $viewModel->contact()->name, 'url' => $viewModel->contactUrl()],
        ['label' => __('Links'), 'url' => $viewModel->linksUrl()],
        ['label' => $viewModel->breadcrumbLabel()],
      ]"
    />
  </x-slot:bar>

  <div class="flex flex-wrap items-start gap-6">
    <div class="min-w-60 flex-1">
      <span class="flex items-center gap-2 text-xs tracking-(--tracking-caption) text-muted uppercase">
        <x-icon name="lock" :size="15" :stroke-width="1.6" />
        {{ __('Read only, always') }}
      </span>

      <h1 class="mt-2 font-display text-page-title text-balance text-ink">{{ $viewModel->title() }}</h1>

      <p class="mt-2 max-w-[66ch] text-base text-pretty text-muted">{{ $viewModel->intro() }}</p>
    </div>

    <div class="flex shrink-0 items-center gap-3.5 rounded-lg border border-hairline bg-canvas px-4 py-3.5 shadow-raised">
      <x-contact-avatar :name="$viewModel->contact()->name" :url="$viewModel->avatarUrl()" :size="44" />

      <span class="flex min-w-0 flex-col gap-0.5">
        <span class="text-card-title text-ink">{{ $viewModel->contact()->name }}</span>

        <span class="text-xs text-muted">{{ $viewModel->templateLine() }}</span>
      </span>
    </div>
  </div>

  <x-form
    :method="$viewModel->method()"
    :action="$viewModel->action()"
    x-data="{ shown: 0, count() { this.shown = this.$el.querySelectorAll('[data-section]:checked').length } }"
    x-init="count()"
    @change="count()"
    class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_340px]"
  >
    <div class="flex min-w-0 flex-col gap-6">
      <x-panel :title="__('The link itself')" :subtitle="__('Both of these are for you. Whoever opens the link never sees them.')">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <x-input
            id="label"
            :label="__('Name this link')"
            :value="old('label', $viewModel->label())"
            :placeholder="__('For the family doctor')"
            :help="__('So you can tell it apart from the others later.')"
            :error="$errors->get('label')"
            maxlength="255"
            required
            autofocus
          />

          <x-input
            id="recipient_hint"
            :label="__('Who it is for')"
            :value="old('recipient_hint', $viewModel->recipientHint())"
            :placeholder="__('Dr Ferrand, Angers')"
            :help="__('Your own note. Nobody is emailed.')"
            :error="$errors->get('recipient_hint')"
            maxlength="255"
            optional
          />
        </div>

        <div class="mt-4 grid grid-cols-1 items-start gap-4 sm:grid-cols-2">
          <x-select id="expiry" :label="__('Stops working')" :error="$errors->get('expiry')" required>
            @foreach ($viewModel->expiryOptions() as $option)
              <option value="{{ $option['value'] }}" @selected(old('expiry', $option['isSelected'] ? $option['value'] : null) === $option['value'])>
                {{ $option['label'] }}
              </option>
            @endforeach

            <x-slot:help>{{ __('A link with no end date is one you have to remember to revoke.') }}</x-slot:help>
          </x-select>

          <div class="flex flex-col gap-2.5">
            <x-label for="password" :value="__('Password')" optional />

            <x-input
              id="password"
              type="password"
              :placeholder="$viewModel->isProtectedByAPassword() ? __('Leave empty to keep the one it has') : __('Asked for before the page opens')"
              :error="$errors->get('password')"
              autocomplete="new-password"
              maxlength="255"
            />

            @if ($viewModel->isProtectedByAPassword())
              <label for="removes_password" class="flex items-start gap-2.5 text-sm text-body">
                <x-checkbox id="removes_password" :checked="old('removes_password') === '1'" class="mt-0.5" />
                {{ __('Stop asking for a password') }}
              </label>
            @endif
          </div>
        </div>
      </x-panel>

      <x-panel :title="__('What the recipient sees')" :subtitle="__('Off means held back. Nothing here is on unless you turn it on.')">
        <x-option-row
          id="includes_sensitive_fields"
          name="includes_sensitive_fields"
          :checked="old('includes_sensitive_fields', $viewModel->includesSensitiveFields() ? '1' : null) === '1'"
          :hint="__('The fields your templates mark sensitive, wherever they appear. Leaving this off is the safe answer.')"
        >
          <span class="flex flex-wrap items-center gap-2">
            {{ __('Fields you marked sensitive') }}

            <x-badge tone="danger">{{ __('Sensitive') }}</x-badge>
          </span>
        </x-option-row>

        <p class="mt-4 text-xs tracking-(--tracking-caption) text-muted uppercase">{{ __('Held back whatever you tick') }}</p>

        <ul class="mt-2 flex list-none flex-col gap-1.5">
          @foreach ([
            __('Anything attached as a file, and anything a repeated block holds'),
            __('Activities, and everything you wrote about them'),
            __('Your account of any day this person appears in'),
            __('Relationships and group memberships'),
            __('Who changed what and when, and everything technical'),
            __('Reminders and notifications, which never reach a visitor'),
          ] as $line)
            <li class="flex items-start gap-2 text-sm text-pretty text-ink-soft">
              <span class="mt-1.5 flex size-3.5 shrink-0 rounded-xs border-[1.5px] border-hairline-strong bg-sunken" aria-hidden="true"></span>
              {{ $line }}
            </li>
          @endforeach
        </ul>
      </x-panel>

      <x-panel :title="__('Sections of their page')" :subtitle="$viewModel->sectionsLine()" :flush="true">
        @foreach ($pages as $page)
          <div class="border-b border-hairline px-4.5 py-3.5 last:border-b-0">
            <p class="text-sm font-semibold text-ink">{{ $page['label'] }}</p>

            <div class="mt-2.5 flex flex-wrap gap-2">
              @foreach ($page['sections'] as $section)
                <x-option-row
                  :id="'section-'.$section['id']"
                  name="sections[]"
                  :value="$section['id']"
                  :checked="old('sections') === null ? $section['isIncluded'] : in_array((string) $section['id'], old('sections', []), true)"
                  :chip="true"
                  data-section
                >{{ $section['name'] }}</x-option-row>
              @endforeach
            </div>
          </div>
        @endforeach
      </x-panel>

      <x-notice tone="quiet" icon="lock" :title="__('What a link can never do')">
        {{ $viewModel->neverLine() }}
      </x-notice>
    </div>

    <div class="flex flex-col gap-4 lg:sticky lg:top-20">
      <x-panel :title="__('Going out')">
        <p class="text-sm text-pretty text-ink">
          <span x-text="shown">{{ $sectionCount }}</span>
          {{ __('of :total sections, their name, and nothing else unless you ticked it above.', ['total' => $sectionCount]) }}
        </p>

        <div class="mt-4 flex flex-col gap-2.5">
          <x-button class="w-full">{{ $viewModel->submitLabel() }}</x-button>

          <x-button.secondary :href="$viewModel->linksUrl()" data-turbo="true" class="w-full">{{ __('Cancel') }}</x-button.secondary>
        </div>

        <p class="mt-3 text-xs text-pretty text-muted">{{ $viewModel->submitLine() }}</p>
      </x-panel>
    </div>
  </x-form>
</x-shell>
