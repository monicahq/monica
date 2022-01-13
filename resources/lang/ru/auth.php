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

    'failed' => 'Имя пользователя и пароль не совпадают.',
    'throttle' => 'Слишком много попыток входа. Пожалуйста, попробуйте еще раз через :seconds секунд.',
    'not_authorized' => 'Вам не разрешено выполнять это действие.',
    'signup_disabled' => 'Регистрация сейчас выключена.',
    'signup_error' => 'Произошла ошибка при регистрации пользователя',
    'back_homepage' => 'Вернуться на главную страницу',
    'mfa_auth_otp' => 'Аутентифицироваться с двухфакторным устройством',
    'mfa_auth_webauthn' => 'Аутентифицироваться с ключом безопасности (WebAuthn)',
    '2fa_title' => 'Двухфакторная аутентификация',
    '2fa_wrong_validation' => 'Сбой двухфакторной аутентификации.',
    '2fa_one_time_password' => 'Код двухфакторной аутентификации',
    '2fa_recuperation_code' => 'Введите код двухфакторного восстановления',
    '2fa_one_time_or_recuperation' => 'Введите код двухфакторной аутентификации или код восстановления',
    '2fa_otp_help' => 'Откройте приложение для мобильной проверки подлинности и скопируйте код',

    'login_to_account' => 'Войти в свою учетную запись',
    'login_with_recovery' => 'Войти с помощью кода восстановления',
    'login_again' => 'Пожалуйста, войдите снова в свою учетную запись',
    'email' => 'Адрес электронной почты',
    'password' => 'Пароль',
    'recovery' => 'Код восстановления',
    'login' => 'Вход',
    'button_remember' => 'Запомнить меня',
    'password_forget' => 'Забыли пароль?',
    'password_reset' => 'Сбросить пароль',
    'use_recovery' => 'Или вы можете использовать <a href=":url">код восстановления</a>',
    'signup_no_account' => 'Нет аккаунта?',
    'signup' => 'Регистрация',
    'create_account' => 'Создайте первую учетную запись, <a href=":url">зарегистрировав</a>',
    'change_language_title' => 'Изменить язык:',
    'change_language' => 'Изменить язык на :lang',

    'password_reset_title' => 'Восстановить пароль',
    'password_reset_email' => 'E-Mail Адрес',
    'password_reset_send_link' => 'Отправить ссылку для сброса пароля',
    'password_reset_password' => 'Пароль',
    'password_reset_password_confirm' => 'Подтверждение пароля',
    'password_reset_action' => 'Восстановить пароль',
    'password_reset_email_content' => 'Нажмите, чтобы сбросить пароль:',

    'register_title_welcome' => 'Добро пожаловать в ваш вновь установленный экземпляр Monica',
    'register_create_account' => 'Для использования Monica вам нужно создать аккаунт',
    'register_title_create' => 'Создайте свой аккаунт Monica',
    'register_login' => '<a href=":url">Войдите</a>, если у вас уже есть аккаунт.',
    'register_email' => 'Введите действительный адрес электронной почты',
    'register_email_example' => 'you@home',
    'register_firstname' => 'Имя',
    'register_firstname_example' => 'например, Иван',
    'register_lastname' => 'Фамилия',
    'register_lastname_example' => 'например, Иванов',
    'register_password' => 'Пароль',
    'register_password_example' => 'Введите сложный пароль',
    'register_password_confirmation' => 'Подтверждение пароля',
    'register_action' => 'Регистрация',
    'register_policy' => 'Регистрация означает, что вы прочитали и согласны с <a href=":url" hreflang=":hreflang">Политикой конфиденциальности</a> и <a href=":urlterm" hreflang=":hreflang">Условиями использования</a>.',
    'register_invitation_email' => 'В целях безопасности укажите, пожалуйста, электронное письмо с приглашенным вами человеком. Эта информация приведена в приглашённом письме.',

    'confirmation_title' => 'Подтвердите ваш адрес электронной почты',
    'confirmation_fresh' => 'На ваш адрес электронной почты выслана ссылка для подтверждения.',
    'confirmation_check' => 'Прежде чем продолжить, пожалуйста, проверьте вашу электронную почту на наличие проверочной ссылки.',
    'confirmation_request_another' => 'Если вы не получили письмо <a :action>, нажмите здесь, чтобы запросить другой</a>.',

    'confirmation_again' => 'Если вы хотите изменить свой адрес электронной почты, <a href=":url" class="alert-link">нажмите здесь</a>.',
    'email_change_current_email' => 'Текущий адрес электронной почты:',
    'email_change_title' => 'Изменить адрес электронной почты',
    'email_change_new' => 'Новый адрес электронной почты',
    'email_changed' => 'Ваш адрес электронной почты изменен. Проверьте свой почтовый ящик, чтобы подтвердить его.',
];
