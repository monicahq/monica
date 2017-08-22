@if ($contacts->count() >=2 and !auth()->user()->account->canAccess())
  <p class="mb4">
    {{ trans('people.people_list_account_usage', ['current' => $contact->count(), 'limit' => config('monica.paid_plan_contact_limit')]) }}
  </p>
@endif

@if ($contacts->count() >=3 and !auth()->user()->account->canAccess())
  <div class="">
    <img src="/img/people/upgrade_account.png">
    <div class="pa3 br bl bb br2 b--black-10 br--bottom">
      <p class="mb3">{{ trans('people.people_list_account_upgrade_title') }}</p>
      <div class="tc">
        <a href="/settings/subscriptions" class="btn btn-secondary">☆ {{ trans('people.people_list_account_upgrade_cta') }}</a>
      </div>
    </div>
  </div>
@endif