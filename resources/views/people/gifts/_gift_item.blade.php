<h2 class="gift-recipient">Gifts for {{ $name }}</h2>

{{-- Gift ideas --}}
<ul class="gifts-list">
  @foreach($gifts as $gift)

    @if ($gift->is_an_idea == 'true')
    <li class="gift-list-item">
      {{ $gift->getTitle() }}

      @if (!is_null($gift->getValue()))
      <div class="gift-list-item-value">
        $ {{ $gift->getValue() }}
      </div>
      @endif

      @if (!is_null($gift->getUrl()))
      <div class="gift-list-item-url">
        <a href="{{ $gift->getUrl() }}">{{ trans('people.gifts_link') }}</a>
      </div>
      @endif

      <div class="gift-list-item-information">
        <div class="gift-list-item-date">
          {{ trans('people.gifts_added_on', ['date' => $gift->getAddedDate()]) }}
        </div>
        <ul class="gift-list-item-actions">
          <li><a href="/people/{{ $people->id }}/gifts/{{ $gift->id }}/delete" onclick="return confirm('{{ trans('people.gifts_delete_confirmation') }}')">{{ trans('people.gifts_delete_cta') }}</a></li>
        </ul>
      </div>
    </li>
    @endif
  @endforeach
</ul>

{{-- Gifts offered --}}
<ul class="gifts-list">
  @foreach($gifts as $gift)

    @if ($gift->has_been_offered == 'true')
    <li class="gift-list-item">

      <span class="offered">{{ trans('people.gifts_offered') }}</span>

      {{ $gift->getTitle() }}

      @if (!is_null($gift->getValue()))
      <div class="gift-list-item-value">
        $ {{ $gift->getValue() }}
      </div>
      @endif

      @if (!is_null($gift->getUrl()))
      <div class="gift-list-item-url">
        <a href="{{ $gift->getUrl() }}">{{ trans('people.gifts_link') }}</a>
      </div>
      @endif

      <div class="gift-list-item-information">
        <div class="gift-list-item-date">
          {{ trans('people.gifts_added_on', ['date' => $gift->getAddedDate()]) }}
        </div>
        <ul class="gift-list-item-actions">
          <li><a href="/people/{{ $people->id }}/gifts/{{ $gift->id }}/delete" onclick="return confirm('{{ trans('people.gifts_delete_confirmation') }}')">{{ trans('people.gifts_delete_cta') }}</a></li>
        </ul>
      </div>
    </li>
    @endif
  @endforeach
</ul>