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
    'register_login' => 'اگر حساب کاربری دارید <a href=":url">وارد شوید</a>  ​',
    'register_email' => 'لطفا یک آدرس ایمیل معتبر را وارد کنید',
    'register_email_example' => 'you@home',
    'register_firstname' => 'نام',
    'register_firstname_example' => 'مثال: صالح',
    'register_lastname' => 'نام خانوادگی',
    'register_lastname_example' => 'مثال: شریفی',
    'register_password' => 'رمز عبور',
    'register_password_example' => 'یک رمز عبور مطمئن وارد کنید',
    'register_password_confirmation' => 'تایید رمز عبور',
    'register_action' => 'ثبت نام',
    'register_policy' => 'ثبت نام شما به معنی مطالعه و قبول  <a href=":url" hreflang=":hreflang">سیاست حفظ حریم خصوصی</a> و <a href=":urlterm" hreflang=":hreflang">  قوانین و مقررات</a> می باشد.',
    'register_invitation_email' => 'برای اهداف امنیتی، لطفاً ایمیل شخصی را که شما را برای پیوستن به این حساب دعوت کرده است، مشخص کنید. این اطلاعات در ایمیل دعوت وجود دارد.',

    'confirmation_title' => 'آدرس ایمیل خود را تایید کنید',
    'confirmation_fresh' => 'یک لینک تأیید جدید به آدرس ایمیلتان ارسال  شد.',
    'confirmation_check' => 'قبل از ادامه، لطفاً ایمیل خود را برای لینک تأیید بررسی کنید.',
    'confirmation_request_another' => 'اگر ایمیل را دریافت نکرده اید  <a :action>برای درخواست مجدد اینجا کلیک کنید</a>.',

    'confirmation_again' => 'اگر میخواهید ایمیل خود را تغییر دهید می‌توانید <a href=":url" class="alert-link">اینجا کلیک کنید</a>.',
    'email_change_current_email' => 'ادرس ایمیل فعلی:',
    'email_change_title' => 'آدرس ایمیل خود را تغییر دهید',
    'email_change_new' => 'آدرس ایمیل جدید',
    'email_changed' => 'ادرس ایمیل شما تغییر کرد . صندوق پستی خود را برای تایید ایمیل چک کنید.',
];
