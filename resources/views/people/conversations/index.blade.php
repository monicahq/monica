<div class="col-xs-12 section-title {{ \App\Helpers\LocaleHelper::getDirection() }}">
  <h3>
    🗣 Conversations

    <span class="{{ \App\Helpers\LocaleHelper::getDirection() == 'ltr' ? 'fr' : 'fl' }}">
      <a href="#logCallModal" class="btn edit-information" data-toggle="modal">{{ trans('people.call_button') }}</a>
    </span>
  </h3>
</div>

<div class="{{ \App\Helpers\LocaleHelper::getDirection() == 'ltr' ? 'fl' : 'fr' }} w-100 pa2">
  <div class="br3 ba b--gray-monica bg-white mb4">
    <div class="dt dt--fixed w-100 collapse br--top br--bottom">
      <div class="dt-row">
        <div class="dtc">
          July 29
        </div>
        <div class="dtc">
          <span>3 messages</span>
          Oui. Dot dot dot.
        </div>
        <div class="dtc">
          <i class="fa fa-pencil-square-o pointer pr2"></i>
          <i class="fa fa-trash-o pointer"></i>
        </div>
      </div>

    </div>
  </div>
</div>

