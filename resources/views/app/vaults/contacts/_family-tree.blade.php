{{-- One person's line: who is recorded as their parent, who they are with, and who their children are. --}}
{{-- @var \App\ViewModels\Vaults\FamilyTreeBlock $tree --}}
@php
  $node = fn (array $person, int $size = 36, bool $isSubject = false): array => [
    'node' => $person,
    'size' => $size,
    'isSubject' => $isSubject,
    'nameOnlyLine' => $tree->nameOnlyLine(),
  ];
@endphp
{{-- The heading row is as tall as a group's, whose Manage chip sets the height, so the first group
  and the tree start on the same line. --}}
<div class="mb-3 flex h-8 items-center">
  <h2 id="family-tree" class="text-section-title text-ink">{{ __('Family tree') }}</h2>
</div>

<x-panel :flush="true">
  @if ($tree->holdsNothing())
    <div class="p-4">
      <p class="text-sm text-pretty text-muted">{{ $tree->emptyLine() }}</p>

      <div class="mt-3.5">
        <x-button.secondary :href="$tree->addRelationshipUrl()" data-turbo="true" size="small">
          {{ __('Add a relationship') }}
        </x-button.secondary>
      </div>
    </div>
  @else
    {{-- A tier gathers onto one line: a stub under each node and a bar that stops halfway under the
      outermost two, so any number of people needs no arithmetic. A tier of one drops the bar. --}}
    <div
      tabindex="0"
      role="group"
      aria-labelledby="family-tree"
      class="h-72 overflow-auto bg-card px-4 py-5 focus-ring-inset"
    >
      <div class="mx-auto flex w-max min-w-full flex-col items-center">
        @if ($tree->parents() !== [])
          <h4 class="sr-only">{{ __('Parents') }}</h4>

          <ul class="flex list-none justify-center">
            @foreach ($tree->parents() as $parent)
              <li class="relative flex flex-col items-center px-1 pb-5 before:absolute before:bottom-0 before:left-1/2 before:h-5 before:w-px before:-translate-x-1/2 before:bg-hairline-strong after:absolute after:inset-x-0 after:bottom-0 after:h-px after:bg-hairline-strong first:after:left-1/2 last:after:right-1/2 only:after:hidden">
                @include('app.vaults.contacts._family-tree-node', $node($parent))
              </li>
            @endforeach
          </ul>

          <span class="h-5 w-px bg-hairline-strong" aria-hidden="true"></span>
        @endif

        <div class="grid w-full grid-cols-[1fr_auto_1fr] items-start">
          <span></span>

          @include('app.vaults.contacts._family-tree-node', $node($tree->subject(), 36, true))

          @if ($tree->currentPartners() === [])
            <span></span>
          @else
            <div class="pl-6">
              <h4 class="sr-only">{{ __('Current partners') }}</h4>

              {{-- A partner line spans the 24px gap plus the empty margin of each node (22px beside a 36px
                avatar, 18px beside the subject's ring), so it runs from avatar edge to avatar edge. --}}
              <ul class="flex list-none justify-start gap-6">
                @foreach ($tree->currentPartners() as $partner)
                  <li class="relative before:absolute before:top-4.5 before:-left-11.5 before:h-px before:w-17 before:bg-hairline-strong first:before:-left-10.5 first:before:w-16">
                    @include('app.vaults.contacts._family-tree-node', $node($partner))
                  </li>
                @endforeach
              </ul>
            </div>
          @endif
        </div>

        @if ($tree->children() !== [])
          <span class="h-5 w-px bg-hairline-strong" aria-hidden="true"></span>

          <h4 class="sr-only">{{ __('Children') }}</h4>

          <ul class="flex list-none justify-center">
            @foreach ($tree->children() as $child)
              <li class="relative flex flex-col items-center px-1 pt-5 before:absolute before:top-0 before:left-1/2 before:h-5 before:w-px before:-translate-x-1/2 before:bg-hairline-strong after:absolute after:inset-x-0 after:top-0 after:h-px after:bg-hairline-strong first:after:left-1/2 last:after:right-1/2 only:after:hidden">
                @include('app.vaults.contacts._family-tree-node', $node($child, 32))
              </li>
            @endforeach
          </ul>
        @endif
      </div>
    </div>
  @endif
</x-panel>

<p class="mt-2.5 px-0.5 text-xs text-pretty text-muted">{{ $tree->scopeLine() }}</p>
