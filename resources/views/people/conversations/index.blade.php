<div class="col-xs-12 section-title {{ \App\Helpers\LocaleHelper::getDirection() }}">
  <h3>
    🗣 Conversations

    <span class="{{ \App\Helpers\LocaleHelper::getDirection() == 'ltr' ? 'fr' : 'fl' }}">
      <a href="{{ route('people.conversation.new', $contact) }}" class="btn edit-information">Log conversation</a>
    </span>
  </h3>
</div>

<div class="{{ \App\Helpers\LocaleHelper::getDirection() == 'ltr' ? 'fl' : 'fr' }} w-100 pa3">
  <div class="br2 bg-white mb4">
    <conversation-list hash="{{ $contact->hashID() }}"></conversation-list>
  </div>
</div>
