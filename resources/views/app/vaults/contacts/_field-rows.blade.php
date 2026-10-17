{{-- The rows of a repeated field, a page at a time, on the record and on the page Load more fetches. --}}
{{--
  @var \App\Models\Field $field
  @var array<int, array{id: int, fields: array<int, array<string, mixed>>}> $rows
  @var array<int, array<string, mixed>>|null $notes
  @var array{rowsId: string, footerId: string, nextPageUrl: string|null, showsEnd: bool, count: int} $more
--}}
<x-load-more
  :rows-id="$more['rowsId']"
  :footer-id="$more['footerId']"
  :next-page-url="$more['nextPageUrl']"
  :shows-end="$more['showsEnd']"
  :count="$more['count']"
  :rows-class="$notes === null ? 'flex flex-col gap-2.5' : 'flex flex-col'"
>
  @if ($notes !== null)
    @include('app.vaults.contacts._note-entries', ['entries' => $notes])
  @else
    <x-field-value :field="$field" :rows="$rows" />
  @endif
</x-load-more>
