
<p>Activity {{ $object->date_it_happened }}
    @foreach ($object->contacts as $contact)
        {{ $contact->getCompleteName() }}
    @endforeach
</p>
<p>{{ $object->summary }}</p>
<p class="bb">{{ $object->description }}</p>
