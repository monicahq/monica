{{-- The people closest to somebody: the children written down for them, and whoever they are with now. --}}
{{-- @var \App\ViewModels\Vaults\ImmediateFamilyBlock $family --}}
@php
  $lists = [
    ['label' => __('Children'), 'people' => $family->children(), 'count' => $family->childCount(), 'empty' => $family->noChildrenLine()],
    ['label' => __('Current partner'), 'people' => $family->currentPartners(), 'count' => $family->currentPartnerCount(), 'empty' => $family->noPartnerLine()],
  ];
@endphp
<x-panel
  :title="__('Immediate family')"
  icon="family"
  :tint="$family->holdsNothing() ? \App\Enums\VaultColourEnum::Plum->tint() : null"
  :flush="true"
>
  @unless ($family->holdsNothing())
    <x-slot:action>
      <x-button.secondary :href="$family->addRelationshipUrl()" data-turbo="true" size="small">{{ __('Add') }}</x-button.secondary>
    </x-slot:action>
  @endunless

  @if ($family->holdsNothing())
    <div class="flex justify-center bg-[#9a6a24]/4 px-4 pt-6 pb-5">
      <div class="grid grid-cols-[repeat(3,minmax(0,6rem))] grid-rows-[auto_1.5rem_auto_1.5rem_auto] items-start justify-items-center">
        <a
          href="{{ $family->addParentUrl() }}"
          data-turbo="true"
          aria-label="{{ __('Add a parent') }}"
          class="group col-start-2 row-start-1 flex flex-col items-center gap-1.5 rounded-md no-underline focus-ring"
        >
          <span
            class="flex size-13 items-center justify-center rounded-full border-[1.5px] border-dashed border-[#9a6a24] bg-[#9a6a24]/10 text-[#7f571d] transition-colors duration-(--duration-fast) ease-out group-hover:border-solid group-hover:bg-[#9a6a24] group-hover:text-white dark:border-[#d9a441] dark:text-[#d9a441] dark:group-hover:text-[#09090b]"
            aria-hidden="true"
          >
            <x-icon name="plus" :size="18" :stroke-width="1.6" />
          </span>

          <span class="text-xs font-medium text-[#7f571d] dark:text-[#d9a441]">{{ __('Parent') }}</span>
        </a>

        <span class="col-start-2 row-start-2 h-full border-l-[1.5px] border-dashed border-[#9a6a24]/50" aria-hidden="true"></span>

        <a
          href="{{ $family->addPartnerUrl() }}"
          data-turbo="true"
          aria-label="{{ __('Add a partner') }}"
          class="group col-start-1 row-start-3 mt-1 flex flex-col items-center gap-1.5 rounded-md no-underline focus-ring"
        >
          <span
            class="flex size-13 items-center justify-center rounded-full border-[1.5px] border-dashed border-[#a8447a] bg-[#a8447a]/10 text-[#8e3866] transition-colors duration-(--duration-fast) ease-out group-hover:border-solid group-hover:bg-[#a8447a] group-hover:text-white dark:border-[#e08ab8] dark:text-[#e08ab8] dark:group-hover:text-[#09090b]"
            aria-hidden="true"
          >
            <x-icon name="plus" :size="18" :stroke-width="1.6" />
          </span>

          <span class="text-xs font-medium text-[#8e3866] dark:text-[#e08ab8]">{{ __('Partner') }}</span>
        </a>

        <span class="col-span-2 col-start-1 row-start-3 mt-7.25 w-10 border-t-[1.5px] border-dashed border-[#a8447a]/50" aria-hidden="true"></span>

        <div class="col-start-2 row-start-3 flex min-w-0 flex-col items-center gap-1.5">
          <x-contact-avatar :name="$family->contactFullName()" :url="$family->contactAvatarUrl()" :size="60" alt="" />

          <span class="max-w-full truncate text-xs font-semibold text-ink">{{ $family->contactName() }}</span>
        </div>

        <span class="col-start-2 row-start-4 h-full border-l-[1.5px] border-dashed border-[#2f7f86]/50" aria-hidden="true"></span>

        <a
          href="{{ $family->addChildUrl() }}"
          data-turbo="true"
          aria-label="{{ __('Add children') }}"
          class="group col-start-2 row-start-5 flex flex-col items-center gap-1.5 rounded-md no-underline focus-ring"
        >
          <span
            class="flex size-13 items-center justify-center rounded-full border-[1.5px] border-dashed border-[#2f7f86] bg-[#2f7f86]/10 text-[#26686e] transition-colors duration-(--duration-fast) ease-out group-hover:border-solid group-hover:bg-[#2f7f86] group-hover:text-white dark:border-[#5fb3bb] dark:text-[#5fb3bb] dark:group-hover:text-[#09090b]"
            aria-hidden="true"
          >
            <x-icon name="plus" :size="18" :stroke-width="1.6" />
          </span>

          <span class="text-xs font-medium text-[#26686e] dark:text-[#5fb3bb]">{{ __('Children') }}</span>
        </a>
      </div>
    </div>
  @else
    @foreach ($lists as $list)
      <div class="border-b border-hairline last:border-b-0">
        <h3 class="flex items-baseline gap-1.5 px-4 pt-3 pb-1.5">
          <x-caption>{{ $list['label'] }}</x-caption>

          @if ($list['count'] > 0)
            <span class="text-2xs font-medium text-muted">{{ $list['count'] }}</span>
          @endif
        </h3>

        @if ($list['people'] === [])
          <p class="px-4 pb-3 text-sm text-muted">{{ $list['empty'] }}</p>
        @else
          <ul class="list-none">
            @foreach ($list['people'] as $person)
              <li>
                @if ($person['url'] === null)
                  <div class="flex items-center gap-2.5 px-4 py-2">
                    <span
                      class="flex size-7.5 shrink-0 items-center justify-center rounded-full border border-dashed border-hairline-strong text-muted-soft"
                      aria-hidden="true"
                    >
                      <x-icon name="profile" :size="14" :stroke-width="1.5" />
                    </span>

                    <span class="min-w-0 flex-1 truncate text-sm text-body">{{ $person['name'] }}</span>

                    <span class="shrink-0 text-xs text-muted">{{ $person['note'] }}</span>
                  </div>
                @else
                  <a
                    href="{{ $person['url'] }}"
                    data-turbo="true"
                    class="flex items-center gap-2.5 px-4 py-2 transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring-inset"
                  >
                    <x-contact-avatar :name="$person['name']" :url="$person['avatarUrl']" :size="30" alt="" />

                    <span class="min-w-0 flex-1 truncate text-sm font-semibold text-ink">{{ $person['name'] }}</span>

                    @if ($person['age'] !== null)
                      <span
                        class="flex shrink-0 items-center gap-1 text-xs text-muted"
                        aria-label="{{ trans_choice(':count year old|:count years old', $person['age']) }}"
                      >
                        <x-icon name="gift" :size="13" :stroke-width="1.5" />
                        <span aria-hidden="true">{{ $person['age'] }}</span>
                      </span>
                    @endif
                  </a>
                @endif
              </li>
            @endforeach
          </ul>
        @endif
      </div>
    @endforeach
  @endif
</x-panel>
