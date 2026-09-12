{{-- The hidden form a reordered row submits once it is dropped. --}}
{{--
  @var string $action
  @var string $target
  @var int|string $rowId
--}}
@props([
  'action',
  'target',
  'rowId',
])

<x-form method="put" :action="$action" :x-target="$target" :data-position-form="$rowId" class="hidden">
  <input type="hidden" name="position" value="" />
</x-form>
