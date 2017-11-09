@if($contact->getProgenitors()->count() != 0)
  <div class="sidebar-box progenitors">

    <p class="sidebar-box-title">
      <strong>Parents</strong>
    </p>

    <ul class="people-list">
      @foreach($contact->getProgenitors() as $parent)
      <li>

        <a href="/people/{{ $parent->id }}"><span class="name">{{ $parent->getCompleteName(auth()->user()->name_order) }}</span></a>

        @if (! is_null($parent->getAge()))
        ({{ $parent->getAge() }})
        @endif

      </li>
      @endforeach
    </ul>

  </div>
@endif
