{{--
  Why passkeys cannot be used here. Shown in place of the control rather than
  beside it, because a control that fails when used is worse than one that is
  not there.

  A secure connection is the whole of the test, so there is one reason and one
  sentence. The old package carried a master switch as well; `laravel/passkeys`
  has none, and no deployment could reach it anyway.
--}}
<div class="px-4 py-3.5">
  <x-notice tone="warning">
    {{ __('Passkeys need a secure connection. Reach this instance over https and they will work.') }}
  </x-notice>
</div>
