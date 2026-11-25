{{-- Everybody one person is connected to, gathered under the kind of connection it is. --}}
{{--
  @var \App\ViewModels\Vaults\RelationshipsBlock $relationships
  @var \App\ViewModels\Vaults\FamilyTreeBlock $tree
--}}
@if ($relationships->holdsNothing() && $relationships->emptyCategories() !== [])
  <div class="flex flex-wrap items-start gap-x-12 gap-y-8">
    <x-empty-state :title="$relationships->emptyTitle()" class="max-w-90 flex-[1_1_17.5rem] self-center">
      <x-slot:icon><x-icon name="relationship" :size="28" :stroke-width="1.5" /></x-slot:icon>
      {{ $relationships->emptyExplanation() }}
    </x-empty-state>

    <x-panel :flush="true" class="min-w-0 flex-[2_1_30rem]">
      @foreach ($relationships->emptyCategories() as $category)
        <x-row :icon="$category['icon']" :title="$category['label']" :description="$category['line']">
          <x-slot:end>
            <x-button.ghost
              :href="$category['addUrl']"
              data-turbo="true"
              size="small"
              aria-label="{{ $category['addLabel'] }}"
            >
              <x-slot:icon><x-icon name="plus" :size="15" :stroke-width="1.6" /></x-slot:icon>
              {{ __('Add') }}
            </x-button.ghost>
          </x-slot:end>
        </x-row>
      @endforeach
    </x-panel>
  </div>
@elseif ($relationships->holdsNothing())
  <x-panel>
    <p class="text-sm text-pretty text-muted">{{ $relationships->emptyLine() }}</p>

    <div class="mt-3.5">
      <x-button.secondary :href="$relationships->settingsUrl()" data-turbo="true" size="small">
        {{ __('Set up relationship types') }}
      </x-button.secondary>
    </div>
  </x-panel>
@else
  <div class="flex flex-col gap-5">
    <div class="flex justify-end">
      <x-button.secondary :href="$relationships->addRelationshipUrl()" data-turbo="true" size="small">
        <x-icon name="plus" :size="15" :stroke-width="1.6" />
        {{ __('Add a relationship') }}
      </x-button.secondary>
    </div>

    <div class="grid items-start gap-8 xl:grid-cols-[minmax(0,1fr)_24rem]">
      <div class="flex min-w-0 flex-col gap-8">
        @foreach ($relationships->groups() as $group)
          <section x-data="{ manage: false }">
            <div class="mb-3 flex flex-wrap items-center gap-3">
              <h2 class="text-section-title text-ink">{{ $group['label'] }}</h2>

              <span class="font-mono text-sm text-muted">{{ $group['count'] }}</span>

              <x-chip
                class="ml-auto"
                x-bind:aria-pressed="manage ? 'true' : 'false'"
                @click="manage = !manage"
              >{{ __('Manage') }}</x-chip>
            </div>

            <x-panel :flush="true">
              <ul class="list-none">
                @foreach ($group['rows'] as $row)
                  <li class="flex items-center gap-3 border-b border-hairline px-4 py-2.5 last:border-b-0">
                    @if ($row['url'] === null)
                      <span
                        class="flex size-7.5 shrink-0 items-center justify-center rounded-full border border-dashed border-hairline-strong text-muted-soft"
                        aria-hidden="true"
                      >
                        <x-icon name="profile" :size="14" :stroke-width="1.5" />
                      </span>

                      <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm text-body">{{ $row['name'] }}</span>
                        <span class="block text-xs text-muted">{{ $row['role'] }}</span>
                      </span>

                      <span class="shrink-0 text-xs text-muted">{{ $row['note'] }}</span>
                    @else
                      <a
                        href="{{ $row['url'] }}"
                        data-turbo="true"
                        class="flex min-w-0 flex-1 items-center gap-3 no-underline focus-ring-inset"
                      >
                        <x-contact-avatar :name="$row['name']" :url="$row['avatarUrl']" :size="30" />

                        <span class="min-w-0 flex-1">
                          <span class="block truncate text-sm font-semibold text-ink">{{ $row['name'] }}</span>
                          <span class="block text-xs text-muted">{{ $row['role'] }}</span>
                        </span>
                      </a>

                      @if ($row['age'] !== null)
                        <span
                          class="flex shrink-0 items-center gap-1 text-xs text-muted"
                          aria-label="{{ trans_choice(':count year old|:count years old', $row['age']) }}"
                        >
                          <x-icon name="gift" :size="13" :stroke-width="1.5" />
                          <span aria-hidden="true">{{ $row['age'] }}</span>
                        </span>
                      @endif
                    @endif

                    @if ($row['hasEnded'])
                      <x-badge class="shrink-0">{{ __('Ended') }}</x-badge>
                    @endif

                    @if ($row['whenLine'] !== null)
                      <span class="shrink-0 text-xs text-muted">{{ $row['whenLine'] }}</span>
                    @endif

                    <span x-show="manage" x-cloak class="flex shrink-0 items-center gap-1.5">
                      <x-button.secondary :href="$row['editUrl']" data-turbo="true" size="small">
                        {{ __('Edit') }}
                      </x-button.secondary>

                      <x-delete-action
                        :title="__('Remove this relationship?')"
                        :confirm="__('Remove it')"
                        :action="$row['destroyUrl']"
                        :label="__('Delete')"
                        size="small"
                      >
                        <p>{{ __('The link with :name is taken off both records. Neither person is deleted.', ['name' => $row['name']]) }}</p>
                      </x-delete-action>
                    </span>
                  </li>
                @endforeach
              </ul>
            </x-panel>
          </section>
        @endforeach
      </div>

      <aside class="min-w-0 xl:sticky xl:top-6">
        @include('app.vaults.contacts._family-tree', ['tree' => $tree])
      </aside>
    </div>
  </div>
@endif
