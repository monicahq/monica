{{-- Laravel's notification email, published so every line of it goes through the translations. --}}
<x-mail::message>
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# {{ __('Whoops!') }}
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

@foreach ($outroLines as $line)
{{ $line }}

@endforeach

@if (! empty($salutation))
{{ $salutation }}
@else
{{ __('Thanks,') }}<br>
{{ config('app.name') }}
@endif

@isset($actionText)
<x-slot:subcopy>
{{ __(
    "If you’re having trouble clicking the \":actionText\" button, copy and paste the URL below into your web browser:",
    [
        'actionText' => $actionText,
    ]
) }} <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
