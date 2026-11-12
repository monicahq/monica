{{-- One item of a Knowledge list: its cells, or the fields changing them, and what may be done with it. --}}
{{--
  @var array{id: string, isEditing: bool, cells: list<array{column: string, columnId: string, html: string, value: string, isBlank: bool}>, editUrl: string, updateUrl: string, destroyUrl: string, cancelUrl: string, deleteTitle: string} $item
  @var string $inline
--}}
@if ($item['isEditing'])
  <tr>
    <td colspan="{{ count($item['cells']) + 1 }}" class="border-b border-hairline-soft p-3">
      <x-form method="put" :action="$item['updateUrl']" class="flex flex-col gap-3" aria-label="{{ __('Edit this item') }}">
        <div class="grid grid-cols-[repeat(auto-fit,minmax(8rem,1fr))] gap-2">
          @foreach ($item['cells'] as $index => $cell)
            <input
              name="cells[{{ $cell['columnId'] }}]"
              value="{{ old("cells.{$cell['columnId']}", $cell['value']) }}"
              placeholder="{{ $cell['column'] }}"
              aria-label="{{ $cell['column'] }}"
              maxlength="5000"
              @if ($index === 0) autofocus @endif
              @if ($errors->has("cells.{$cell['columnId']}")) aria-invalid="true" aria-describedby="knowledge-item-cells-error" @endif
              class="h-8 w-full rounded-md border border-hairline-strong bg-input px-2.5 text-[13px] text-ink focus-ring-field"
            />
          @endforeach
        </div>
        <x-error id="knowledge-item-cells-error" :messages="$errors->first('cells*')" />
        <div class="flex flex-wrap gap-2">
          <x-button size="small">{{ __('Save changes') }}</x-button>
          <x-button.secondary size="small" :href="$item['cancelUrl']">{{ __('Cancel') }}</x-button.secondary>
        </div>
      </x-form>
    </td>
  </tr>
@else
  <tr id="item-{{ $item['id'] }}">
    @foreach ($item['cells'] as $index => $cell)
      <td class="border-b border-hairline-soft px-4 py-2.75 align-top leading-[1.55] {{ $cell['isBlank'] ? 'text-muted' : ($index === 0 ? 'text-ink' : 'text-body') }} {{ $inline }}">
        @if ($cell['isBlank'])
          <span aria-hidden="true">—</span><span class="sr-only">{{ __('Empty') }}</span>
        @else
          {!! $cell['html'] !!}
        @endif
      </td>
    @endforeach
    <td class="border-b border-hairline-soft px-2 py-1.5 align-top">
      <x-actions-menu :label="__('Actions for this item')" variant="ghost">
        <x-actions-menu.item :href="$item['editUrl']">{{ __('Edit') }}</x-actions-menu.item>

        <x-modal.warning :title="$item['deleteTitle']" :confirm="__('Delete the item')" :action="$item['destroyUrl']">
          <x-slot:trigger>
            <button type="button" role="menuitem" @click="open = true; menuOpen = false" class="flex w-full cursor-pointer items-center gap-2.5 min-h-8 rounded-md px-2.5 text-left font-sans text-base text-error transition-colors hover:bg-hover focus-ring-inset">{{ __('Delete') }}</button>
          </x-slot:trigger>

          <p>{{ __('The item and everything written in it are deleted. This cannot be undone.') }}</p>
        </x-modal.warning>
      </x-actions-menu>
    </td>
  </tr>
@endif
