{{--
  What one endpoint answers with: a sentence, then one row per field.

  A field is named by its whole path rather than by its own last word, and that
  is the point of this block rather than a detail of it. The indentation says
  which parent a field belongs to only for somebody who can see it; the path says
  it to everybody, in this rendering and in the plain text one, which has no
  indentation to lean on. The indentation is therefore computed from the path,
  so the two can never disagree, and it is decoration.

  The type is a word. Nothing here is told apart by colour.

  @var \App\ViewModels\Docs\ApiDocsSection $section
--}}
@props(['section'])

@php
  $indents = ['ml-0', 'ml-4', 'ml-8', 'ml-12'];
@endphp

@if ($section->returns || $section->hasReturnFields())
  <h2 class="mt-9 mb-1 text-xl text-ink">Returns</h2>

  @if ($section->returns)
    <p class="text-[14px] leading-[1.65] text-pretty text-body">{{ $section->returns }}</p>
  @endif

  @if ($section->hasReturnFields())
    <div class="mt-1.5 flex flex-col">
      @foreach ($section->returnFields as $field)
        @php
          $depth = min(substr_count($field['name'], '.') + substr_count($field['name'], '[]'), 3);
        @endphp

        <div class="flex flex-col gap-1.5 border-t border-hairline-soft py-4 {{ $indents[$depth] }}">
          <div class="flex flex-wrap items-baseline gap-2.5">
            <span class="font-mono text-[14px] font-semibold break-all text-ink">{{ $field['name'] }}</span>
            <span class="font-mono text-[12px] text-muted">{{ $field['type'] }}</span>
          </div>

          <p class="text-[14px] leading-relaxed text-pretty text-body">{{ $field['description'] }}</p>
        </div>
      @endforeach
    </div>
  @endif
@endif
