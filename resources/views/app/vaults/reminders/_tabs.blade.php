{{-- The ways of reading this vault's reminders: what is coming, what has gone out, what was answered. --}}
{{--
  @var string $activeUrl
  @var string $sentUrl
  @var int $activeCount
  @var int $sentCount
  @var string $historyUrl
  @var int $historyCount
  @var string $current
--}}
<x-tabs>
  <x-tabs.item
    :href="$activeUrl"
    :selected="$current === 'active'"
    :count="$activeCount"
    :count-label="trans_choice(':count reminder still coming|:count reminders still coming', $activeCount)"
    data-turbo="true"
  >{{ __('Coming up') }}</x-tabs.item>

  <x-tabs.item
    :href="$sentUrl"
    :selected="$current === 'sent'"
    :count="$sentCount"
    :count-label="trans_choice(':count reminder already sent|:count reminders already sent', $sentCount)"
    data-turbo="true"
  >{{ __('Sent') }}</x-tabs.item>

  <x-tabs.item
    :href="$historyUrl"
    :selected="$current === 'history'"
    :count="$historyCount"
    :count-label="trans_choice(':count answer kept|:count answers kept', $historyCount)"
    data-turbo="true"
  >{{ __('History') }}</x-tabs.item>
</x-tabs>
