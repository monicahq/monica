{{-- One location in the tree, its controls, and the locations inside it one level down. --}}
{{-- @var array $row --}}
<li>
  <div
    class="flex flex-wrap items-center gap-x-3 gap-y-2 border-t border-hairline py-2.5 pr-3 pl-[calc(12px_+_var(--location-depth)_*_14px)] sm:pl-[calc(12px_+_var(--location-depth)_*_28px)]"
    style="--location-depth: {{ $row['level'] - 1 }}"
  >
    <div class="flex min-w-44 flex-1 items-center gap-3">
      <span class="flex size-8 flex-none items-center justify-center rounded-md border border-hairline bg-card text-muted" aria-hidden="true">
        @if ($row['emoji'] !== null)
          <span class="text-[17px] leading-none">{{ $row['emoji'] }}</span>
        @else
          <x-icon :name="$row['icon']" :size="16" :stroke-width="1.5" />
        @endif
      </span>

      <span class="min-w-0 flex-1">
        <span class="block truncate font-semibold text-ink">{{ $row['name'] }}</span>
        <span id="location-{{ $row['uuid'] }}-line" class="mt-px block text-sm text-pretty text-muted">{{ $row['line'] }}</span>
      </span>
    </div>

    <div class="ml-auto flex flex-none items-center gap-2">
      <x-button
        :href="$row['editUrl']"
        variant="secondary"
        size="small"
        :aria-label="__('Edit :name', ['name' => $row['name']])"
      >{{ __('Edit') }}</x-button>

      <x-icon-button
        :href="$row['newInsideUrl']"
        icon="plus"
        :label="__('Add a location inside :name', ['name' => $row['name']])"
        align="right"
      />

      @if ($row['mayBeDeleted'])
        <x-modal.warning
          :title="__('Delete “:name”?', ['name' => $row['name']])"
          :confirm="__('Delete the location')"
          :cancel="__('Keep it')"
          :action="$row['destroyUrl']"
        >
          <x-slot:trigger>
            <x-icon-button
              icon="close"
              :label="__('Delete :name', ['name' => $row['name']])"
              align="right"
              @click="open = true"
            />
          </x-slot:trigger>

          <p>{{ __('This cannot be undone.') }}</p>
        </x-modal.warning>
      @else
        <x-icon-button
          icon="close"
          :label="__('Delete :name', ['name' => $row['name']])"
          align="right"
          aria-disabled="true"
          :aria-describedby="'location-'.$row['uuid'].'-line'"
          class="aria-disabled:cursor-default aria-disabled:opacity-50"
        />
      @endif
    </div>
  </div>

  @if ($row['children'] !== [])
    <ul>
      @foreach ($row['children'] as $child)
        @include('app.settings.account.locations._row', ['row' => $child])
      @endforeach
    </ul>
  @endif
</li>
