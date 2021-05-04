<div class="{{ htmldir() == 'ltr' ? 'fl' : 'fr' }} w-100 pb3 pt1 pl3 pr3">
  <div class="br2 bg-white mb4">

    @if (config('monica.requires_subscription') && $accountHasLimitations)

    <div class="">
        <h3>
            📄&#8199;{{ trans('people.document_list_title') }}
        </h3>

        <div class="section-blank">
          <p>{{ trans('settings.storage_upgrade_notice') }}</p>
        </div>
    </div>

    @else

    <document-list hash="{{ $contact->hashID() }}" reach-limit="{{ \Safe\json_encode($hasReachedAccountStorageLimit) }}"></document-list>

    @endif
  </div>
</div>
