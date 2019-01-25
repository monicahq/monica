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

    'failed' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
    'throttle' => 'Tentatives de connexion trop nombreuses. Veuillez essayer de nouveau dans :seconds secondes.',
    'not_authorized' => 'Vous n’êtes pas autorisé à exécuter cette action',
    'signup_disabled' => 'L’inscription est actuellement désactivée',
    'back_homepage' => 'Retour à la page d’accueil',
    'mfa_auth_otp' => 'S\'authentifier avec votre dispositif à deux facteurs',
    'mfa_auth_u2f' => 'S’authentifier avec un dispositif U2F',
    '2fa_title' => 'Authentification à deux facteurs',
    '2fa_wrong_validation' => 'L’authentification à deux facteurs a échoué.',
    '2fa_one_time_password' => 'Code d’authentification à deux facteurs',
    '2fa_recuperation_code' => 'Entrez le code de récupération de deux facteurs',
    '2fa_otp_help' => 'Ouvrez votre application mobile pour l’authentification à deux facteurs et copiez le Qr code suivant',
    'u2f_otp_extension' => 'U2F est supporté nativement sur Chrome, <a href="{urlquantum}" lang="en">Firefox</a>, et Opera. Sur les anciennes versions de Firefox, installez <a href="{urlext}">l\'extension U2F</a>.',

    'login_to_account' => 'Connectez-vous à votre compte',
    'login_with_recovery' => 'Connexion avec un code de récupération',
    'login_again' => 'Merci de vous connecter à nouveau à votre compte',
    'email' => 'Courriel',
    'password' => 'Mot de passe',
    'recovery' => 'Code de récupération',
    'login' => 'Connexion',
    'button_remember' => 'Se souvenir de moi',
    'password_forget' => 'Mot de passe oublié ?',
    'password_reset' => 'Réinitialisez votre mot de passe',
    'use_recovery' => 'Ou vous pouvez utiliser un <a href=":url">code de récupération</a>',
    'signup_no_account' => 'Vous n’avez pas de compte ?',
    'signup' => 'S’inscrire',
    'create_account' => 'Créer le premier compte en vous <a href=":url">enregistrant</a>',
    'change_language_title' => 'Changer la langue :',
    'change_language' => 'Afficher la page en :lang',

    'password_reset_title' => 'Réinitialiser le mot de passe',
    'password_reset_email' => 'Adresse courriel',
    'password_reset_send_link' => 'Envoyer un lien pour réinitialiser le mot de passe',
    'password_reset_password' => 'Mot de passe',
    'password_reset_password_confirm' => 'Confirmez le mot de passe',
    'password_reset_action' => 'Réinitialiser le mot de passe',
    'password_reset_email_content' => 'Cliquez ici pour réinitialiser votre mot de passe :',

    'register_title_welcome' => 'Bienvenue à votre nouvelle instance Monica',
    'register_create_account' => 'Vous devez créer un compte pour utiliser Monica',
    'register_title_create' => 'Créez votre compte Monica',
    'register_login' => '<a href=":url">Connectez-vous</a> si vous avez déjà un compte.',
    'register_email' => 'Entrez une adresse courriel valide',
    'register_email_example' => 'vous@maison',
    'register_firstname' => 'Prénom',
    'register_firstname_example' => 'ex : Pierre',
    'register_lastname' => 'Nom de famille',
    'register_lastname_example' => 'ex : Dupont',
    'register_password' => 'Mot de passe',
    'register_password_example' => 'Entrez un mot de passe sécurisé',
    'register_password_confirmation' => 'Confirmez le mot de passe',
    'register_action' => 'Enregistrement',
    'register_policy' => 'L’inscription signifie vous avez lu et acceptez notre <a href=":url" hreflang=":hreflang">Politique de Confidentialité</a> et nos <a href=":urlterm" hreflang=":hreflang">Conditions d’Utilisation</a>.',
    'register_invitation_email' => 'Pour des raisons de sécurité, merci d’indiquer l’adresse courriel de la personne qui vous a invité à joindre son compte. Cette information est indiquée dans le courriel d’invitation.',

    'confirmation_title' => 'Vérifiez votre adresse courriel',
    'confirmation_fresh' => 'Un nouveau lien de vérification a été envoyé à votre adresse courriel.',
    'confirmation_check' => 'Avant de continuer, veuillez vérifier votre boîte mail pour un lien de vérification.',
    'confirmation_request_another' => 'Si vous n’avez pas reçu le courriel <a href=":url">cliquez ici pour en demander un autre</a>.',

    'confirmation_again' => 'Si vous souhaitez modifier votre adresse courriel vous pouvez <a href=":url" class="alert-link">cliquer ici</a>.',
    'email_change_current_email' => 'Adresse courriel actuelle :',
    'email_change_title' => 'Modifier votre adresse courriel',
    'email_change_new' => 'Nouvelle adresse courriel',
    'email_changed' => 'Votre adresse courriel a été modifée. Vérifiez votre boîte aux lettres pour la valider.',
];
