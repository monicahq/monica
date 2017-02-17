{{ $userFirstname }},

YOU SHOULD GO TO LUNCH WITH:
{{ $peopleCompleteName }}

@include('emails.partials._contact_details')

------

@include('emails.partials._contact_family')

------

@include('emails.partials._last_time')

------

Add, view, complete, and change information about this contact:
{{ $urlContact }}
