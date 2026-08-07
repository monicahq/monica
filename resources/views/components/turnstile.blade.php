{{--
  The Cloudflare Turnstile widget, and nothing at all on an instance that has not
  turned it on. Every guest form can therefore carry it unconditionally.
--}}
@if (config('turnstile.enabled'))
  @once
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
  @endonce

  <div class="space-y-2">
    <div {{ $attributes->merge(['class' => 'cf-turnstile']) }} data-sitekey="{{ config('turnstile.site_key') }}" data-theme="auto"></div>

    <x-error :messages="$errors->get('cf-turnstile-response')" />
  </div>
@endif
