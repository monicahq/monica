<div class="col-12 col-sm-3 sidebar-menu">
  <ul class="mb4">

    @component('components.sidebar', [
      'route' => 'settings.index',
      'icon' => 'fa fa-cog',
      'title' => 'settings.sidebar_settings'])
    @endcomponent

    @component('components.sidebar', [
      'route' => 'settings.personalization.index',
      'icon' => 'fa fa-handshake-o',
      'title' => 'settings.sidebar_personalization'])
    @endcomponent

    @component('components.sidebar', [
      'route' => 'settings.storage.index',
      'icon' => 'fa fa-cube',
      'title' => 'settings.sidebar_settings_storage'])
    @endcomponent

    @component('components.sidebar', [
      'route' => 'settings.export.index',
      'icon' => 'fa fa-cloud-download',
      'title' => 'settings.sidebar_settings_export'])
    @endcomponent

    @component('components.sidebar', [
      'route' => 'settings.import',
      'icon' => 'fa fa-cloud-upload',
      'title' => 'settings.sidebar_settings_import'])
    @endcomponent

    @component('components.sidebar', [
      'route' => 'settings.users.index',
      'icon' => 'fa fa-user-circle-o',
      'title' => 'settings.sidebar_settings_users'])
    @endcomponent

    @if (config('monica.requires_subscription') && ! auth()->user()->account->has_access_to_paid_version_for_free)
      @component('components.sidebar', [
        'route' => 'settings.subscriptions.index',
        'icon' => 'fa fa-money',
        'title' => 'settings.sidebar_settings_subscriptions'])
      @endcomponent
    @endif

    @component('components.sidebar', [
      'route' => 'settings.tags.index',
      'icon' => 'fa fa-tags',
      'title' => 'settings.sidebar_settings_tags'])
    @endcomponent

    @component('components.sidebar', [
      'route' => 'settings.api',
      'icon' => 'fa fa-random',
      'title' => 'settings.sidebar_settings_api'])
    @endcomponent

    @if (config('laravelsabre.enabled') && ! $accountHasLimitations)
      @component('components.sidebar', [
        'route' => 'settings.dav',
        'icon' => 'fa fa-calendar',
        'title' => 'settings.sidebar_settings_dav'])
      @endcomponent
    @endif

    @component('components.sidebar', [
      'route' => 'settings.auditlog.index',
      'icon' => 'fa fa-id-card-o',
      'title' => 'settings.sidebar_settings_auditlogs'])
    @endcomponent

    @component('components.sidebar', [
      'route' => 'settings.security.index',
      'icon' => 'fa fa-shield',
      'title' => 'settings.sidebar_settings_security'])
    @endcomponent

  </ul>
</div>
