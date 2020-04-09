<div class="col-12 {{ \App\Helpers\LocaleHelper::getDirection() }}">
  <h3>
    🗣 {{ trans('people.conversation_list_title') }}

    <span class="relative {{ \App\Helpers\LocaleHelper::getDirection() == 'ltr' ? 'fr' : 'fl' }}" style="top: -7px;">
      <a href="{{ route('people.conversations.create', $contact) }}" class="btn edit-information">{{ trans('people.conversation_list_cta') }}</a>
    </span>
  </h3>
</div>

@if ($contact->conversations->count() > 0)

<div class="{{ \App\Helpers\LocaleHelper::getDirection() == 'ltr' ? 'fl' : 'fr' }} w-100 pb3 pt1 pl3 pr3">
  <div class="br2 bg-white mb4">
    <conversation-list hash="{{ $contact->hashID() }}"></conversation-list>
  </div>
</div>

@else

<div class="col-12" cy-name="conversation-blank-state">
    <div class="section-blank">
      <h3>{{ trans('people.conversation_blank', ['name' => $contact->first_name]) }}</h3>
      <a href="{{ route('people.conversations.create', $contact) }}" cy-name="add-conversation-button">{{ trans('people.conversation_list_cta') }}</a>
    </div>
</div>

@endif
