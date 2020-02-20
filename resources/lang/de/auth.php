<?php

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

    'failed' => 'Die Anmeldedaten stimmen nicht.',
    'throttle' => 'Zu viele Anmeldeversuche. Bitte in :seconds Sekunden erneut versuchen.',
    'not_authorized' => 'Du hast keine Berechtigung diese Aktion auszuführen',
    'signup_disabled' => 'Neue Registrierungen sind zur Zeit nicht möglich',
    'back_homepage' => 'Zurück zur Seite',
    'mfa_auth_otp' => 'Authentifizieren Sie sich mit Ihrem Zwei-Faktor-Gerät',
    'mfa_auth_u2f' => 'Authentifizieren Sie sich mit einem U2F Gerät',
    'mfa_auth_webauthn' => 'Authentifizieren mit einem Sicherheitsschlüssel (WebAuthn)',
    '2fa_title' => 'Zwei-Faktor-Authentifizierung',
    '2fa_wrong_validation' => 'Die Zwei-Faktor-Authentifizierung ist fehlgeschlagen.',
    '2fa_one_time_password' => 'Zwei-Faktor-Authentifizierungscode',
    '2fa_recuperation_code' => 'Bitte gib deinen Zwei-Faktor-Wiederherstellungscode ein',
    '2fa_otp_help' => 'Öffne deine Zwei-Faktor-Authentifizierungs-App und scanne den folgenden QR-Code',
    'u2f_otp_extension' => 'U2F wird nativ mit Chrome, <a href="{urlquantum}" lang="en">Firefox</a> und Opera unterstützt. In alten Firefox-Versionen installiere das <a href="{urlext}">U2F Support-Add-on</a>.',

    'login_to_account' => 'In Konto einloggen',
    'login_with_recovery' => 'Mit einem Wiederherstellungsschlüssel anmelden',
    'login_again' => 'Bitte loggen Sie sich wieder in Ihren Account ein',
    'email' => 'E-Mail',
    'password' => 'Passwort',
    'recovery' => 'Wiederherstellungsschlüssel',
    'login' => 'Einloggen',
    'button_remember' => 'Eingeloggt bleiben',
    'password_forget' => 'Passwort vergessen?',
    'password_reset' => 'Passwort zurücksetzen',
    'use_recovery' => 'Oder sie verwenden einen <a href=":url">Wiederherstellungsschlüssel</a>',
    'signup_no_account' => 'Haben Sie noch kein Konto?',
    'signup' => 'Registrieren',
    'create_account' => 'Erstellen Sie ihr erstes Konto, indem sie sich <a href=":url">registrieren</a>',
    'change_language_title' => 'Sprache ändern:',
    'change_language' => 'Sprache ändern zu :lang',

    'password_reset_title' => 'Passwort zurücksetzen',
    'password_reset_email' => 'E-Mail-Adresse',
    'password_reset_send_link' => 'E-Mail zum Zurücksetzen des Passworts senden',
    'password_reset_password' => 'Passwort',
    'password_reset_password_confirm' => 'Passwort bestätigen',
    'password_reset_action' => 'Passwort zurücksetzen',
    'password_reset_email_content' => 'Hier klicken, um das Passwort zurückzusetzen:',

    'register_title_welcome' => 'Herzlich Willkommen in Ihrer neu installierten Instanz von Monica',
    'register_create_account' => 'Sie benötigen ein Konto, um Monica zu verwenden',
    'register_title_create' => 'Monica Konto erstellen',
    'register_login' => '<a href=":url">Einloggen</a> wenn Sie bereits ein Konto haben.',
    'register_email' => 'Gültige E-Mail Adresse eingeben',
    'register_email_example' => 'du@zuhause',
    'register_firstname' => 'Vorname',
    'register_firstname_example' => 'z.B. Max',
    'register_lastname' => 'Nachname',
    'register_lastname_example' => 'z.B. Mustermann',
    'register_password' => 'Passwort',
    'register_password_example' => 'Sicheres Kennwort eingeben',
    'register_password_confirmation' => 'Passwortbestätigung',
    'register_action' => 'Anmelden',
    'register_policy' => 'Deine Anmeldung bedeutet, dass du unsere <a href=":url" hreflang=":hreflang">Datenschutzrichtlinien</a> and <a href=":urlterm" hreflang=":hreflang">AGBs</a> gelesen und akzeptiert hast.',
    'register_invitation_email' => 'Aus Sicherheitsgründen geben Sie bitte die E-Mail-Adresse der Person an, die Sie eingeladen hat, diesem Konto beizutreten. Diese Informationen finden Sie in der Einladungs-E-Mail.',

    'confirmation_title' => 'E-Mail-Adresse bestätigen',
    'confirmation_fresh' => 'Ein Bestätigungslink wurde an Ihre E-Mail-Adresse geschickt.',
    'confirmation_check' => 'Bevor sie weitermachen, überprüfen sie bitte ihre E-mails nach einem Bestätigungslink.',
    'confirmation_request_another' => 'If you did not receive the email <a :action>click here to request another</a>.',

    'confirmation_again' => 'Wenn Sie Ihre E-Mail-Adresse ändern möchten, <a href=":url" class="alert-link">klicken Sie bitte hier</a>.',
    'email_change_current_email' => 'Aktuelle E-Mail-Adresse:',
    'email_change_title' => 'E-Mail-Adresse ändern',
    'email_change_new' => 'Neue E-Mail-Adresse',
    'email_changed' => 'Ihre E-Mail-Adresse wurde geändert. Überprüfen Sie Ihre E-Mails um sie zu bestätigen.',
];
