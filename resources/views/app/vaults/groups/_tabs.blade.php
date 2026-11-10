{{-- The three tabs of a group: what it did and noted, who is in it, and what it keeps. --}}
{{--
  @var \App\ViewModels\Vaults\GroupViewModel $viewModel
  @var string $current
--}}
@php
  $membersCount = $viewModel->membersCount();
  $filesCount = $viewModel->filesCount();
@endphp
<x-tabs :aria-label="__('Parts of the group :name', ['name' => $viewModel->name()])">
  <x-tabs.item :href="$viewModel->overviewUrl()" :selected="$current === 'overview'" icon="family" data-turbo="true">{{ __('Overview') }}</x-tabs.item>

  <x-tabs.item
    :href="$viewModel->membersUrl()"
    :selected="$current === 'members'"
    :count="$membersCount"
    :count-label="trans_choice(':count member|:count members', $membersCount)"
    data-turbo="true"
  >{{ __('Members') }}</x-tabs.item>

  @if ($filesCount !== null)
    <x-tabs.item
      :href="$viewModel->filesUrl()"
      :selected="$current === 'files'"
      :count="$filesCount"
      :count-label="trans_choice(':count file|:count files', $filesCount)"
      data-turbo="true"
    >{{ __('Files') }}</x-tabs.item>
  @endif
</x-tabs>
