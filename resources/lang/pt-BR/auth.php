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

    'failed' => 'Credenciais informadas não correspondem com nossos registros.',
    'throttle' => 'Você realizou muitas tentativas de login. Por favor, tente novamente em :seconds segundos.',
    'not_authorized' => 'Você não está autorizado a executar esta ação',
    'signup_disabled' => 'Atualmente o registro está desativado',
    'back_homepage' => 'Voltar à página inicial',
    'mfa_auth_otp' => 'Autenticar com dois fatores',
    'mfa_auth_u2f' => 'Authenticate with a U2F device',
    'mfa_auth_webauthn' => 'Autenticar com uma chave de segurança (WebAuthn)',
    '2fa_title' => 'Autenticação de dois fatores',
    '2fa_wrong_validation' => 'Falha na autenticação de dois fatores.',
    '2fa_one_time_password' => 'Two factor authentication code',
    '2fa_recuperation_code' => 'Enter a two factor recovery code',
    '2fa_otp_help' => 'Open up your two factor authentication mobile app and copy the code',
    'u2f_otp_extension' => 'U2F is supported natively on Chrome, <a href="{urlquantum}" lang="en">Firefox</a> and Opera. On old Firefox versions, install the <a href="{urlext}">U2F Support Add-on</a>.',

    'login_to_account' => 'Entre na sua conta',
    'login_with_recovery' => 'Login with a recovery code',
    'login_again' => 'Please login again to your account',
    'email' => 'Email',
    'password' => 'Senha',
    'recovery' => 'Recovery code',
    'login' => 'Entrar',
    'button_remember' => 'Permanecer logado',
    'password_forget' => 'Esqueceu sua Senha?',
    'password_reset' => 'Redefinir senha',
    'use_recovery' => 'Or you can use a <a href=":url">recovery code</a>',
    'signup_no_account' => 'Não tem uma conta?',
    'signup' => 'Inscreve-se',
    'create_account' => 'Create the first account by <a href=":url">signing up</a>',
    'change_language_title' => 'Mudar idioma:',
    'change_language' => 'Change language to :lang',

    'password_reset_title' => 'Redefinir senha',
    'password_reset_email' => 'E-mail',
    'password_reset_send_link' => 'Enviar Link para redefinição de senha',
    'password_reset_password' => 'Senha',
    'password_reset_password_confirm' => 'Confirmar senha',
    'password_reset_action' => 'Redefinir senha',
    'password_reset_email_content' => 'Clique aqui para redefinir sua senha:',

    'register_title_welcome' => 'Bem-vindo à sua instância Monica recém instalada',
    'register_create_account' => 'Você precisa criar uma conta para usar Monica',
    'register_title_create' => 'Crie sua Conta',
    'register_login' => '<a href=":url">Log in</a> if you already have an account.',
    'register_email' => 'Enter a valid email address',
    'register_email_example' => 'nome@email',
    'register_firstname' => 'Nome',
    'register_firstname_example' => 'ex. Lucas',
    'register_lastname' => 'Último Nome',
    'register_lastname_example' => 'ex. Duarte',
    'register_password' => 'Senha',
    'register_password_example' => 'Escreva uma senha segura',
    'register_password_confirmation' => 'Password confirmation',
    'register_action' => 'Registrar',
    'register_policy' => 'Signing up signifies you’ve read and agree to our <a href=":url" hreflang=":hreflang">Privacy Policy</a> and <a href=":urlterm" hreflang=":hreflang">Terms of use</a>.',
    'register_invitation_email' => 'For security purposes, please indicate the email of the person who’ve invited you to join this account. This information is provided in the invitation email.',

    'confirmation_title' => 'Verify Your Email Address',
    'confirmation_fresh' => 'A fresh verification link has been sent to your email address.',
    'confirmation_check' => 'Before proceeding, please check your email for a verification link.',
    'confirmation_request_another' => 'If you did not receive the email <a href=":url">click here to request another</a>.',

    'confirmation_again' => 'If you want to change your email address you can <a href=":url" class="alert-link">click here</a>.',
    'email_change_current_email' => 'Current email address:',
    'email_change_title' => 'Change your email address',
    'email_change_new' => 'New email address',
    'email_changed' => 'Your email address has been changed. Check your mailbox to validate it.',
];
