{{-- One of the answers a field offers when the answer is picked rather than written. --}}
{{--
  @var string $reference
  @var string $choice
--}}
<div data-choice class="flex items-center gap-2">
  <span aria-hidden="true" class="shrink-0 text-muted-soft">
    <x-icon name="radio" :size="14" :stroke-width="1.5" />
  </span>

  <div class="min-w-0 max-w-70 flex-1">
    <x-input
      :name="'fields['.$reference.'][choices][]'"
      :value="$choice"
      :aria-label="__('Wording of the choice')"
      :placeholder="__('Wording of the choice')"
      size="small"
      maxlength="255"
    />
  </div>

  <x-icon-button type="button" icon="close" :label="__('Remove this choice')" @click="removeChoice($el)" />
</div>
