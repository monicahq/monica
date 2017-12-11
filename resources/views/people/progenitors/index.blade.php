@if($contact->getProgenitors()->count() != 0)
  <div class="sidebar-box progenitors">

    <p class="sidebar-box-title">
      <strong>Parents</strong>
    </p>

    <ul class="people-list">
      @foreach($contact->getProgenitors() as $parent)
      <li>

        <a href="/people/{{ $parent->id }}"><span class="name">{{ $parent->getCompleteName(auth()->user()->name_order) }}</span></a>

        @if (! is_null($parent->birthday_special_date_id))
          @if ($parent->birthdate->getAge())
            ({{ $parent->birthdate->getAge() }})
          @endif
        @endif

      </li>
      @endforeach
    </ul>

  </div>
@endif
