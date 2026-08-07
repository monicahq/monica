{{--
  @var string $url
  @var int $minutes
--}}
<x-mail::message>
# {{ __('Your sign-in link') }}

{{ __('Click the button below and you are in. No password needed.') }}

<x-mail::button :url="$url">
{{ __('Sign in to :app', ['app' => config('app.name')]) }}
</x-mail::button>

{{ __('The link works once, and only for the next :count minutes.', ['count' => $minutes]) }}

<x-mail::panel>
{{ __('If you did not ask for this link, you can ignore this email. Somebody typed your address by mistake, or is trying to find out whether you have an account.') }}
</x-mail::panel>

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
