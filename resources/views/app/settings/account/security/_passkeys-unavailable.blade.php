{{--
  Why passkeys cannot be used here. Shown in place of the control rather than
  beside it, because a control that fails when used is worse than one that is
  not there.

  @var string $reason
--}}
<div class="px-4 py-3.5">
  <x-notice tone="warning">
    @if ($reason === \App\Helpers\PasskeyAvailability::NOT_SECURE)
      {{ __('Passkeys need a secure connection. Reach this instance over https and they will work.') }}
    @else
      {{ __('Passkeys are switched off on this instance.') }}
    @endif
  </x-notice>
</div>
