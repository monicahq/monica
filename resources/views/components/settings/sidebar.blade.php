{{-- The column beside the settings screens, and the way back out of them. --}}
{{--
  @var \App\Models\User $user
  @var string $current
--}}
@props([
  'user',
  'current' => 'profile',
])

@php
  $groups = [
    [
      'title' => __('You'),
      'entries' => [
        ['key' => 'profile', 'label' => __('Profile'), 'icon' => 'profile', 'url' => route('settings.profile.index')],
        ['key' => 'security', 'label' => __('Security and access'), 'icon' => 'lock', 'url' => route('settings.security.index')],
        ['key' => 'preferences', 'label' => __('Preferences'), 'icon' => 'settings', 'url' => route('settings.preferences.index')],
        ['key' => 'notifications', 'label' => __('Notifications'), 'icon' => 'bell', 'url' => route('settings.notifications.index')],
        ['key' => 'logs', 'label' => __('Logs'), 'icon' => 'timeline', 'url' => route('settings.logs.index')],
        ['key' => 'transfer', 'label' => __('Export and import'), 'icon' => 'file', 'url' => route('settings.accountImports.index')],
      ],
    ],
    [
      'title' => __('Contacts and types'),
      'entries' => [
        ['key' => 'entity-types', 'label' => __('Contacts and more'), 'icon' => 'organization', 'url' => route('settings.entityTypes.index')],
        ['key' => 'templates', 'label' => __('Templates'), 'icon' => 'file', 'url' => route('settings.templates.index')],
        ['key' => 'value-lists', 'label' => __('Value lists'), 'icon' => 'tasks', 'url' => route('settings.valueLists.index')],
        ['key' => 'relationship-types', 'label' => __('Relationship types'), 'icon' => 'relationship', 'url' => route('settings.relationshipTypes.index')],
        ['key' => 'life-event-types', 'label' => __('Life event types'), 'icon' => 'activity', 'url' => route('settings.lifeEventTypes.index')],
      ],
    ],
    [
      'title' => __('Possessions'),
      'entries' => [
        ['key' => 'locations', 'label' => __('Locations'), 'icon' => 'place', 'url' => route('settings.locations.index')],
        ['key' => 'possession-types', 'label' => __('Possession types'), 'icon' => 'tag', 'url' => route('settings.possessionTypes.index')],
        ['key' => 'possession-tags', 'label' => __('Possession tags'), 'icon' => 'hash', 'url' => route('settings.possessionTags.index')],
        ['key' => 'possession-conditions', 'label' => __('Possession conditions'), 'icon' => 'star', 'url' => route('settings.possessionConditions.index')],
      ],
    ],
    ...(config('monica.self_hosted') ? [] : [[
      'title' => __('Account'),
      'entries' => [
        ['key' => 'billing', 'label' => __('Billing'), 'icon' => 'coin', 'url' => route('settings.billing.index')],
      ],
    ]]),
  ];
@endphp

<x-nav-drawer
  :groups="$groups"
  :current="$current"
  :leave-url="route('vaults.index')"
  :leave-label="__('Leave the settings')"
  :label="__('Settings')"
/>
