<div class="col-xs-12 col-sm-3 sidebar-menu">
  <ul>

    @if (Route::currentRouteName() == 'settings.index')
    <li class="selected">
      <i class="fa fa-cog" aria-hidden="true"></i>
      {{ trans('settings.sidebar_settings') }}
    </li>
    @else
    <li>
      <i class="fa fa-cog" aria-hidden="true"></i>
      <a href="/settings">{{ trans('settings.sidebar_settings') }}</a>
    </li>
    @endif

    @if (Route::currentRouteName() == 'settings.export')
    <li class="selected">
      <i class="fa fa-cloud-download" aria-hidden="true"></i>
      {{ trans('settings.sidebar_settings_export') }}
    </li>
    @else
    <li>
      <i class="fa fa-cloud-download" aria-hidden="true"></i>
      <a href="/settings/export">{{ trans('settings.sidebar_settings_export') }}</a>
    </li>
    @endif

    @if (Route::currentRouteName() == 'settings.import')
    <li class="selected">
      <i class="fa fa-cloud-upload" aria-hidden="true"></i>
      {{ trans('settings.sidebar_settings_import') }}
    </li>
    @else
    <li>
      <i class="fa fa-cloud-upload" aria-hidden="true"></i>
      <a href="/settings/import">{{ trans('settings.sidebar_settings_import') }}</a>
    </li>
    @endif

    @if (Route::currentRouteName() == 'settings.users')
    <li class="selected">
      <i class="fa fa-user-circle-o" aria-hidden="true"></i>
      {{ trans('settings.sidebar_settings_users') }}
    </li>
    @else
    <li>
      <i class="fa fa-user-circle-o" aria-hidden="true"></i>
      <a href="/settings/users">{{ trans('settings.sidebar_settings_users') }}</a>
    </li>
    @endif

    @if (config('monica.requires_subscription'))
      @if (Route::currentRouteName() == 'settings.subscriptions.index')
      <li class="selected">
        <i class="fa fa-money" aria-hidden="true"></i>
        {{ trans('settings.sidebar_settings_subscriptions') }}
      </li>
      @else
      <li>
        <i class="fa fa-money" aria-hidden="true"></i>
        <a href="/settings/subscriptions">{{ trans('settings.sidebar_settings_subscriptions') }}</a>
      </li>
      @endif
    @endif

    @if (Route::currentRouteName() == 'settings.tags')
    <li class="selected">
      <i class="fa fa-tags" aria-hidden="true"></i>
      {{ trans('settings.sidebar_settings_tags') }}
    </li>
    @else
    <li>
      <i class="fa fa-tags" aria-hidden="true"></i>
      <a href="/settings/tags">{{ trans('settings.sidebar_settings_tags') }}</a>
    </li>
    @endif

    {{-- @if (Route::currentRouteName() == 'settings.api')
    <li class="selected">
      <i class="fa fa-random"></i>
      {{ trans('settings.sidebar_settings_api') }}
    </li>
    @else
    <li>
      <i class="fa fa-random"></i>
      <a href="/settings/api">{{ trans('settings.sidebar_settings_api') }}</a>
    </li>
    @endif --}}
  </ul>
</div>
