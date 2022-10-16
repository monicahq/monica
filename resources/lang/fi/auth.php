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

    'failed' => 'Nämä tiedot eivät vastaa tietojamme.',
    'throttle' => 'Liian monta kirjautumisyritystä. Yritä uudelleen :seconds sekunnin kuluttua.',
    'not_authorized' => 'Sinulla ei ole oikeutta suorittaa tätä toimintoa',
    'signup_disabled' => 'Rekisteröinti on poistettu käytöstä',
    'signup_error' => 'Tapahtui virhe yritettäessä rekisteröidä käyttäjää',
    'back_homepage' => 'Takaisin kotisivulle',
    'mfa_auth_otp' => 'Todenna kaksivaiheisella laitteellasi',
    'mfa_auth_webauthn' => 'Todenna turvaavaimella (WebAuthn)',
    '2fa_title' => 'Kaksivaiheinen tunnistautuminen',
    '2fa_wrong_validation' => 'Kaksitasoinen todennus epäonnistui.',
    '2fa_one_time_password' => 'Kaksivaiheisen todennuksen koodi',
    '2fa_recuperation_code' => 'Kirjoita kaksivaiheisen todennuksen palautuskoodi',
    '2fa_one_time_or_recuperation' => 'Syötä kaksivaiheinen tunnistautumiskoodi tai palautuskoodi',
    '2fa_otp_help' => 'Avaa kaksivaiheinen autentikointi mobiilisovellus ja kopioi koodi',

    'login_to_account' => 'Kirjaudu tilillesi',
    'login_with_recovery' => 'Kirjaudu käyttäen palautuskoodia',
    'login_again' => 'Ole hyvä ja kirjaudu uudelleen tilillesi',
    'email' => 'Sähköposti',
    'password' => 'Salasana',
    'recovery' => 'Palautuskoodi',
    'login' => 'Kirjaudu',
    'button_remember' => 'Muista minut',
    'password_forget' => 'Unohditko salasanasi?',
    'password_reset' => 'Nollaa salasanasi',
    'use_recovery' => 'Tai voit käyttää <a href=":url">palautuskoodia</a>',
    'signup_no_account' => 'Eikö sinulla ole tiliä?',
    'signup' => 'Rekisteröidy nyt',
    'create_account' => 'Luo ensimmäinen tili <a href=":url">rekisteröitymällä</a>',
    'change_language_title' => 'Vaihda kieltä:',
    'change_language' => 'Vaihda kieli :lang',

    'password_reset_title' => 'Nollaa Salasana',
    'password_reset_email' => 'Sähköpostiosoite',
    'password_reset_send_link' => 'Lähetä Salasanan Nollauslinkki',
    'password_reset_password' => 'Salasana',
    'password_reset_password_confirm' => 'Vahvista Salasana',
    'password_reset_action' => 'Resetoi salasana',
    'password_reset_email_content' => 'Klikkaa tästä nollataksesi salasanan:',

    'register_title_welcome' => 'Tervetuloa juuri asennettuun Monica instanssiin',
    'register_create_account' => 'Sinun täytyy luoda tili käyttääksesi Monicaa',
    'register_title_create' => 'Luo Monica tilisi',
    'register_login' => '<a href=":url">Kirjaudu sisään</a> jos sinulla on jo tili.',
    'register_email' => 'Syötä toimiva sähköpostiosoite',
    'register_email_example' => 'sinä@koti',
    'register_firstname' => 'Etunimi',
    'register_firstname_example' => 'esim. Joni',
    'register_lastname' => 'Sukunimi',
    'register_lastname_example' => 'esim. Räikkönen',
    'register_password' => 'Salasana',
    'register_password_example' => 'Syötä turvallinen salasana',
    'register_password_confirmation' => 'Salasanan vahvistus',
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
