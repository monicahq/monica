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

    'failed' => 'Girilmiş olan kullanıcı verileri sistemdekiler ile eşleşmemektedir.',
    'throttle' => 'Çok fazla oturum açma girişiminde bulundunuz. Lütfen :seconds saniye içerisinde tekrar deneyiz.',
    'not_authorized' => 'You are not authorized to execute this action',
    'signup_disabled' => 'Kayıt şu anda devre dışı',
    'back_homepage' => 'Ana sayfaya dön',
    'mfa_auth_otp' => 'Authenticate with your two factor device',
    'mfa_auth_u2f' => 'Authenticate with a U2F device',
    '2fa_title' => 'İki Adımlı Doğrulama',
    '2fa_wrong_validation' => 'İki adımlı doğrulaması başarısız oldu.',
    '2fa_one_time_password' => 'İki adımlı doğrulama kodu',
    '2fa_recuperation_code' => 'İki aşamalı doğrulama kodu ile girin',
    '2fa_otp_help' => 'Open up your two factor authentication mobile app and copy the code',
    'u2f_otp_extension' => 'U2F is supported natively on Chrome, <a href="{urlquantum}" lang="en">Firefox</a> and Opera. On old Firefox versions, install the <a href="{urlext}">U2F Support Add-on</a>.',

    'login_to_account' => 'Hesabınıza giriş yapın',
    'login_with_recovery' => 'Bir kurtarma kodu ile giriş yap',
    'login_again' => 'Lütfen hesabınıza tekrar giriş yapınız',
    'email' => 'E-posta',
    'password' => 'Şifre',
    'recovery' => 'Kurtarma kodu',
    'login' => 'Oturum Aç',
    'button_remember' => 'Beni Hatırla',
    'password_forget' => 'Şifremi unuttum',
    'password_reset' => 'Şifrenizi değiştirin',
    'use_recovery' => 'Or you can use a <a href=":url">recovery code</a>',
    'signup_no_account' => 'Hesabınız yok mu?',
    'signup' => 'Kayıt ol',
    'create_account' => 'Create the first account by <a href=":url">signing up</a>',
    'change_language_title' => 'Dili değiştir:',
    'change_language' => 'Dili :lang ile değiştir',

    'password_reset_title' => 'Şifreyi Yenile',
    'password_reset_email' => 'E-posta Adresi',
    'password_reset_send_link' => 'Şifre sıfırlama bağlantısını gönder',
    'password_reset_password' => 'Şifre',
    'password_reset_password_confirm' => 'Şifreyi Doğrula',
    'password_reset_action' => 'Şifreyi Sıfırla',
    'password_reset_email_content' => 'Şifrenizi sıfırlamak için buraya tıklayın:',

    'register_title_welcome' => 'Yeni yüklenen Monica örneğinize hoş geldiniz',
    'register_create_account' => 'Monica\'yı kullanmak için bir hesap oluşturmanız gerekir',
    'register_title_create' => 'Monica hesabınızı oluşturun',
    'register_login' => 'Zaten bir hesabınız varsa <a href=":url">Giriş Yapın</a>.',
    'register_email' => 'Geçerli bir e-posta adresi girin',
    'register_email_example' => 'mail@mail',
    'register_firstname' => 'Ad',
    'register_firstname_example' => 'örn: John',
    'register_lastname' => 'Soyad',
    'register_lastname_example' => 'örn. Doe',
    'register_password' => 'Şifre',
    'register_password_example' => 'Güçlü bir şifre girin',
    'register_password_confirmation' => 'Şifre doğrulama',
    'register_action' => 'Kayıt Ol',
    'register_policy' => 'Signing up signifies you’ve read and agree to our <a href=":url" hreflang=":hreflang">Privacy Policy</a> and <a href=":urlterm" hreflang=":hreflang">Terms of use</a>.',
    'register_invitation_email' => 'For security purposes, please indicate the email of the person who’ve invited you to join this account. This information is provided in the invitation email.',

    'confirmation_title' => 'E-posta adresinizi doğrulayın',
    'confirmation_fresh' => 'A fresh verification link has been sent to your email address.',
    'confirmation_check' => 'Before proceeding, please check your email for a verification link.',
    'confirmation_request_another' => 'If you did not receive the email <a href=":url">click here to request another</a>.',

    'confirmation_again' => 'If you want to change your email address you can <a href=":url" class="alert-link">click here</a>.',
    'email_change_current_email' => 'Geçerli e-posta adresi:',
    'email_change_title' => 'E-posta adresini değiştir',
    'email_change_new' => 'Yeni e-posta adresi',
    'email_changed' => 'Your email address has been changed. Check your mailbox to validate it.',
];
