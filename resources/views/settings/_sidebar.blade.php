<div class="col-xs-12 col-sm-3 sidebar-menu">
  <ul class="mb4">

    @if (Route::currentRouteName() == 'settings.index')
    <li class="selected">
      <i class="fa fa-cog" aria-hidden="true"></i>
      <strong>{{ trans('settings.sidebar_settings') }}</strong>
    </li>
    @else
    <li class="bg-white">
      <i class="fa fa-cog" aria-hidden="true"></i>
      <a href="/settings">{{ trans('settings.sidebar_settings') }}</a>
    </li>
    @endif

    @if (Route::currentRouteName() == 'settings.personalization')
    <li class="selected">
      <i class="fa fa-handshake-o" aria-hidden="true"></i>
      <strong>{{ trans('settings.sidebar_personalization') }}</strong>
    </li>
    @else
    <li class="bg-white">
      <i class="fa fa-handshake-o" aria-hidden="true"></i>
      <a href="/settings/personalization">{{ trans('settings.sidebar_personalization') }}</a>
    </li>
    @endif

    @if (Route::currentRouteName() == 'settings.export')
    <li class="selected">
      <i class="fa fa-cloud-download" aria-hidden="true"></i>
      <strong>{{ trans('settings.sidebar_settings_export') }}</strong>
    </li>
    @else
    <li class="bg-white">
      <i class="fa fa-cloud-download" aria-hidden="true"></i>
      <a href="/settings/export">{{ trans('settings.sidebar_settings_export') }}</a>
    </li>
    @endif

    @if (Route::currentRouteName() == 'settings.import')
    <li class="selected">
      <i class="fa fa-cloud-upload" aria-hidden="true"></i>
      <strong>{{ trans('settings.sidebar_settings_import') }}</strong>
    </li>
    @else
    <li class="bg-white">
      <i class="fa fa-cloud-upload" aria-hidden="true"></i>
      <a href="/settings/import">{{ trans('settings.sidebar_settings_import') }}</a>
    </li>
    @endif

    @if (Route::currentRouteName() == 'settings.users')
    <li class="selected">
      <i class="fa fa-user-circle-o" aria-hidden="true"></i>
      <strong>{{ trans('settings.sidebar_settings_users') }}</strong>
    </li>
    @else
    <li class="bg-white">
      <i class="fa fa-user-circle-o" aria-hidden="true"></i>
      <a href="/settings/users">{{ trans('settings.sidebar_settings_users') }}</a>
    </li>
    @endif

    @if (config('monica.requires_subscription') && auth()->user()->account->has_access_to_paid_version_for_free == false)
      @if (Route::currentRouteName() == 'settings.subscriptions.index')
      <li class="selected">
        <i class="fa fa-money" aria-hidden="true"></i>
        <strong>{{ trans('settings.sidebar_settings_subscriptions') }}</strong>
      </li>
      @else
      <li class="bg-white">
        <i class="fa fa-money" aria-hidden="true"></i>
        <a href="/settings/subscriptions">{{ trans('settings.sidebar_settings_subscriptions') }}</a>
      </li>
      @endif
    @endif

    @if (Route::currentRouteName() == 'settings.tags')
    <li class="selected">
      <i class="fa fa-tags" aria-hidden="true"></i>
      <strong>{{ trans('settings.sidebar_settings_tags') }}</strong>
    </li>
    @else
    <li class="bg-white">
      <i class="fa fa-tags" aria-hidden="true"></i>
      <a href="/settings/tags">{{ trans('settings.sidebar_settings_tags') }}</a>
    </li>
    @endif

    @if (Route::currentRouteName() == 'settings.api')
    <li class="selected">
      <i class="fa fa-random"></i>
      <strong>{{ trans('settings.sidebar_settings_api') }}</strong>
    </li>
    @else
    <li class="bg-white">
      <i class="fa fa-random"></i>
      <a href="/settings/api">{{ trans('settings.sidebar_settings_api') }}</a>
    </li>
    @endif

    @if (Route::currentRouteName() == 'settings.security')
    <li class="selected">
      <i class="fa fa-cog"></i>
      <strong>{{ trans('settings.sidebar_settings_security') }}</strong>
    </li>
    @else
    <li class="bg-white">
      <i class="fa fa-cog"></i>
      <a href="/settings/security">{{ trans('settings.sidebar_settings_security') }}</a>
    </li>
    @endif
  </ul>
</div>
