<div class="col-xs-12 {{ \App\Helpers\LocaleHelper::getDirection() }}">
  <h3>
    🗣 Conversations

    <span class="relative {{ \App\Helpers\LocaleHelper::getDirection() == 'ltr' ? 'fr' : 'fl' }}" style="top: -7px;">
      <a href="{{ route('people.conversation.new', $contact) }}" class="btn edit-information">Log conversation</a>
    </span>
  </h3>
</div>

<div class="{{ \App\Helpers\LocaleHelper::getDirection() == 'ltr' ? 'fl' : 'fr' }} w-100 pb3 pt1 pl3 pr3">
  <div class="br2 bg-white mb4">
    <conversation-list hash="{{ $contact->hashID() }}"></conversation-list>
  </div>
</div>
