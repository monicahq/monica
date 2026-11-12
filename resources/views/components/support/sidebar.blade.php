{{-- The column beside the support screens: the requests, a new one, the guides, and the way out. --}}
{{--
  @var string $current
  @var int $openCount
--}}
@props([
  'current' => 'new',
  'openCount' => 0,
])

@php
  $groups = [
    [
      'title' => __('Support'),
      'entries' => [
        ['key' => 'requests', 'label' => __('Your requests'), 'icon' => 'mail', 'url' => route('support.requests.index'), 'count' => $openCount, 'countLabel' => trans_choice(':count open request|:count open requests', $openCount)],
        ...(\App\Helpers\SupportAvailability::isOffered() ? [['key' => 'new', 'label' => __('New request'), 'icon' => 'plus', 'url' => route('support.requests.new')]] : []),
      ],
    ],
    [
      'title' => __('Resources'),
      'entries' => [
        ['key' => 'docs', 'label' => __('Documentation'), 'icon' => 'journal', 'url' => route('docs.index')],
        ['key' => 'api', 'label' => __('API docs'), 'icon' => 'link', 'url' => route('docs.api.index')],
      ],
    ],
  ];
@endphp

<x-nav-drawer
  :groups="$groups"
  :current="$current"
  :leave-url="route('vaults.index')"
  :leave-label="__('Leave support')"
  :label="__('Support')"
/>
