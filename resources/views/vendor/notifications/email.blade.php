{{-- Laravel's notification email, published so every line of it goes through the translations. --}}
<x-mail::message>
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# {{ __('Something went wrong') }}
@else
# {{ __('Hello!') }}
@endif
@endif

@foreach ($introLines as $line)
{{ $line }}

@endforeach

@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

@if (! empty($panel))
<x-mail::panel>
{{ $panel }}
</x-mail::panel>
@endif

<x-mail::closing>
@foreach ($outroLines as $line)
{{ $line }}

@endforeach
@if (! empty($salutation))
{{ $salutation }}
@else
{{ __('Thanks,') }}<br>
{{ config('app.name') }}
@endif
</x-mail::closing>

@isset($actionText)
<x-slot:subcopy>
{{ __(
    "If the \":actionText\" button does not work, copy and paste the address below into your browser:",
    [
        'actionText' => $actionText,
    ]
) }} <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
