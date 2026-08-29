{{--
  A row of tabs sitting on a rule that runs the whole width.

  The tab you are on thickens its own piece of the rule and nothing else: no
  enclosing pill, no card, nothing that looks like a button. That is what keeps a
  tab bar legible as a set of destinations rather than as a row of controls, and it
  is why the rule is drawn here, on the row, rather than by each tab.

  The attributes land on the row, so a caller that has to make the tabs draggable
  can put its Alpine data here and keep the markup below it unchanged.

  Every tab inside is an <x-tabs.item>.
--}}
<div role="tablist" {{ $attributes->class(['flex flex-wrap items-end gap-0.5 border-b border-hairline']) }}>
  {{ $slot }}
</div>
