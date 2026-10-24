{{-- The way through a list shown in numbered pages: where you are, the pages around you, and how many rows a page holds. --}}
{{--
  @var \Illuminate\Pagination\LengthAwarePaginator $paginator
  @var int|string $size
  @var int $default
  @var string|null $target
  @var string|null $id
--}}
@props([
  'paginator',
  'size',
  'default',
  'target' => null,
  'id' => null,
])

@php
  $numberFormat = auth()->user()?->number_format
    ?? \App\Enums\NumberFormatEnum::tryFrom((string) \App\Helpers\AvailableLocales::details(app()->getLocale())['number_format'])
    ?? \App\Enums\NumberFormatEnum::CommaAndDot;
  $count = fn (int $number): string => $numberFormat->format($number, 0);

  $window = $paginator->onEachSide(1)->linkCollection()->slice(1, -1);
  $pageSizeId = ($id ? $id.'-' : '').'page-size';

  $step = 'inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-sm';
  $link = $step.' border-hairline text-body transition-colors duration-(--duration-fast) hover:bg-hover focus-ring';
  $held = $step.' border-transparent text-muted';
@endphp

@if ($paginator->total() > 0)
  <nav aria-label="{{ __('Pagination') }}" @if ($id) id="{{ $id }}" @endif {{ $attributes->class(['flex flex-wrap items-center justify-between gap-x-4 gap-y-3']) }}>
    @if ($paginator->firstItem() !== null)
      <p class="text-sm text-muted">{{ __(':first to :last of :total', ['first' => $count($paginator->firstItem()), 'last' => $count($paginator->lastItem()), 'total' => $count($paginator->total())]) }}</p>
    @endif

    @if ($paginator->hasPages())
      <ul class="flex flex-wrap items-center gap-1">
        <li>
          @if ($paginator->onFirstPage())
            <span aria-disabled="true" class="{{ $held }}">
              <svg class="size-5 rtl:rotate-180" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
              <span class="sr-only">{!! __('pagination.previous') !!}</span>
            </span>
          @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" @if ($target) x-target.push="{{ $target }}" @else data-turbo="true" @endif class="{{ $link }}">
              <svg class="size-5 rtl:rotate-180" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
              <span class="sr-only">{!! __('pagination.previous') !!}</span>
            </a>
          @endif
        </li>

        @foreach ($window as $page)
          <li>
            @if ($page['url'] === null)
              <span aria-hidden="true" class="{{ $held }}">…</span>
            @elseif ($page['active'])
              <span aria-current="page" class="{{ $step }} border-hairline bg-hover font-medium text-ink">{{ $page['label'] }}</span>
            @else
              <a href="{{ $page['url'] }}" aria-label="{{ __('Page :page', ['page' => $page['label']]) }}" @if ($target) x-target.push="{{ $target }}" @else data-turbo="true" @endif class="{{ $link }}">{{ $page['label'] }}</a>
            @endif
          </li>
        @endforeach

        <li>
          @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" @if ($target) x-target.push="{{ $target }}" @else data-turbo="true" @endif class="{{ $link }}">
              <svg class="size-5 rtl:rotate-180" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
              <span class="sr-only">{!! __('pagination.next') !!}</span>
            </a>
          @else
            <span aria-disabled="true" class="{{ $held }}">
              <svg class="size-5 rtl:rotate-180" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
              <span class="sr-only">{!! __('pagination.next') !!}</span>
            </span>
          @endif
        </li>
      </ul>
    @endif

    <form method="get" action="{{ $paginator->path() }}" @if ($target) x-target.push="{{ $target }}" @endif class="flex items-end gap-2">
      @foreach (request()->except(['page', 'size']) as $name => $value)
        @if (is_scalar($value))
          <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
      @endforeach

      <x-select :id="$pageSizeId" name="size" :label="__('Rows per page')" size="small" x-on:change="$el.form.requestSubmit()">
        @foreach (\App\Helpers\PageSize::CHOICES as $choice)
          <option value="{{ $choice }}" @selected($size === $choice)>{{ $choice }}</option>
        @endforeach
        <option value="{{ \App\Helpers\PageSize::ALL }}" @selected($size === \App\Helpers\PageSize::ALL)>{{ __('All') }}</option>
      </x-select>

      <x-button.secondary type="submit" size="small" x-show="false">{{ __('Apply') }}</x-button.secondary>
    </form>
  </nav>
@endif
