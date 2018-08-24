<div class="col-xs-12 section-title {{ \App\Helpers\LocaleHelper::getDirection() }}">
  <h3>
    🗣 Conversations

    <span class="{{ \App\Helpers\LocaleHelper::getDirection() == 'ltr' ? 'fr' : 'fl' }}">
      <a href="#logCallModal" class="btn edit-information" data-toggle="modal">{{ trans('people.call_button') }}</a>
    </span>
  </h3>
</div>

<div class="{{ \App\Helpers\LocaleHelper::getDirection() == 'ltr' ? 'fl' : 'fr' }} w-100 pa3">
  <div class="br2 ba b--gray-monica bg-white mb4">
    <conversation-list :contact-id="{{ $contact->id }}"></conversation-list>
  </div>
</div>
