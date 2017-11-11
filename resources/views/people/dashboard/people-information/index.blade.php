<div class="sidebar-box">

  <p class="sidebar-box-title">
    <strong>{{ trans('people.section_contact_information') }}</strong>
  </p>

  <div class="people-information">
    <ul>

      {{-- Dead --}}
      @if ($contact->is_dead)
      <li>
        <i class="fa fa-ambulance"></i>
        @if (! is_null($contact->deceased_date))
          {{ trans('people.deceased_label_with_date', ['date' => \App\Helpers\DateHelper::getShortDate($contact->deceased_date)]) }}
        @else
          {{ trans('people.deceased_label') }}
        @endif
      </li>
      @endif

      @foreach ($contact->contactFields as $contactField)
      <li>
        <i class="{{ $contactField->contactFieldType->fontawesome_icon }}"></i>
        <a href="{{ $contactField->contactFieldType->protocol }}{{ $contactField->data }}">{{ $contactField->data }}</a>
      </li>
      @endforeach

      @foreach ($contact->addresses as $address)
      <li>
        <i class="fa fa-globe"></i>
        <a href="{{ $address->getGoogleMapAddress() }}" target="_blank">
          {{ $address->getFullAddress() }}
        </a>
        @if ($address->name != 'default')
        <span class="grey">({{ $address->name }})</span>
        @endif
      </li>
      @endforeach
    </ul>

    <p class="sidebar-box-paragraph">
      <a href="{{ route('people.introductions.edit', $contact) }}">{{ trans('app.edit') }}</a>
    </p>
  </div>

</div>
