{{-- The fields a possession's type brings, replaced whenever another type is picked for a new one. --}}
{{-- @var \App\ViewModels\Vaults\PossessionTypeFieldsBlock|null $fieldsBlock --}}
<div id="possession-type-fields" x-merge="replace" aria-live="polite" class="dim-when-busy">
  @if ($fieldsBlock === null)
    <div class="flex items-center gap-2.5 px-4 py-5">
      <x-icon name="arrow-right" :size="15" class="-rotate-90 text-muted" />
      <p class="text-sm text-muted">{{ __('Choose a type above and its fields appear here.') }}</p>
    </div>
  @elseif ($fieldsBlock->isEmpty())
    <p class="px-4 py-5 text-sm text-muted">{{ __('The :type type has no fields of its own.', ['type' => $fieldsBlock->typeName()]) }}</p>
  @else
    <div class="flex flex-col gap-3.5 p-4">
      <div class="flex flex-wrap items-baseline gap-2">
        <h3 class="text-label text-ink">{{ $fieldsBlock->title() }}</h3>
        <span class="text-xs text-muted">{{ $fieldsBlock->note() }}</span>
      </div>

      @foreach ($fieldsBlock->groups() as $group)
        <fieldset class="flex flex-col gap-3">
          <legend class="pb-2"><x-caption tone="muted">{{ $group['name'] }}</x-caption></legend>

          <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-4">
            @foreach ($group['fields'] as $field)
              @include('app.vaults.possessions._field', ['field' => $field])
            @endforeach
          </div>
        </fieldset>
      @endforeach

      @if ($fieldsBlock->standaloneFields() !== [])
        <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-4">
          @foreach ($fieldsBlock->standaloneFields() as $field)
            @include('app.vaults.possessions._field', ['field' => $field])
          @endforeach
        </div>
      @endif
    </div>
  @endif
</div>
