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
    'signup_disabled' => 'Cadastro de novas contas desativado no momento',
    'back_homepage' => 'Voltar à página inicial',
    'mfa_auth_otp' => 'Autenticar com dois fatores',
    'mfa_auth_u2f' => 'Autenticar com um dispositivo U2F',
    'mfa_auth_webauthn' => 'Autenticar com uma chave de segurança (WebAuthn)',
    '2fa_title' => 'Autenticação de dois fatores',
    '2fa_wrong_validation' => 'Falha na autenticação de dois fatores.',
    '2fa_one_time_password' => 'Código de autenticação de dois fatores',
    '2fa_recuperation_code' => 'Digite um código de recuperação de dois fatores',
    '2fa_otp_help' => 'Abra seu aplicativo para autenticação de dois fatores e copie o código',
    'u2f_otp_extension' => 'U2F é suportado de forma nativa no Chrome, <a href="{urlquantum}" lang="en">Firefox</a> e Opera. Em versões mais antigas do Firefox, instale a extensão <a href="{urlext}">U2F Support</a>',

    'login_to_account' => 'Entre na sua conta',
    'login_with_recovery' => 'Entrar com um código de recuperação',
    'login_again' => 'Por favor, entre novamente na sua conta',
    'email' => 'E-mail',
    'password' => 'Senha',
    'recovery' => 'Código de recuperação',
    'login' => 'Entrar',
    'button_remember' => 'Permanecer logado',
    'password_forget' => 'Esqueceu sua senha?',
    'password_reset' => 'Redefinir senha',
    'use_recovery' => 'Ou você pode usar um <a href=":url">código de recuperação</a>',
    'signup_no_account' => 'Não tem uma conta?',
    'signup' => 'Cadastre-se',
    'create_account' => '<a href=":url">Cadastre-se</a> para criar a primeira conta',
    'change_language_title' => 'Mudar idioma:',
    'change_language' => 'Mudar idioma para :lang',

    'password_reset_title' => 'Redefinir senha',
    'password_reset_email' => 'Endereço de e-mail',
    'password_reset_send_link' => 'Enviar e-mail para redefinição de senha',
    'password_reset_password' => 'Senha',
    'password_reset_password_confirm' => 'Confirmar senha',
    'password_reset_action' => 'Redefinir senha',
    'password_reset_email_content' => 'Clique aqui para redefinir sua senha:',

    'register_title_welcome' => 'Bem-vindo à sua instância Monica recém instalada',
    'register_create_account' => 'Você precisa criar uma conta para usar o Monica',
    'register_title_create' => 'Crie sua conta Monica',
    'register_login' => '<a href=":url">Entre</a> se você já tiver uma conta.',
    'register_email' => 'Insira um endereço de e-mail válido',
    'register_email_example' => 'joao@gmail.com',
    'register_firstname' => 'Nome',
    'register_firstname_example' => 'ex. João',
    'register_lastname' => 'Sobrenome',
    'register_lastname_example' => 'ex. Silva',
    'register_password' => 'Senha',
    'register_password_example' => 'Digite uma senha segura',
    'register_password_confirmation' => 'Confirmação de senha',
    'register_action' => 'Cadastrar',
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
