<div class="col-xs-12 section-title">
  <img src="/img/people/gifts.svg" class="icon-section icon-gifts">
  <h3>{{ trans('people.section_personal_gifts') }}</h3>
</div>

@if ($contact->getGiftIdeas()->count() == 0 and $contact->getGiftsOffered()->count() == 0)

  <div class="col-xs-12">
    <h3>{{ trans('people.gifts_blank_title', ['name' => $contact->getFirstName()]) }}</h3>
    <div class="cta-blank">
      <a href="/people/{{ $contact->id }}/gifts/add" class="btn btn-primary">{{ trans('people.gifts_blank_add_gift') }}</a>
    </div>
    <div class="illustration-blank">
      <img src="/img/people/gifts/blank.svg">
      <p>{{ trans('people.gifts_blank_description', ['name' => $contact->getFirstName()]) }}</p>
    </div>
  </div>

@else

  <div class="col-xs-12 col-sm-9 gifts-list">

    @if ($contact->getGiftIdeas()->count() != 0)
      <h2 class="gift-recipient">Gifts ideas</h2>

      <ul class="gifts-list">
        @foreach ($contact->getGiftIdeas() as $gift)
          <li class="gift-list-item">
            {{ $gift->getName() }}

            @if (! is_null($gift->getValue()))
              <div class="gift-list-item-value">
                $ {{ $gift->getValue() }}
              </div>
            @endif

            @if (! is_null($gift->getUrl()))
              <div class="gift-list-item-url">
                <a href="{{ $gift->getUrl() }}">{{ trans('people.gifts_link') }}</a>
              </div>
            @endif

            <div class="gift-list-item-information">
              <div class="gift-list-item-date">
                {{ trans('people.gifts_added_on', ['date' => $gift->getCreatedAt()]) }}
              </div>
              <ul class="gift-list-item-actions">
                <li><a href="/people/{{ $contact->id }}/gifts/{{ $gift->id }}/delete" onclick="return confirm('{{ trans('people.gifts_delete_confirmation') }}')">{{ trans('people.gifts_delete_cta') }}</a></li>
              </ul>
            </div>
          </li>
        @endforeach
      </ul>
    @endif

    @if ($contact->getGiftsOffered()->count() != 0)
      <h2 class="gift-recipient">Gifts already offered</h2>

      <ul class="gifts-list">
        @foreach ($contact->getGiftsOffered() as $gift)
          <li class="gift-list-item">
            {{ $gift->getName() }}

            @if (! is_null($gift->getValue()))
              <div class="gift-list-item-value">
                $ {{ $gift->getValue() }}
              </div>
            @endif

            @if (! is_null($gift->getUrl()))
              <div class="gift-list-item-url">
                <a href="{{ $gift->getUrl() }}">{{ trans('people.gifts_link') }}</a>
              </div>
            @endif

            <div class="gift-list-item-information">
              <div class="gift-list-item-date">
                {{ trans('people.gifts_added_on', ['date' => $gift->getCreatedAt()]) }}
              </div>
              <ul class="gift-list-item-actions">
                <li><a href="/people/{{ $contact->id }}/gifts/{{ $gift->id }}/delete" onclick="return confirm('{{ trans('people.gifts_delete_confirmation') }}')">{{ trans('people.gifts_delete_cta') }}</a></li>
              </ul>
            </div>
          </li>
        @endforeach
      </ul>
    @endif

  </div>

  {{-- Sidebar --}}
  <div class="col-xs-12 col-sm-3 sidebar">

    <!-- Add activity  -->
    <div class="sidebar-cta hidden-xs-down">
      <a href="/people/{{ $contact->id }}/gifts/add" class="btn btn-primary">{{ trans('people.gifts_add_gift') }}</a>
    </div>
  </div>

@endif
