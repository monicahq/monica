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

    'failed' => 'Такий обліковий запис не знайдено.',
    'throttle' => 'Забагато невдалих спроб входу. Будь ласка, повторіть спробу через :seconds секунд.',
    'not_authorized' => 'Ви не маєте права виконувати цю дію',
    'signup_disabled' => 'Реєстрацію вимкнено',
    'signup_error' => 'Сталася помилка при спробі реєстрації користувача',
    'back_homepage' => 'Повернутися на головну сторінку',
    'mfa_auth_otp' => 'Увійти через двофакторний пристрій',
    'mfa_auth_webauthn' => 'Увійти через ключ безпеки (WebAuthn)',
    '2fa_title' => 'Двофакторна аутентифікація',
    '2fa_wrong_validation' => 'Помилка двофакторної аутентифікації.',
    '2fa_one_time_password' => 'Код двофакторної аутентифікації',
    '2fa_recuperation_code' => 'Введіть двофакторний код відновлення',
    '2fa_one_time_or_recuperation' => 'Введіть двофакторний код автентифікації або код відновлення',
    '2fa_otp_help' => 'Відкрийте мобільний додаток для двофакторної аутентифікації та скопіюйте код',

    'login_to_account' => 'Увійти у свій обліковий запис',
    'login_with_recovery' => 'Увійти через код відновлення',
    'login_again' => 'Будь ласка, повторно увійдіть до вашого облікового запису',
    'email' => 'Емейл',
    'password' => 'Пароль',
    'recovery' => 'Код відновлення',
    'login' => 'Вхід',
    'button_remember' => 'Запам\'ятати мене',
    'password_forget' => 'Забули свій пароль?',
    'password_reset' => 'Скинути пароль',
    'use_recovery' => 'Або ж ви можете використати <a href=":url">код відновлення</a>',
    'signup_no_account' => 'Не маєте облікового запису?',
    'signup' => 'Реєстрація',
    'create_account' => 'Create the first account by <a href=":url">signing up</a>',
    'change_language_title' => 'Change language:',
    'change_language' => 'Change language to :lang',

    'password_reset_title' => 'Reset Password',
    'password_reset_email' => 'E-Mail Address',
    'password_reset_send_link' => 'Send Password Reset Link',
    'password_reset_password' => 'Password',
    'password_reset_password_confirm' => 'Confirm Password',
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
