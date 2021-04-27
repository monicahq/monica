<?php

/**
 * ⚠️ Editing not allowed except for 'en' language.
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

    'failed' => 'Brukernavn eller passord stemmer ikke.',
    'throttle' => 'Du har forsøkt å logge inn for mange ganger. Prøv igjen om :seconds sekunder.',
    'not_authorized' => 'Du har ikke tilgang',
    'signup_disabled' => 'Beklager, men nye registreringer er for tiden ikke tillatt',
    'signup_error' => 'Det oppstod en feil ved forsøk på å registrere brukeren',
    'back_homepage' => 'Tilbake til hjemmesiden',
    'mfa_auth_otp' => 'Autentiser med din to-faktor enhet',
    'mfa_auth_webauthn' => 'Autentiser med en sikkerhetsnøkkel (WebAuthn)',
    '2fa_title' => 'To-faktor-autentisering',
    '2fa_wrong_validation' => 'To-faktor autentisering mislyktes.',
    '2fa_one_time_password' => 'Tofaktorautentiseringskode',
    '2fa_recuperation_code' => 'Skriv inn din tofaktorgjenopprettingskode',
    '2fa_otp_help' => 'Open up your two factor authentication mobile app and copy the code',

    'login_to_account' => 'Login to your account',
    'login_with_recovery' => 'Login with a recovery code',
    'login_again' => 'Please login again to your account',
    'email' => 'Email',
    'password' => 'Password',
    'recovery' => 'Recovery code',
    'login' => 'Login',
    'button_remember' => 'Remember Me',
    'password_forget' => 'Glemt passord?',
    'password_reset' => 'Tilbakestill passordet ditt',
    'use_recovery' => 'Eller du kan bruke en <a href=":url">gjenopprettingskode</a>',
    'signup_no_account' => 'Mangler du konto?',
    'signup' => 'Registrer deg',
    'create_account' => 'Create the first account by <a href=":url">signing up</a>',
    'change_language_title' => 'Change language:',
    'change_language' => 'Change language to :lang',

    'password_reset_title' => 'Reset Password',
    'password_reset_email' => 'E-Mail Address',
    'password_reset_send_link' => 'Send Password Reset Link',
    'password_reset_password' => 'Passord',
    'password_reset_password_confirm' => 'Bekreft passord',
    'password_reset_action' => 'Tilbakestill passord',
    'password_reset_email_content' => 'Klikk her for å tilbakestille passordet:',

    'register_title_welcome' => 'Velkommen til din nyinstallerte Monica-instans',
    'register_create_account' => 'Du må opprette en konto for å bruke Monica',
    'register_title_create' => 'Opprett konto',
    'register_login' => '<a href=":url">Logg inn</a> hvis du allerede har en konto.',
    'register_email' => 'Oppgi en gyldig e-postadresse',
    'register_email_example' => 'du@hjem',
    'register_firstname' => 'Fornavn',
    'register_firstname_example' => 'f.eks. Jon',
    'register_lastname' => 'Etternavn',
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
