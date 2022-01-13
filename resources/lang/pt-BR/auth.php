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

    'failed' => 'Credenciais informadas não correspondem com nossos registros.',
    'throttle' => 'Você realizou muitas tentativas de login. Por favor, tente novamente em :seconds segundos.',
    'not_authorized' => 'Você não está autorizado a executar esta ação',
    'signup_disabled' => 'Cadastro de novas contas desativado no momento',
    'signup_error' => 'Um erro ocorreu ao tentar registrar o usuário',
    'back_homepage' => 'Voltar à página inicial',
    'mfa_auth_otp' => 'Autenticar com dois fatores',
    'mfa_auth_webauthn' => 'Autenticar com uma chave de segurança (WebAuthn)',
    '2fa_title' => 'Autenticação de dois fatores',
    '2fa_wrong_validation' => 'Falha na autenticação de dois fatores.',
    '2fa_one_time_password' => 'Código de autenticação de dois fatores',
    '2fa_recuperation_code' => 'Digite um código de recuperação de dois fatores',
    '2fa_one_time_or_recuperation' => 'Digite um código de autenticação de dois fatores ou um código de recuperação',
    '2fa_otp_help' => 'Abra seu aplicativo para autenticação de dois fatores e copie o código',

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
    'register_policy' => 'Registrar-se significa que você leu e concordou com nossas <a href=":url" hreflang=":hreflang">Políticas de Privacidade</a> e <a href=":urlterm" hreflang=":hreflang">Termos de uso</a>.',
    'register_invitation_email' => 'Por questões de segurança, favor indicar o email da pessoa que te convidou para fazer parte desta conta. Esta informação é fornecida no email do convite.',

    'confirmation_title' => 'Verifique seu endereço de email',
    'confirmation_fresh' => 'Um novo link de verificação foi enviado para o seu endereço de email.',
    'confirmation_check' => 'Antes de prosseguir, verifique seu email para um link de verificação.',
    'confirmation_request_another' => 'Se você não recebeu o email <a :action> clique aqui para solicitar outro</a>.',

    'confirmation_again' => 'Se você desejar alterar seu endereço de email, você pode <a href=":url" class="alert-link">clicar aqui</a>.',
    'email_change_current_email' => 'Endereço de e-mail atual:',
    'email_change_title' => 'Alterar o seu endereço de e-mail',
    'email_change_new' => 'Novo endereço de e-mail',
    'email_changed' => 'Seu endereço de e-mail foi alterado. Verifique sua caixa de entrada para validá-lo.',
];
