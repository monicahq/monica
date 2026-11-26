{{-- What the template has no say over about a contact: their name, their picture, their existence. --}}
{{-- @var \App\ViewModels\Vaults\ContactEditViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $contact = $viewModel->contact();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[['label' => __('People'), 'url' => route('contacts.index', $vault)],
        ['label' => $contact->name, 'url' => $viewModel->contactUrl()],
        ['label' => $viewModel->title()],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="contacts" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()" :description="$viewModel->description()" />

  <x-panel
    :title="__('Photo')"
    :subtitle="$viewModel->avatarDescription()"
    icon="profile"
  >
    <div
      class="flex flex-wrap items-center gap-5"
      x-data="{ tooBig: false, tooBigMessage: @js(__('The photo must be under 5 MB.')) }"
    >
      <x-contact-avatar :name="$contact->name" :url="$viewModel->avatarUrl()" :size="72" />

      <div class="flex min-w-0 flex-1 flex-col gap-3">
        @if ($viewModel->uploadsAreOn())
          <x-form
            method="post"
            :action="$viewModel->updateAvatarUrl()"
            :upload="true"
            id="avatar-form"
            class="flex flex-wrap items-center gap-2.5"
            x-on:submit="if (tooBig) $event.preventDefault()"
          >
            <x-file-input
              id="avatar"
              accept="image/jpeg,image/png,image/webp"
              :error="$errors->get('avatar')"
              required
              class="w-auto"
              aria-label="{{ __('Choose a photo') }}"
              x-on:change="tooBig = window.oversizedFiles($event.target.files, 5120).length > 0"
            />

            <x-button size="small">{{ __('Upload') }}</x-button>
          </x-form>

          <p x-show="tooBig" x-cloak x-text="tooBigMessage" class="text-xs text-error"></p>
        @elseif ($viewModel->hasAvatar())
          <x-notice tone="quiet">{{ __('Uploads are unavailable on this account for now.') }}</x-notice>
        @endif

        @if ($viewModel->hasAvatar())
          <x-modal.warning
            :title="__('Remove the photo of :name?', ['name' => $contact->name])"
            :confirm="__('Remove the photo')"
            :action="$viewModel->destroyAvatarUrl()"
          >
            <x-slot:trigger>
              <x-button.ghost type="button" size="small" tone="danger" class="self-start" @click="open = true">
                {{ __('Remove the photo') }}
              </x-button.ghost>
            </x-slot:trigger>

            <p>{{ __('They are drawn as their initials again.') }}</p>
          </x-modal.warning>
        @endif
      </div>
    </div>
  </x-panel>

  <x-form id="name-form" method="put" :action="$viewModel->action()" class="flex flex-col gap-5">
    <x-panel
      :title="__('Change the name')"
      icon="people"
    >
      <div class="flex flex-col gap-6">
        <x-contact-name-editor :block="$viewModel->nameEditor()" :short-name="$viewModel->shortName()" />

        <div class="h-px bg-hairline"></div>

        <div class="grid gap-4 sm:grid-cols-2">
          <x-input
            id="nickname"
            :value="$viewModel->value('nickname')"
            :label="__('Nickname')"
            optional
            :help="__('What they are actually called.')"
            :error="$errors->get('nickname')"
            maxlength="255"
          />

          <x-input
            id="maiden_name"
            :value="$viewModel->value('maiden_name')"
            :label="__('Name before marriage')"
            optional
            :error="$errors->get('maiden_name')"
            maxlength="255"
          />
        </div>
      </div>
    </x-panel>

    <div class="flex flex-wrap items-center justify-end gap-2" x-data="{ held: false }" @name-editor-busy.window="held = $event.detail">
      <p id="name-form-held" x-show="held" x-cloak class="text-sm text-muted">{{ __('Finish the change to the name first, or cancel it.') }}</p>

      <x-button.secondary :href="$viewModel->contactUrl()" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

      <x-button type="submit" ::aria-disabled="held" aria-describedby="name-form-held">{{ __('Save the name') }}</x-button>
    </div>
  </x-form>

  @if ($viewModel->showsTemplatePanel())
    <x-panel :title="__('Template')" :subtitle="$viewModel->templateSubtitle()" icon="layers" :clip="false">
      <div class="flex flex-col gap-4" x-data="{ consequencesShown: false }">
        <div class="flex max-w-105 flex-col gap-1.5">
          <div class="flex items-center gap-1.5">
            <span id="template-label" class="text-sm font-medium text-ink">{{ $viewModel->templateLabel() }}</span>

            <x-help
              :label="__('Help about the template of a contact')"
              :title="__('What changing the template means')"
              guide="contacts.change-template"
            >{{ $viewModel->templateHelp() }}</x-help>
          </div>

          <x-actions-menu
            :label="__('Template')"
            variant="control"
            width="full"
            trigger-id="template-trigger"
            labelledby="template-label template-current"
            :describedby="$errors->has('template') ? 'template-error' : null"
          >
            <x-slot:text>
              <span id="template-current" class="flex min-w-0 flex-1 flex-wrap items-baseline gap-x-2.5">
                <span class="min-w-0 font-medium wrap-anywhere">{{ $viewModel->currentTemplateName() }}</span>

                @if ($viewModel->currentVersionLabel())
                  <span class="text-sm text-muted">{{ $viewModel->currentVersionLabel() }}</span>
                @endif
              </span>
            </x-slot:text>

            @foreach ($viewModel->templateChoices() as $choice)
              <div
                class="flex flex-col gap-0.5 pb-1"
                @if ($choice['countLabel']) role="group" aria-labelledby="template-group-{{ $loop->index }}" @endif
              >
                @if ($choice['countLabel'])
                  <p id="template-group-{{ $loop->index }}" class="flex items-baseline gap-2 px-2.5 pt-2.5 pb-1">
                    <span class="text-sm font-semibold text-ink wrap-anywhere">{{ $choice['name'] }}</span>
                    <span class="text-xs text-muted">{{ $choice['countLabel'] }}</span>
                  </p>
                @endif

                @foreach ($choice['versions'] as $version)
                  <x-actions-menu.item
                    :href="$version['url']"
                    :checked="$version['isCurrent']"
                    x-target="template-change"
                    :indented="$choice['countLabel'] !== null"
                  >
                    <span class="min-w-0 flex-1 wrap-anywhere">{{ $version['label'] }}</span>

                    @if ($version['hint'] !== '')
                      <span class="shrink-0 text-xs text-muted">{{ $version['hint'] }}</span>
                    @endif

                    <x-icon name="check" :size="15" :stroke-width="2" @class(['shrink-0', 'invisible' => ! $version['isCurrent']]) />
                  </x-actions-menu.item>
                @endforeach
              </div>
            @endforeach
          </x-actions-menu>

          @error('template')
            <p id="template-error" class="text-sm text-error">{{ $message }}</p>
          @enderror
        </div>

        <div
          id="template-change"
          x-merge="update"
          @ajax:merged="if (! $el.querySelector('[x-autofocus]')) document.getElementById('template-trigger')?.focus()"
          class="flex flex-col gap-4"
        >
          @if ($change = $viewModel->pendingChange())
            <section aria-labelledby="template-change-title" class="flex flex-col overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel">
              <div class="flex flex-col gap-4 p-5">
                <div class="flex flex-col gap-1.5">
                  <h3 id="template-change-title" tabindex="-1" x-autofocus class="text-base font-semibold text-pretty text-ink focus:outline-none">{{ $change['title'] }}</h3>

                  @if ($change['refusal'])
                    <p id="template-refusal" class="text-sm text-error">{{ $change['refusal'] }}</p>
                  @else
                    <p class="text-sm text-pretty text-body">{{ $change['sentence'] }}</p>
                  @endif
                </div>

                @if (($groups = $viewModel->preview()) !== null)
                  <div class="flex items-start gap-2.5">
                    <x-icon name="check" :size="16" :stroke-width="2" class="mt-px text-success" />

                    <p class="text-sm text-pretty text-body">
                      <strong class="font-semibold text-ink">{{ __('No data will be lost.') }}</strong>
                      {{ __('Any information that doesn’t have a matching field in the new template will remain accessible in Unused answers.') }}
                    </p>
                  </div>

                  @foreach ($groups as $group)
                    <div class="flex flex-col gap-2">
                      <p class="text-sm font-semibold text-ink">{{ $group['title'] }}</p>

                      <ul class="flex flex-col divide-y divide-hairline rounded-md border border-hairline">
                        @foreach ($group['lines'] as $line)
                          <li class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1 px-3 py-2">
                            <span class="text-sm font-medium text-ink wrap-anywhere">
                              {{ $line['text'] }}
                              @if ($line['count'])
                                <span class="font-normal text-muted">{{ $line['count'] }}</span>
                              @endif
                            </span>

                            @if ($line['section'])
                              <span class="flex items-center gap-1.5 text-xs text-muted">
                                <x-icon name="folder" :size="14" />
                                {{ $line['section'] }}
                              </span>
                            @endif
                          </li>
                        @endforeach
                      </ul>

                      @if ($group['note'])
                        <p class="text-xs text-pretty text-muted">{{ $group['note'] }}</p>
                      @endif
                    </div>
                  @endforeach
                @endif
              </div>

              <div class="flex flex-wrap items-center justify-between gap-3 border-t border-hairline bg-card px-5 py-3">
                <p class="text-xs text-muted">{{ __('You can switch back to your previous template at any time.') }}</p>

                <div class="flex flex-wrap gap-2">
                  <x-button.secondary :href="$change['cancelUrl']" x-target="template-change">{{ __('Cancel') }}</x-button.secondary>

                  @if ($change['refusal'])
                    <x-button type="button" aria-disabled="true" aria-describedby="template-refusal">{{ __('Change template') }}</x-button>
                  @else
                    <x-form method="put" :action="$change['action']">
                      <input type="hidden" name="template" value="{{ $change['template'] }}" />
                      <input type="hidden" name="structure_version_id" value="{{ $change['versionId'] }}" />

                      <x-button type="submit">{{ __('Change template') }}</x-button>
                    </x-form>
                  @endif
                </div>
              </div>
            </section>
          @else
            <p class="text-sm text-muted">
              {{ __('Changing a template will have some consequences.') }}
              <button
                type="button"
                class="cursor-pointer font-medium text-ink underline underline-offset-2 focus-ring"
                aria-controls="template-consequences"
                :aria-expanded="consequencesShown ? 'true' : 'false'"
                @click="consequencesShown = ! consequencesShown"
                x-text="consequencesShown ? @js(__('Show less')) : @js(__('Read more'))"
              >{{ __('Read more') }}</button>
            </p>

            <div id="template-consequences" x-show="consequencesShown" x-cloak class="flex flex-col gap-4 rounded-lg border border-hairline bg-card px-5 py-4">
              <p class="text-base font-medium text-ink">{{ $viewModel->consequencesLead() }}</p>

              <ul class="grid gap-x-8 gap-y-3.5 md:grid-cols-2">
                @foreach ($viewModel->consequences() as $consequence)
                  <li class="flex items-start gap-3">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-md border border-hairline bg-canvas text-ink-soft">
                      <x-icon :name="$consequence['icon']" :size="15" />
                    </span>

                    <span class="pt-1 text-sm text-body">{{ $consequence['text'] }}</span>
                  </li>
                @endforeach
              </ul>
            </div>
          @endif
        </div>
      </div>
    </x-panel>
  @endif

  @if ($demotion = $viewModel->demotion())
    @include('app.vaults.contacts._demotion', ['demotion' => $demotion])
  @endif

  <x-delete-section :title="__('Delete contact')" icon="people">
    <p id="delete-contact-reason">{{ $viewModel->deleteDescription() }}</p>

    <x-slot:trigger>
      @if ($viewModel->mayBeDeleted())
        <x-modal.warning
          :title="__('Delete :name?', ['name' => $contact->name])"
          :confirm="__('Delete the contact')"
          :action="$viewModel->destroyUrl()"
        >
          <x-slot:trigger>
            <x-button.danger type="button" @click="open = true">
              {{ __('Delete the contact') }}
            </x-button.danger>
          </x-slot:trigger>

          <p>{{ __('Their relationships, reminders and life events go with them to the trash of this vault, where they wait 30 days and can be restored.') }}</p>
        </x-modal.warning>
      @else
        <x-button.danger
          type="button"
          aria-disabled="true"
          aria-describedby="delete-contact-reason"
        >
          {{ __('Delete the contact') }}
        </x-button.danger>
      @endif
    </x-slot:trigger>
  </x-delete-section>
</x-shell>
