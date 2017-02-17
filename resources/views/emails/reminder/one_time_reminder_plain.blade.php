{{ $userFirstname }},

YOU WANT TO BE REMINDED TODAY FOR:
{{ $reason }}

ABOUT

{{ $peopleCompleteName }}

@include('emails.partials._contact_details')

------

@include('emails.partials._contact_family')

------

@include('emails.partials._last_time')

------

Add, view, complete, and change information about this contact:
{{ $urlContact }}
