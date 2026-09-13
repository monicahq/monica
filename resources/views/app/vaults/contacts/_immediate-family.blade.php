{{-- The people closest to somebody: the children written down for them, and whoever they are with now. --}}
{{-- @var \App\ViewModels\Vaults\ImmediateFamilyBlock $family --}}
@php
  $lists = [
    ['label' => __('Children'), 'people' => $family->children(), 'count' => $family->childCount(), 'empty' => $family->noChildrenLine()],
    ['label' => __('Current partner'), 'people' => $family->currentPartners(), 'count' => $family->currentPartnerCount(), 'empty' => $family->noPartnerLine()],
  ];
@endphp
<x-panel :title="__('Immediate family')" icon="family" :flush="true">
  @unless ($family->holdsNothing())
    <x-slot:action>
      <x-button.secondary :href="$family->addRelationshipUrl()" data-turbo="true" size="small">{{ __('Add') }}</x-button.secondary>
    </x-slot:action>
  @endunless

  @if ($family->holdsNothing())
    <div class="p-4">
      <p class="text-sm text-pretty text-muted">{{ $family->emptyLine() }}</p>

      <div class="mt-3.5">
        <x-button.secondary :href="$family->addRelationshipUrl()" data-turbo="true" size="small">
          {{ __('Add a relationship') }}
        </x-button.secondary>
      </div>
    </div>
  @else
    @foreach ($lists as $list)
      <div class="border-b border-hairline last:border-b-0">
        <h3 class="flex items-baseline gap-1.5 px-4 pt-3 pb-1.5">
          <x-caption>{{ $list['label'] }}</x-caption>

          @if ($list['count'] > 0)
            <span class="text-[11px] font-medium text-muted">{{ $list['count'] }}</span>
          @endif
        </h3>

        @if ($list['people'] === [])
          <p class="px-4 pb-3 text-sm text-muted">{{ $list['empty'] }}</p>
        @else
          <ul class="list-none pb-1">
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
                    <x-contact-avatar :name="$person['name']" :url="$person['avatarUrl']" :size="30" />

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
