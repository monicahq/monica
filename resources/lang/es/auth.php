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

    'failed' => 'Estas credenciales no coinciden con nuestros registros.',
    'throttle' => 'Demasiados intentos de acceso. Por favor intente nuevamente en :seconds segundos.',
    'not_authorized' => 'Usted no esta autorizado para ejecutar esta acción',
    'signup_disabled' => 'La registración se encuentra actualmente deshabilitada',
    'back_homepage' => 'Volver al inicio',
    'mfa_auth_otp' => 'Autentícate con tú dispositivo de dos pasos',
    'mfa_auth_u2f' => 'Autentícate con un dispositivo U2F',
    'mfa_auth_webauthn' => 'Authenticate with a security key (WebAuthn)',
    '2fa_title' => 'Autenticación en dos pasos',
    '2fa_wrong_validation' => 'La autenticación en dos pasos ha fallado.',
    '2fa_one_time_password' => 'Código de autenticación en dos pasos',
    '2fa_recuperation_code' => 'Introduce un código de recuperación de autenticación en dos pasos',
    '2fa_otp_help' => 'Abre tú aplicación móvil de autenticación en dos pasos y copia el código',
    'u2f_otp_extension' => 'U2F es soportado de forma nativa en Chrome, <a href="{urlquantum}" lang="en">Firefox</a> y Opera. Para versiones viejas de Firefox, instala el <a href="{urlext}">U2F Support Add-on</a>.',

    'login_to_account' => 'Inicia sesión en tu cuenta',
    'login_with_recovery' => 'Inicia sesión con un código de recuperación',
    'login_again' => 'Por favor inicia sesión de nuevo en tu cuenta',
    'email' => 'Email',
    'password' => 'Contraseña',
    'recovery' => 'Código de recuperación',
    'login' => 'Identificarse',
    'button_remember' => 'Recordarme',
    'password_forget' => '¿Olvidaste tu contraseña?',
    'password_reset' => 'Restablece tu contraseña',
    'use_recovery' => 'O puedes usar un <a href=":url">código de recuperación</a>',
    'signup_no_account' => '¿No tienes una cuenta?',
    'signup' => 'Regístrate',
    'create_account' => 'Crea la primera cuenta <a href=":url">registrándote</a>',
    'change_language_title' => 'Cambiar idioma:',
    'change_language' => 'Cambiar el idioma a :lang',

    'password_reset_title' => 'Restablecer contraseña',
    'password_reset_email' => 'Correo Eletrónico',
    'password_reset_send_link' => 'Enviar enlace para restablecer la contraseña',
    'password_reset_password' => 'Contraseña',
    'password_reset_password_confirm' => 'Confirma Contraseña',
    'password_reset_action' => 'Restablecer contraseña',
    'password_reset_email_content' => 'Haz clic aquí para restablecer tu contraseña:',

    'register_title_welcome' => 'Bienvenido a tu nueva instancia de Monica',
    'register_create_account' => 'Debes crear una cuenta para usar Monica',
    'register_title_create' => 'Crea tu cuenta de Monica',
    'register_login' => '<a href=":url">Inicia sesión</a> si ya tienes una cuenta.',
    'register_email' => 'Introduzca una dirección de correo electrónico válida',
    'register_email_example' => 'you@home',
    'register_firstname' => 'Nombre',
    'register_firstname_example' => 'ej. John',
    'register_lastname' => 'Apellidos',
    'register_lastname_example' => 'ej. Doe',
    'register_password' => 'Contraseña',
    'register_password_example' => 'Escribe una contraseña segura',
    'register_password_confirmation' => 'Confirmar contraseña',
    'register_action' => 'Registrarse',
    'register_policy' => 'Registrarte implica que has leido y aceptas nuestra <a href=":url" hreflang=":hreflang">Política de Privacidad</a> y <a href=":urlterm" hreflang=":hreflang">Términos de uso</a>.',
    'register_invitation_email' => 'Por motivos de seguridad, porfavor indica el email de la persona que te ha invitado a unirte a esta cuenta. Esta información aparece in el email de invitación.',

    'confirmation_title' => 'Verifica tu dirección de correo electrónico',
    'confirmation_fresh' => 'Se ha enviado un correo electrónico con el enlace de verificación a tu dirección de correo electrónico.',
    'confirmation_check' => 'Antes de proceder, por favor comprueba el link de verificación en tu correo electrónico.',
    'confirmation_request_another' => 'Si no has recibido el email <a href=":url">haz click aquí para solicitar otro</a>.',

    'confirmation_again' => 'If you want to change your email address you can <a href=":url" class="alert-link">click here</a>.',
    'email_change_current_email' => 'Current email address:',
    'email_change_title' => 'Change your email address',
    'email_change_new' => 'New email address',
    'email_changed' => 'Your email address has been changed. Check your mailbox to validate it.',
];
