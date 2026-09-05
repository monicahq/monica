{{-- Everything that has happened to one reminder, newest first. --}}
{{-- @var array<int, array<string, string>> $entries --}}
<ol class="flex flex-col gap-0">
  @foreach ($entries as $entry)
    <li class="grid grid-cols-[7.5rem_1.25rem_minmax(0,1fr)] items-start gap-3 pb-4.5 last:pb-0">
      <div class="flex flex-col items-end gap-px pt-px">
        <span class="text-xs whitespace-nowrap text-body">{{ $entry['date'] }}</span>
        <span class="font-mono text-[11.5px] whitespace-nowrap text-muted-soft">{{ $entry['time'] }}</span>
      </div>

      <div aria-hidden="true" class="relative flex self-stretch justify-center">
        <span class="absolute top-4 -bottom-4.5 w-px bg-hairline"></span>

        <span
          @class([
            'relative mt-1 size-2.25 rounded-full ring-3 ring-canvas',
            'bg-success' => $entry['tone'] === 'success',
            'bg-muted-soft' => $entry['tone'] !== 'success',
          ])
        ></span>
      </div>

      <div class="flex min-w-0 flex-col gap-0.5">
        <span class="text-sm font-semibold text-ink">{{ $entry['title'] }}</span>
        <span class="text-xs leading-relaxed text-pretty text-body">{{ $entry['note'] }}</span>
      </div>
    </li>
  @endforeach
</ol>
