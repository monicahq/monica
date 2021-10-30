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

    'failed' => 'مشخصات وارد شده با اطلاعات ما سازگار نیست.',
    'throttle' => 'تعداد دفعات تلاش برای ورود به سیستم بسیار زیاد است. لطفا در :seconds ثانیه دیگر تلاش نمایید.',
    'not_authorized' => 'شما مجاز به انجام این عمل نیستید.',
    'signup_disabled' => 'ثبت نام در حال حاضر غیر فعال است',
    'signup_error' => 'هنگام ثبت نام کاربر خطایی روی داد',
    'back_homepage' => 'بازگشت به صفحه اصلی',
    'mfa_auth_otp' => 'با دستگاه دو عاملی خود احراز هویت کنید',
    'mfa_auth_webauthn' => 'احراز هویت با کلید امنیتی (WebAuthn)',
    '2fa_title' => 'احراز هویت دو عاملی',
    '2fa_wrong_validation' => 'مجوز دو مرحله‌ای ناموفق بود.',
    '2fa_one_time_password' => 'کد تایید هویت دو مرحله‌ای',
    '2fa_recuperation_code' => 'کد بازیابی دوگانه را وارد کنید',
    '2fa_one_time_or_recuperation' => 'کد احراز هویت دو عاملی یا کد بازیابی را وارد کنید',
    '2fa_otp_help' => 'برنامه تلفن همراه احراز هویت دو عاملی خود را باز کنید و کد را کپی کنید',

    'login_to_account' => 'ورود به حساب کابری',
    'login_with_recovery' => 'ورود با کد بازیابی',
    'login_again' => 'لطفاً مجدداً وارد حساب کاربری خود شوید',
    'email' => 'ایمیل',
    'password' => 'رمز عبور',
    'recovery' => 'کد بازیابی',
    'login' => 'ورود',
    'button_remember' => 'مرا به خاطر بسپار',
    'password_forget' => 'رمز عبور را فراموش کردید؟',
    'password_reset' => 'بازنشانی کلمه عبور',
    'use_recovery' => 'یا شما می‌توانید از  <a href=":url">کد بازیابی</a> استفاده کنید',
    'signup_no_account' => 'حساب کاربری ندارید؟',
    'signup' => 'ثبت نام',
    'create_account' => 'برای ایجاد یک حساب کاربری جدید  <a href=":url"> اینجا کلیک</a> کنید',
    'change_language_title' => 'تغییر زبان:',
    'change_language' => 'تغییر زبان به :lang',

    'password_reset_title' => 'بازنشانی گذرواژه',
    'password_reset_email' => 'آدرس ایمیل',
    'password_reset_send_link' => 'ارسال لینک بازنشانی کلمه عبور',
    'password_reset_password' => 'رمز عبور',
    'password_reset_password_confirm' => 'تایید کلمه عبور',
    'password_reset_action' => 'بازنشانی گذرواژه',
    'password_reset_email_content' => 'برای تغییر رمز عبور اینجا کلیک کنید:',

    'register_title_welcome' => 'به نمونه تازه نصب شده مونیکا خود خوش آمدید',
    'register_create_account' => 'شما برای استفاده از مونیکا لازم است یک حساب کاربری ایجاد کنید',
    'register_title_create' => 'حساب کاربری مونیکا خود را بسازید',
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
