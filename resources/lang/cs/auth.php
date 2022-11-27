<?php

/**
 * ⚠️ Editing not allowed except for 'en' language.
 *
 * @see https://github.com/monicahq/monica/blob/main/docs/contribute/translate.md for translations.
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

    'failed' => 'Tyto přihlašovací údaje neodpovídají žadnému záznamu.',
    'throttle' => 'Příliš mnoho pokusů o přihlášení. Zkuste to prosím znovu za :seconds vteřin.',
    'not_authorized' => 'Nejste oprávněni provést tuto akci',
    'signup_disabled' => 'Nové registrace jsou aktuálně zastaveny',
    'signup_error' => 'An error occured trying to register the user',
    'back_homepage' => 'Zpět na domovskou stránku',
    'mfa_auth_otp' => 'Authenticate with your two factor device',
    'mfa_auth_webauthn' => 'Authenticate with a security key (WebAuthn)',
    '2fa_title' => 'Two Factor Authentication',
    '2fa_wrong_validation' => 'The two factor authentication has failed.',
    '2fa_one_time_password' => 'Two factor authentication code',
    '2fa_recuperation_code' => 'Enter a two factor recovery code',
    '2fa_one_time_or_recuperation' => 'Enter a two factor authentication code or a recovery code',
    '2fa_otp_help' => 'Open up your two factor authentication mobile app and copy the code',

    'login_to_account' => 'Login to your account',
    'login_with_recovery' => 'Login with a recovery code',
    'login_again' => 'Please login again to your account',
    'email' => 'Email',
    'password' => 'Password',
    'recovery' => 'Recovery code',
    'login' => 'Login',
    'button_remember' => 'Remember Me',
    'password_forget' => 'Forget your password?',
    'password_reset' => 'Reset your password',
    'use_recovery' => 'Or you can use a <a href=":url">recovery code</a>',
    'signup_no_account' => 'Don’t have an account?',
    'signup' => 'Registrace',
    'create_account' => 'Vytvořte první účet <a href=":url">registrací</a>',
    'change_language_title' => 'Změnit jazyk:',
    'change_language' => 'Změnit jazyk na :lang',

    'password_reset_title' => 'Resetovat heslo',
    'password_reset_email' => 'E-mailová adresa',
    'password_reset_send_link' => 'Odeslat odkaz na obnovení hesla',
    'password_reset_password' => 'Heslo',
    'password_reset_password_confirm' => 'Potvrdit heslo',
    'password_reset_action' => 'Resetovat heslo',
    'password_reset_email_content' => 'Pro zresetování hesla klikněte na:',

    'register_title_welcome' => 'Vítejte v nově nainstalované instanci Monica',
    'register_create_account' => 'Abyste mohli používat aplikaci Monica je třeba založit účet',
    'register_title_create' => 'Založte svůj účet Monica',
    'register_login' => '<a href=":url">Přihlaste se</a>, pokud již máte účet.',
    'register_email' => 'Zadejte platnou e-mailovou adresu',
    'register_email_example' => 'ucet@domena',
    'register_firstname' => 'Jméno',
    'register_firstname_example' => 'např. Jan',
    'register_lastname' => 'Příjmení',
    'register_lastname_example' => 'např. Novák',
    'register_password' => 'Heslo',
    'register_password_example' => 'Zadejte bezpečné heslo',
    'register_password_confirmation' => 'Potvrzení hesla',
    'register_action' => 'Registrovat',
    'register_policy' => 'Signing up signifies you’ve read and agree to our <a href=":url" hreflang=":hreflang">Privacy Policy</a> and <a href=":urlterm" hreflang=":hreflang">Terms of use</a>.',
    'register_invitation_email' => 'For security purposes, please indicate the email of the person who’ve invited you to join this account. This information is provided in the invitation email.',

    'confirmation_title' => 'Verify Your Email Address',
    'confirmation_fresh' => 'A fresh verification link has been sent to your email address.',
    'confirmation_check' => 'Before proceeding, please check your email for a verification link.',
    'confirmation_request_another' => 'If you did not receive the email <a :action>click here to request another</a>.',

    'confirmation_again' => 'If you want to change your email address you can <a href=":url" class="alert-link">click here</a>.',
    'email_change_current_email' => 'Aktuální e-mailová adresa:',
    'email_change_title' => 'Změna e-mailové adresy',
    'email_change_new' => 'Nová e-mailová adresa',
    'email_changed' => 'Your email address has been changed. Check your mailbox to validate it.',
];
