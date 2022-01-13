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
    '2fa_one_time_or_recuperation' => 'Skriv inn en to-faktor autentiseringskode eller en gjenopprettingskode',
    '2fa_otp_help' => 'Åpne din To-faktor autentiseringsapp og kopier koden',

    'login_to_account' => 'Logg på kontoen din',
    'login_with_recovery' => 'Logg inn med en gjenopprettingskode',
    'login_again' => 'Vennligst logg inn på kontoen din igjen',
    'email' => 'E-post',
    'password' => 'Passord',
    'recovery' => 'Gjenopprettingskode',
    'login' => 'Logg på',
    'button_remember' => 'Husk meg',
    'password_forget' => 'Glemt passord?',
    'password_reset' => 'Tilbakestill passordet ditt',
    'use_recovery' => 'Eller du kan bruke en <a href=":url">gjenopprettingskode</a>',
    'signup_no_account' => 'Mangler du konto?',
    'signup' => 'Registrer deg',
    'create_account' => 'Opprett den første kontoen ved å <a href=":url">registrere deg</a>',
    'change_language_title' => 'Bytt språk:',
    'change_language' => 'Endre språk til :lang',

    'password_reset_title' => 'Tilbakestill passord',
    'password_reset_email' => 'E-postadresse',
    'password_reset_send_link' => 'Send lenke for tilbakestilling av passord',
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
    'register_lastname_example' => 'f.eks. Smith',
    'register_password' => 'Passord',
    'register_password_example' => 'Skriv inn et sikkert passord',
    'register_password_confirmation' => 'Passord (bekreft)',
    'register_action' => 'Registrer',
    'register_policy' => 'Registrering bekrefter at du har lest og godtar vår <a href=":url" hreflang=":hreflang">personvernerklæring</a> og våre <a href=":urlterm" hreflang=":hreflang">brukervilkår</a>.',
    'register_invitation_email' => 'Av sikkerhetsgrunner kan du oppgi e-postadressen til personen som har invitert deg til å delta i denne kontoen. Denne informasjonen er gitt i invitasjonse-posten.',

    'confirmation_title' => 'Verifiser din e-postadresse',
    'confirmation_fresh' => 'En bekreftelseslenke har blitt sendt til din e-postadresse.',
    'confirmation_check' => 'Før du fortsetter må du sjekke e-posten din for verifiseringslenken.',
    'confirmation_request_another' => 'Hvis du ikke mottok e-posten, <a :action>klikk her for å be om en ny</a>.',

    'confirmation_again' => 'Hvis du vil endre din e-postadresse kan du <a href=":url" class="alert-link">klikke her</a>.',
    'email_change_current_email' => 'Nåværende e-postadresse:',
    'email_change_title' => 'Endre e-postadresse',
    'email_change_new' => 'Ny e-postadresse',
    'email_changed' => 'Din e-postadresse har blitt endret. Sjekk din e-postkasse for å validere den nye adressen.',
];
