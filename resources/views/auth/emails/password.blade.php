{{ trans('auth.password_reset_email_content') }}&nbsp;<a href="{{ $link = url('password/reset', $token).'?email='.urlencode($user->getEmailForPasswordReset()) }}">{{ $link }}</a>
