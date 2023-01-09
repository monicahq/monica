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

    'failed' => 'Uppgifterna stämmer inte överrens med våra register.',
    'throttle' => 'För många inloggningsförsök. Vänligen försök igen om :seconds sekunder.',
    'not_authorized' => 'Du har inte behörighet att utföra denna åtgärd',
    'signup_disabled' => 'Registreringen är för närvarande inaktiverad',
    'signup_error' => 'Ett fel inträffade vid försök att registrera användaren',
    'back_homepage' => 'Tillbaka till startsidan',
    'mfa_auth_otp' => 'Autentisera med din tvåfaktorsenhet',
    'mfa_auth_webauthn' => 'Autentisera med en säkerhetsnyckel (WebAuthn)',
    '2fa_title' => 'Tvåfaktorsautentisering',
    '2fa_wrong_validation' => 'Den tvåfaktorsautentiseringen har misslyckats.',
    '2fa_one_time_password' => 'Tvåfaktorsautentiseringskod',
    '2fa_recuperation_code' => 'Ange en tvåfaktorsåterställningskod',
    '2fa_one_time_or_recuperation' => 'Ange en auktoriseringskod eller återställningskod',
    '2fa_otp_help' => 'Öppna din tvåfaktorsautentisering mobilapp och kopiera koden',

    'login_to_account' => 'Logga in på ditt konto',
    'login_with_recovery' => 'Logga in med en återställningskod',
    'login_again' => 'Logga in igen på ditt konto',
    'email' => 'E-post',
    'password' => 'Lösenord',
    'recovery' => 'Återställningskod',
    'login' => 'Logga in',
    'button_remember' => 'Kom ihåg mig',
    'password_forget' => 'Glömt Ditt lösenord?',
    'password_reset' => 'Återställ ditt lösenord',
    'use_recovery' => 'Eller så kan du använda en <a href=":url">återställningskod</a>',
    'signup_no_account' => 'Har du inget konto?',
    'signup' => 'Registrera dig',
    'create_account' => 'Skapa det första kontot genom att <a href=":url">registrera dig</a>',
    'change_language_title' => 'Växla språk:',
    'change_language' => 'Byt språk till',

    'password_reset_title' => 'Återställ lösenord',
    'password_reset_email' => 'E-postadress',
    'password_reset_send_link' => 'Skicka lösenordsåterställningslänk',
    'password_reset_password' => 'Lösenord',
    'password_reset_password_confirm' => 'Bekräfta Lösenord',
    'password_reset_action' => 'Återställ lösenord',
    'password_reset_email_content' => 'Klicka här för att återställa ditt lösenord:',

    'register_title_welcome' => 'Välkommen till din nyinstallerade Monica-instans',
    'register_create_account' => 'Du måste skapa ett konto för att använda Monica',
    'register_title_create' => 'Skapa ditt Monica-konto',
    'register_login' => '<a href=":url">Logga in</a> om du redan har ett konto.',
    'register_email' => 'Ange en giltig e-postadress',
    'register_email_example' => 'du@hem',
    'register_firstname' => 'Förnamn',
    'register_firstname_example' => 'ex: John',
    'register_lastname' => 'Efternamn',
    'register_lastname_example' => 'ex. Svensson',
    'register_password' => 'Lösenord',
    'register_password_example' => 'Ange ett säkert lösenord',
    'register_password_confirmation' => 'Bekräftelse på lösenord',
    'register_action' => 'Registrera',
    'register_policy' => 'Att registrera dig innebär att du har läst och godkänner vår <a href=":url" hreflang=":hreflang">Integritetspolicy</a> och <a href=":urlterm" hreflang=":hreflang">Användarvillkor</a>.',
    'register_invitation_email' => 'Av säkerhetsskäl ber vi dig att ange e-postmeddelandet för den person som har bjudit in dig till detta konto. Denna information finns i e-postmeddelandet med inbjudan.',

    'confirmation_title' => 'Verifiera e-postadressen',
    'confirmation_fresh' => 'En ny verifieringslänk har skickats till din e-postadress.',
    'confirmation_check' => 'Innan du fortsätter, kontrollera din e-post efter en verifieringslänk.',
    'confirmation_request_another' => 'Om du inte fick e-postmeddelandet <a :action>klicka här för att begära en annan</a>.',

    'confirmation_again' => 'Om du vill ändra din e-postadress kan du <a href=":url" class="alert-link">klicka här</a>.',
    'email_change_current_email' => 'Nuvarande e-postadresser:',
    'email_change_title' => 'Ändra din e-postadress',
    'email_change_new' => 'Ny e-postadress',
    'email_changed' => 'Din e-postadress har ändrats. Kolla din brevlåda för att validera den.',
];
