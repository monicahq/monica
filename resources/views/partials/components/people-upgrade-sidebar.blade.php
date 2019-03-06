@if (auth()->user()->account->hasLimitations())
  <div class="mb4">
    <img src="img/people/upgrade_account.png">
    <div class="pa3 br bl bb br2 b--black-10 br--bottom bg-white">
      <p class="mb3">{{ trans('people.people_list_account_upgrade_title') }}</p>
      <div class="tc">
        <a href="{{ route('settings.subscriptions.index') }}" class="btn btn-secondary">☆ {{ trans('people.people_list_account_upgrade_cta') }}</a>
      </div>
    </div>
  </div>
@endif