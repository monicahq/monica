<?php

/**
 * ⚠️ Edition not allowed except for 'en' language.
 *
 * @see https://github.com/monicahq/monica/blob/master/docs/contribute/translate.md for translations.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'not_authorized' => 'You are not authorized to execute this action',
    'signup_disabled' => 'Registration is currently disabled',
    'signup_error' => 'An error occured trying to register the user',
    'back_homepage' => 'Back to homepage',
    'mfa_auth_otp' => 'Authenticate with your two factor device',
    'mfa_auth_webauthn' => 'Authenticate with a security key (WebAuthn)',
    '2fa_title' => 'Two Factor Authentication',
    '2fa_wrong_validation' => 'Ο έλεγχος ταυτότητας δύο παραγόντων απέτυχε.',
    '2fa_one_time_password' => 'Κωδικός ελέγχου ταυτότητας δύο παραγόντων',
    '2fa_recuperation_code' => 'Πληκτρολογήστε έναν κωδικό ελέγχου ταυτότητας δύο παραγόντων',
    '2fa_otp_help' => 'Ανοίξτε την εφαρμογή ελέγχου ταυτότητας δύο παραγόντων στο κινητό σας και αντιγράψτε τον κωδικό',

    'login_to_account' => 'Συνδεθείτε στο λογαριασμό σας',
    'login_with_recovery' => 'Συνδεθείτε με έναν κωδικό ανάκτησης',
    'login_again' => 'Παρακαλούμε συνδεθείτε στο λογαριασμό σας ξανά',
    'email' => 'Email',
    'password' => 'Κωδικός',
    'recovery' => 'Κωδικός ανάκτησης',
    'login' => 'Σύνδεση',
    'button_remember' => 'Να με θυμάσαι',
    'password_forget' => 'Ξεχάσατε τον κωδικό πρόσβασης;',
    'password_reset' => 'Επαναφορά κωδικού πρόσβασης',
    'use_recovery' => 'Ή μπορείτε να χρησιμοποιήσετε έναν <a href=":url">κωδικό επαναφοράς</a>',
    'signup_no_account' => 'Δεν έχετε λογαριασμό;',
    'signup' => 'Εγγραφή',
    'create_account' => 'Δημιουργήστε τον πρώτο λογαριασμό με <a href=":url">εγγραφή</a>',
    'change_language_title' => 'Αλλαγή γλώσσας:',
    'change_language' => 'Αλλαγή γλώσσας σε :lang',

    'password_reset_title' => 'Επαναφορά κωδικού πρόσβασης',
    'password_reset_email' => 'Διεύθυνση E-mail',
    'password_reset_send_link' => 'Αποστολή συνδέσμου επαναφοράς κωδικού πρόσβασης',
    'password_reset_password' => 'Κωδικός πρόσβασης',
    'password_reset_password_confirm' => 'Επιβεβαίωση Κωδικού πρόσβασης',
    'password_reset_action' => 'Reset Password',
    'password_reset_email_content' => 'Click here to reset your password:',

    'register_title_welcome' => 'Welcome to your newly installed Monica instance',
    'register_create_account' => 'You need to create an account to use Monica',
    'register_title_create' => 'Create your Monica account',
    'register_login' => '<a href=":url">Log in</a> if you already have an account.',
    'register_email' => 'Enter a valid email address',
    'register_email_example' => 'you@home',
    'register_firstname' => 'First name',
    'register_firstname_example' => 'eg. John',
    'register_lastname' => 'Last name',
    'register_lastname_example' => 'eg. Doe',
    'register_password' => 'Password',
    'register_password_example' => 'Enter a secure password',
    'register_password_confirmation' => 'Password confirmation',
    'register_action' => 'Register',
    'register_policy' => 'Signing up signifies you’ve read and agree to our <a href=":url" hreflang=":hreflang">Privacy Policy</a> and <a href=":urlterm" hreflang=":hreflang">Terms of use</a>.',
    'register_invitation_email' => 'For security purposes, please indicate the email of the person who’ve invited you to join this account. This information is provided in the invitation email.',

    'confirmation_title' => 'Verify Your Email Address',
    'confirmation_fresh' => 'A fresh verification link has been sent to your email address.',
    'confirmation_check' => 'Before proceeding, please check your email for a verification link.',
    'confirmation_request_another' => 'If you did not receive the email <a :action>click here to request another</a>.',

    'confirmation_again' => 'If you want to change your email address you can <a href=":url" class="alert-link">click here</a>.',
    'email_change_current_email' => 'Current email address:',
    'email_change_title' => 'Change your email address',
    'email_change_new' => 'New email address',
    'email_changed' => 'Your email address has been changed. Check your mailbox to validate it.',
];
