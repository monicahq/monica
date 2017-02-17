Hi {{ $user->first_name }},

{{-- PHONE CALL --}}
@if ($reminder->getReminderType() == 'phone_call')
YOU SHOULD CALL:
{{ $contact->getCompleteName() }}
@endif
{{-- LUNCH --}}
@if ($reminder->getReminderType() == 'lunch')
YOU HAVE TO GRAB A LUNCH WITH:
{{ $contact->getCompleteName() }}
@endif
{{-- HANGOUT --}}
@if ($reminder->getReminderType() == 'hangout')
YOU SHOULD HANGOUT WITH:
{{ $contact->getCompleteName() }}
@endif
{{-- EMAIL --}}
@if ($reminder->getReminderType() == 'email')
YOU SHOULD WRITE AN EMAIL TO:
{{ $contact->getCompleteName() }}
@endif
{{-- CUSTOM --}}
@if (is_null($reminder->getReminderType()))
YOU WANTED TO BE REMINDED OF:
{{ $reminder->getTitle() }}
FOR:
{{ $contact->getCompleteName() }}
@endif

{{-- COMMENTS --}}
@if (! is_null($reminder->getDescription()))
COMMENT:
{{ $reminder->getDescription() }}
@endif

{{-- SIGNIFICANT OTHER and KIDS --}}
@if (! is_null($contact->getCurrentSignificantOther()) or $contact->getNumberOfKids() != 0)
-------
@if (! is_null($contact->getCurrentSignificantOther()))
Significant other: {{ $contact->getCurrentSignificantOther()->getCompleteName() }}, {{ $contact->getCurrentSignificantOther()->getAge() }}
@endif
@if ($contact->getNumberOfKids() != 0)
Kids:
@foreach ($contact->getKids() as $kid)
{{ $kid->getFirstName() }} ({{ $kid->getAge() }})
@endforeach
@endif
@endif

-------

Add, view, complete, and change information about this contact:
{{ env('APP_URL') }}/people/{{ $contact->id }}
