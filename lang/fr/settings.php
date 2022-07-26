<?php

return [
    'user_settings' => 'Paramètres de l’utilisateur',
    'user_preferences' => 'Préférences de l’utilisateur',
    'notification_channels' => 'Canaux de notifications',
    'account_settings' => 'Paramètres du compte',
    'manage_users' => 'Gestion des utilisateurs',
    'manage_storage' => 'Gestion du stockage',
    'personalize_your_contacts_data' => 'Personnalisation des données de contacts',
    'cancel_your_account' => 'Annulation du compte',

    /***************************************************************
     * USER PREFERENCES
     **************************************************************/

    'user_preferences_locale_title' => 'Langue de l’application',
    'user_preferences_locale_current_language' => 'Langue courante :',
    'user_preferences_locale_en' => 'Anglais',
    'user_preferences_locale_fr' => 'Français',

    'user_preferences_name_order_title' => 'Personnaliser l’affichage des contacts',
    'user_preferences_name_order_description' => 'Vous pouvez personnaliser la manière dont les contacts sont affichés en fonction de votre propre goût/culture. Peut-être que vous souhaitez utiliser James Bond plutôt que Bond James. Ici, vous pouvez le définir.',
    'user_preferences_name_order_current' => 'Façon actuelle d’afficher le nom des contacts :',
    'user_preferences_name_order_example' => 'Les contacts seront affichés ainsi :',
    'user_preferences_name_order_first_name_last_name' => 'Prénom Nom de famille',
    'user_preferences_name_order_last_name_first_name' => 'Nom de famille Prénom',
    'user_preferences_name_order_first_name_last_name_nickname' => 'Prénom Nom de famille (surnom)',
    'user_preferences_name_order_nickname' => 'surnom',
    'user_preferences_name_order_custom' => 'Ordre personnalisé',

    'user_preferences_date_title' => 'Personnaliser l’affichage des dates',
    'user_preferences_date_description' => 'Vous pouvez choisir comment Monica affiche les dates dans l’application.',
    'user_preferences_date_name' => 'Façon actuelle d’afficher les dates :',

    'user_preferences_number_format_title' => 'Personnaliser l’affichage des données chiffrées',
    'user_preferences_number_format_description' => 'Façon actuelle d’afficher les nombres :',

    'user_preferences_timezone_title' => 'Fuseau horaire',
    'user_preferences_timezone_description' => 'Peu importe où vous êtes dans le monde, vous pouvez avoir les dates affichées selon votre propre fuseau horaire.',
    'user_preferences_timezone_current' => 'Fuseau horaire actuel :',

    'user_preferences_map_title' => 'Utilisation des cartes',
    'user_preferences_map_current' => 'Site choisi pour afficher les cartes :',
    'user_preferences_map_site_google_maps' => 'Google Maps',
    'user_preferences_map_site_google_maps_description' => 'Google Maps offre le plus de précision et de détails, mais n’est pas idéal pour la vie privée.',
    'user_preferences_map_site_open_street_maps' => 'Open Street Maps',
    'user_preferences_map_site_open_street_maps_description' => 'Open Street Maps est une alternative garantissant la vie privée, mais offre bien moins de détails.',

    /***************************************************************
     * NOTIFICATION CHANNELS
     **************************************************************/

    'notification_channel_type_email' => 'Courriel',
    'notification_channel_type_telegram' => 'Telegram',
    'notification_channels_title' => 'Configurer comment nous devons vous notifier',
    'notification_channels_description' => 'Vous pouvez être notifié à travers plusieurs canaux : courriels, Telegram, Facebook. Vous décidez.',
    'notification_channels_email_title' => 'Par courriel',
    'notification_channels_email_cta' => 'Ajouter un courriel',
    'notification_channels_email_field' => 'A quel courriel devrions-nous vous envoyer les notifications ?',
    'notification_channels_email_name' => 'Donnez un nom à ce courriel',
    'notification_channels_email_at' => 'A quel heure devrions nous envoyer la notification, quand un rappel doit être déclencher ?',
    'notification_channels_email_at_word' => 'À',
    'notification_channels_email_help' => 'Nous vous enverrons un courriel de confirmation à cette adresse courriel que vous devrez valider avant qu’on puisse envoyer des notifications à cette adresse.',
    'notification_channels_email_sent_at' => 'Envoyé à :time',
    'notification_channels_email_deactivate' => 'Désactiver',
    'notification_channels_email_activate' => 'Activer',
    'notification_channels_email_send_test' => 'Envoyer un test',
    'notification_channels_email_send_success' => 'Courriel de test envoyé !',
    'notification_channels_email_log' => 'Voir les logs',
    'notification_channels_verif_email_sent' => 'Courriel de vérification envoyé',
    'notification_channels_blank' => 'Ajouter un courriel pour être notifié quand un rappel se déclenche.',
    'notification_channels_success_email' => 'Le courriel de test a été envoyé',
    'notification_channels_success_channel' => 'Le canal a été mis à jour',
    'notification_channels_email_added' => 'Le courriel a été ajouté',
    'notification_channels_email_destroy_confirm' => 'Êtes-vous sûr ? Vous pourrez toujours rajouter le courriel plus tard si vous le voulez.',
    'notification_channels_email_destroy_success' => 'Le courriel a été supprimé',
    'notification_channels_log_title' => 'Historique des notifications envoyées',
    'notification_channels_log_type' => 'Type :',
    'notification_channels_log_label' => 'Libellé :',
    'notification_channels_log_help' => 'Cette page montre toutes les notifications qui ont été envoyés via ce canal par le passé. Cela sert principalement comme une façon de déboguer dans le cas où vous ne recevez pas les notifications que vous avez programmées.',
    'notification_channels_log_blank' => 'Vous n’avez pas encore reçu de notifications dans ce canal.',
    'notification_channels_telegram_title' => 'Par Telegram',
    'notification_channels_telegram_cta' => 'Configurer Telegram',
    'notification_channels_telegram_blank' => 'Vous n’avez pas encore configuré Telegram.',
    'notification_channels_telegram_delete_confirm' => 'Êtes-vous sûr ? Vous pourrez toujours rajouter Telegram plus tard si vous le voulez.',
    'notification_channels_telegram_not_set' => 'Vous n’avez pas encore configuré Telegram dans vos variables d’environnement.',
    'notification_channels_telegram_test_notification' => 'Ceci est une notification de test pour :name',
    'notification_channels_telegram_test_notification_sent' => 'Notification envoyée',
    'notification_channels_telegram_destroy_success' => 'Le canal Telegram a été supprimé',
    'notification_channels_telegram_added' => 'Le canal Telegram a été ajouté',
    'notification_channels_telegram_linked' => 'Votre compte est lié',
    'notification_channels_test_success_telegram' => 'La notification a été envoyée',

    /***************************************************************
     * USER MANAGEMENT
     **************************************************************/

    'users_management_title' => 'Tous les utilisateurs dans ce compte',
    'users_management_cta' => 'Inviter un utilisateur',
    'users_management_administrator' => 'administrateur',
    'users_management_regular_user' => 'Utilisateur régulier',
    'users_management_administrator_role' => 'Administrateur',
    'users_management_invitation_sent' => 'Invitation envoyée',
    'users_management_permission' => 'Quelle permission devrait avoir :name ?',
    'users_management_administrator_role_help' => 'Peut tout faire, y compris ajouter ou supprimer d’autres utilisateurs, gérer la souscription et fermer le compte.',
    'users_management_update_success' => 'L’utilisateur a été mis à jour',
    'users_management_delete_confirmation' => 'Êtes-vous sûr ? On ne peut pas revenir en arrière.',
    'users_management_delete_success' => 'L’utilisateur a été supprimé',
    'users_management_new_title' => 'Inviter quelqu’un',
    'users_management_new_description' => 'Cet utilisateur fera parti du compte, mais n’aura pas accès à toutes les voûtes dans ce compte, sauf s’il a le droit d’y accéder. Cette personne pourra également créer des voûtes.',
    'users_management_new_email' => 'Adresse courriel à qui envoyer l’invitation',
    'users_management_new_permission' => 'Quelle permission devrait avoir l’utilisateur ?',
    'users_management_new_cta' => 'Envoyer l’invitation',
    'users_management_new_success' => 'Invitation envoyée',

    /***************************************************************
     * PERSONNALIZE
     **************************************************************/

    'personalize_title' => 'Personnaliser votre compte',
    'personalize_title_manage_template' => 'Gérer les modèles',
    'personalize_title_manage_module' => 'Gérer les modules',
    'personalize_title_manage_rel_types' => 'Gérer les types de relation',
    'personalize_title_manage_life_event_categories' => 'Gérer les catégories des évènements de vie',
    'personalize_title_manage_group_types' => 'Gérer les types de groupes',
    'personalize_title_manage_activity_types' => 'Gérer les types d’activités',
    'personalize_title_manage_pronouns' => 'Gérer les pronoms',
    'personalize_title_manage_genders' => 'Gérer les genres',
    'personalize_title_manage_adress_types' => 'Gérer les types d’adresses',
    'personalize_title_manage_contact_information_types' => 'Gérer les types d’informations de contact',
    'personalize_title_manage_call_reasons' => 'Gérer les raisons d’appels',
    'personalize_title_manage_pet_categories' => 'Gérer les catégories d’animaux',
    'personalize_title_manage_gift_occasions' => 'Gérer les occasions de cadeaux',
    'personalize_title_manage_gift_states' => 'Gérer les états de cadeaux',
    'personalize_title_manage_currencies' => 'Gérer les devises',

    /***************************************************************
     * PERSONNALIZE TEMPLATES
     **************************************************************/

    'personalize_templates_title' => 'Tous les modèles',
    'personalize_templates_cta' => 'Ajouter un modèle',
    'personalize_templates_help' => 'Les modèles vous permettent d’indiquer quelles données doivent être affichées quand vous consultez vos contacts. Vous pouvez définir autant de modèles que vous le voulez, et choisir quel modèle doit être utilisé sur quel contact.',
    'personalize_templates_help_2' => 'Vous avez besoin d’au moins un modèle pour que vos contacts soient affichés. Sans un modèle, Monica ne saura pas quelle information on doit afficher.',
    'personalize_templates_new_name' => 'Nom du nouveau modèle',
    'personalize_templates_edit_name' => 'Nom',
    'personalize_templates_blank' => 'Créez au moins un template pour utiliser Monica.',
    'personalize_templates_new_success' => 'Le modèle a été crée',
    'personalize_templates_update_success' => 'Le modèle a été mis à jour',
    'personalize_templates_destroy_confirmation' => 'Êtes-vous sûr ? Cela dissociera le modèle des contacts, mais n’effacera pas les contacts eux-mêmes.',
    'personalize_templates_destroy_success' => 'Le modèle a été supprimé',
    'personalize_template_show_title' => 'Ce modèle définit quelles informations sont affichées sur la page d’un contact.',
    'personalize_template_show_description' => 'Un modèle est composé de pages, et sur chaque page, il y a des modules. Vous êtes en plein de contrôle de quelles données il faut afficher.',
    'personalize_template_show_description_2' => 'Prenez note qu’enlever un module d’une page ne va pas effacer les données de ce module. Le module sera simplement caché.',
    'personalize_template_show_page_title' => 'Pages',
    'personalize_template_show_page_cant_moved' => 'Ne peut être déplacé ou supprimé',
    'personalize_template_show_page_cta' => 'Ajouter une page',
    'personalize_template_show_page_new_name' => 'Nom',
    'personalize_template_show_page_blank' => 'Créez au moins une page pour afficher les données de vos contacts.',
    'personalize_template_show_module_title' => 'Modules sur cette page',
    'personalize_template_show_module_cta' => 'Ajouter un module',
    'personalize_template_show_module_available_modules' => 'Modules disponibles :',
    'personalize_template_show_module_already_used' => 'Déjà utilisé sur cette page',
    'personalize_template_show_module_add_module' => 'Ajouter au moins un module.',
    'personalize_template_show_module_select' => 'Merci de choisir la page sur la gauche pour charger les modules.',
    'personalize_template_show_module_add_success' => 'Le module a été ajouté',
    'personalize_template_show_module_remove_success' => 'Le module a été enlevé',
    'personalize_template_show_module_order_success' => 'L’ordre a été enregistré',

    /***************************************************************
     * PERSONNALIZE RELATIONSHIP TYPES
     **************************************************************/

    'personalize_relationship_types_title' => 'Tous les types de relation',
    'personalize_relationship_types_cta' => 'Ajouter un type de relation',
    'personalize_relationship_types_help_1' => 'Quand vous définissez une relation entre deux contacts, par exemple une relation fils-père, Monica crée deux relations, un pour chaque contact :',
    'personalize_relationship_types_help_2' => 'une relation père-fils, affichée sur la page du père,',
    'personalize_relationship_types_help_3' => 'une relation fils-père, affichée sur la page du fils.',
    'personalize_relationship_types_help_4' => 'On les appelle une relation, et une relation inversée. Pour chaque relation, vous devez définir sa relation inverse.',
    'personalize_relationship_types_new_name' => 'Nom du type de groupe',
    'personalize_relationship_types_new_relationship_name' => 'Nom de la relation',
    'personalize_relationship_types_new_relationship_reverse_name' => 'Nom de la relation inversée',
    'personalize_relationship_types_add_relationship' => 'Ajouter un type de relation',
    'personalize_relationship_types_blank' => 'Les types de relation vous permet de lier des contacts et de documenter comment ils sont connectés.',
    'personalize_relationship_types_group_update_success' => 'Le type de groupe a été mis à jour',
    'personalize_relationship_types_group_destroy_confirm' => 'Êtes-vous sûr ? Cela va supprimer toutes les relations de ce type pour tous les contacts qui l’utilisaient.',
    'personalize_relationship_types_group_destroy_success' => 'Le type de groupe a été supprimé',
    'personalize_relationship_types_new_success' => 'Le type de relation a été crée',
    'personalize_relationship_types_update_success' => 'Le type de relation a été mis à jour',
    'personalize_relationship_types_destroy_confirm' => 'Êtes-vous sûr ? Cela va supprimer toutes les relations de ce type pour tous les contacts qui l’utilisaient.',
    'personalize_relationship_types_destroy_success' => 'Le type de relation a été supprimé',

    /***************************************************************
     * PERSONNALIZE CONTACT TYPE INFORMATION
     **************************************************************/

    'personalize_contact_information_types_title' => 'Tous les types d’information de contact',
    'personalize_contact_information_types_cta' => 'Ajouter un type',
    'personalize_contact_information_types_new_name' => 'Nom',
    'personalize_contact_information_types_new_protocol' => 'Protocole',
    'personalize_contact_information_types_new_protocol_help' => 'Une information de contact peut être cliquable. Par exemple, un numéro de téléphone peut être cliquable et ouvrir l’application par défaut sur votre ordinateur. Si vous ne connaissez pas le protocole pour le type que vous ajoutez, vous pouvez simplement omettre ce champ.',
    'personalize_contact_information_types_protocol' => 'Protocole : :name',
    'personalize_contact_information_types_blank' => 'Les types d’information de contact vous permettent de définir les moyens de communication avec vos contacts (téléphone, courriel, …).',
    'personalize_contact_information_types_new_success' => 'Le type d’information de contact a été crée',
    'personalize_contact_information_types_edit_success' => 'Le type d’information de contact a été mis à jour',
    'personalize_contact_information_types_delete_success' => 'Le type d’information de contact a été supprimé',
    'personalize_contact_information_types_blank' => 'Êtes-vous sûr ? Cela va supprimer toutes les informations de contact de ce type pour tous les contacts qui l’utilisaient, sans supprimer les contacts eux mêmes.',

    /***************************************************************
     * STORAGE
     **************************************************************/

    'storage_title' => 'Stockage',
    'storage_account_limit' => 'La limite de votre compte',
    'storage_account_current_usage' => 'Votre utilisation courante',
    'storage_type_document' => 'Documents',
    'storage_type_avatar' => 'Avatars',
    'storage_type_photo' => 'Photos',
];
