{{-- A numbered run of steps, counted by the browser so a step never carries its own number. --}}
<ol role="list" {{ $attributes->class('flex flex-col [counter-reset:step]') }}>
  {{ $slot }}
</ol>
