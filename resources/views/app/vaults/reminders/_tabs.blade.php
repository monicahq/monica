{{--
  The two ways of reading this vault's reminders: what is coming, and what has
  already gone out.

  Each is an address rather than a filter, so both can be linked to and the back
  button moves between them. The count on each is the number of rows behind it,
  which is what makes the bar worth reading before it is clicked: a Sent tab
  showing nothing says the engine has never fired, and that is the first thing
  somebody hunting a missing reminder needs to know.

  The counts are named as well as drawn, since a bare number beside a word is not
  a sentence for anybody listening rather than looking.

  @var string $activeUrl
  @var string $sentUrl
  @var int $activeCount
  @var int $sentCount
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
</x-tabs>
