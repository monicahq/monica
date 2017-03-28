<div class="col-xs-12 section-title">
  <img src="/img/people/gifts.svg" class="icon-section icon-gifts">
  <h3>
    {{ trans('people.section_personal_gifts') }}

    <span>
      <a href="/people/{{ $contact->id }}/gifts/add">{{ trans('people.gifts_add_gift') }}</a>
    </span>
  </h3>
</div>

@if ($contact->getGiftIdeas()->count() == 0 and $contact->getGiftsOffered()->count() == 0)

  <div class="col-xs-12">
    <div class="section-blank">
      <h3>{{ trans('people.gifts_blank_title', ['name' => $contact->getFirstName()]) }}</h3>
      <a href="/people/{{ $contact->id }}/gifts/add">{{ trans('people.gifts_blank_add_gift') }}</a>
    </div>
  </div>

@else

  <div class="col-xs-12 gifts-list">

    <p>{{ trans('people.gifts_blank_title', ['name' => $contact->getFirstName()]) }}</p>

    @if ($contact->getGiftIdeas()->count() != 0)
      <h2 class="gift-recipient">{{ trans('people.gifts_gift_idea') }}</h2>

      <table class="table table-sm table-hover">
        <thead>
          <tr>
            <th>Date added</th>
            <th>Description</th>
            <th class="actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($contact->getGiftIdeas() as $gift)
            <tr>
              <td class="date">
                {{ \App\Helpers\DateHelper::getShortDate($gift->getCreatedAt(), Auth::user()->locale) }}
              </td>
              <td>
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
              </td>
              <td class="actions">
                <ul class="gift-list-item-actions">
                  <li><a href="/people/{{ $contact->id }}/gifts/{{ $gift->id }}/delete" onclick="return confirm('{{ trans('people.gifts_delete_confirmation') }}')">{{ trans('people.gifts_delete_cta') }}</a></li>
                </ul>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif

    @if ($contact->getGiftsOffered()->count() != 0)
      <h2 class="gift-recipient">Gifts already offered</h2>

      <table class="table table-sm table-hover">
        <thead>
          <tr>
            <th>Date added</th>
            <th>Description</th>
            <th class="actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($contact->getGiftsOffered() as $gift)
            <tr>
              <td class="date">
                {{ \App\Helpers\DateHelper::getShortDate($gift->getCreatedAt(), Auth::user()->locale) }}
              </td>
              <td>
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
              </td>
              <td class="actions">
                <ul class="gift-list-item-actions">
                  <li><a href="/people/{{ $contact->id }}/gifts/{{ $gift->id }}/delete" onclick="return confirm('{{ trans('people.gifts_delete_confirmation') }}')">{{ trans('people.gifts_delete_cta') }}</a></li>
                </ul>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif

  </div>

@endif
