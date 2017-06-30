<div class="col-xs-12 col-sm-3 sidebar-menu">
  <ul>

    @if (Route::currentRouteName() == 'settings.index')
    <li class="selected">
      {{ trans('settings.sidebar_settings') }}
    </li>
    @else
    <li>
      <a href="/settings">{{ trans('settings.sidebar_settings') }}</a>
    </li>
    @endif

    @if (Route::currentRouteName() == 'settings.export')
    <li class="selected">
      {{ trans('settings.sidebar_settings_export') }}
    </li>
    @else
    <li>
      <a href="/settings/export">{{ trans('settings.sidebar_settings_export') }}</a>
    </li>
    @endif

    @if (Route::currentRouteName() == 'settings.import')
    <li class="selected">
      {{ trans('settings.sidebar_settings_import') }}
    </li>
    @else
    <li>
      <a href="/settings/import">{{ trans('settings.sidebar_settings_import') }}</a>
    </li>
    @endif

    @if (Route::currentRouteName() == 'settings.users')
    <li class="selected">
      {{ trans('settings.sidebar_settings_users') }}
    </li>
    @else
    <li>
      <a href="/settings/users">{{ trans('settings.sidebar_settings_users') }}</a>
    </li>
    @endif

    @if (config('monica.requires_subscription'))
      @if (Route::currentRouteName() == 'settings.subscriptions.index')
      <li class="selected">
        {{ trans('settings.sidebar_settings_subscriptions') }}
      </li>
      @else
      <li>
        <a href="/settings/subscriptions">{{ trans('settings.sidebar_settings_subscriptions') }}</a>
      </li>
      @endif
    @endif
  </ul>
</div>
