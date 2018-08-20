<?php

return [
    'sidebar_settings' => 'Paramètres du compte',
    'sidebar_personalization' => 'Personnalisation',
    'sidebar_settings_export' => 'Exporter les données',
    'sidebar_settings_users' => 'Utilisateurs',
    'sidebar_settings_subscriptions' => 'Abonnement',
    'sidebar_settings_import' => 'Importation de données',
    'sidebar_settings_tags' => 'Gestion des étiquettes',
    'sidebar_settings_api' => 'API',
    'sidebar_settings_security' => 'Sécurité',

    'export_title' => 'Exporter les données de votre compte',
    'export_be_patient' => 'Cliquez sur le bouton pour commencer l’export. Cela peut prendre plusieurs minutes pour préparer l’export – merci d’être patient et de ne pas spammer le bouton.',
    'export_title_sql' => 'Exporter en SQL',
    'export_sql_explanation' => 'Exporter les données au format SQL vous permet de récupérer vos données et de les importer dans votre propre instance de Monica. Ceci n’a d’intérêt que si vous avez votre propre serveur.',
    'export_sql_cta' => 'Exporter en SQL',
    'export_sql_link_instructions' => 'Note : <a href=":url">lisez les instructions</a> afin d’apprendre comment importer ce fichier dans votre instance.',

    'firstname' => 'Prénom',
    'lastname' => 'Nom de famille',
    'name_order' => 'Ordre des noms',
    'name_order_firstname_lastname' => '<Prénom> <Nom> – Jean Dupont',
    'name_order_lastname_firstname' => '<Nom> <Prénom> – Dupont Jean',
    'name_order_firstname_lastname_nickname' => '<Prénom> <Nom> (<Surnom>) – Jean Dupont (Jojo)',
    'name_order_firstname_nickname_lastname' => '<Prénom> (<Surnom>) <Nom> – Jean (Jojo) Dupont',
    'name_order_lastname_firstname_nickname' => '<Nom> <Prénom> (<Surnom>) – Dupont Jean (Jojo)',
    'name_order_lastname_nickname_firstname' => '<Nom> (<Surnom>) <Prénom> – Dupont (Jojo) Jean',
    'name_order_nickname' => '<Surnom> – Jojo',
    'currency' => 'Devise',
    'name' => 'Votre nom : :name',
    'email' => 'Adresse courriel',
    'email_placeholder' => 'Entrez le courriel',
    'email_help' => 'Ce courriel est utilisé pour vous connecter à ce compte, et c’est l’adresse que nous utiliserons pour vous envoyer les rappels par courriel.',
    'timezone' => 'Fuseau horaire',
    'layout' => 'Disposition',
    'layout_small' => 'Maximum de 1200 pixels de large',
    'layout_big' => 'Largeur maximale du navigateur',
    'save' => 'Mettre à jour',
    'delete_title' => 'Supprimer votre compte',
    'delete_desc' => 'Souhaitez-vous supprimer votre compte ? Attention : la suppression est permanente et toutes vos données seront supprimées définitivement.',
    'reset_desc' => 'Souhaitez-vous remettre à zéro votre compte ? Ceci effacera tous les contacts ainsi que les données associées. Votre compte ne sera pas effacé.',
    'reset_title' => 'Remettre à zéro votre compte',
    'reset_cta' => 'Remettre à zéro',
    'reset_notice' => 'Êtes-vous sûr de réinitialiser votre compte ? Ceci ne peut pas être annulé.',
    'reset_success' => 'Votre compte a été réinitialisé',
    'delete_notice' => 'Êtes-vous sûr de vouloir supprimer votre compte ? Ceci ne peut pas être annulé.',
    'delete_cta' => 'Effacer le compte',
    'settings_success' => 'Préférences mises à jour',
    'locale' => 'Langue',
    'locale_cs' => 'Tchèque',
    'locale_de' => 'Allemand',
    'locale_en' => 'Anglais',
    'locale_es' => 'Espagnol',
    'locale_fr' => 'Francais',
    'locale_he' => 'Hébreu',
    'locale_it' => 'Italien',
    'locale_nl' => 'Néerlandais',
    'locale_pt' => 'Portugais',
    'locale_ru' => 'Russe',
    'locale_zh' => 'Chinois Simplifié',
    'locale_tr' => 'Turc',

    'security_title' => 'Sécurité',
    'security_help' => 'Changer les questions de sécurité pour votre compte.',
    'password_change' => 'Modification du mot de passe',
    'password_current' => 'Mot de passe actuel',
    'password_current_placeholder' => 'Entrez votre mot de passe actuel',
    'password_new1' => 'Nouveau mot de passe',
    'password_new1_placeholder' => 'Entrer un nouveau mot de passe',
    'password_new2' => 'Confirmation',
    'password_new2_placeholder' => 'Confirmez le nouveau mot de passe',
    'password_btn' => 'Changer votre mot de passe',
    '2fa_title' => 'Authentification à deux facteurs',
    '2fa_otp_title' => 'Application mobile d’authentification à deux facteurs',
    '2fa_enable_title' => 'Activer l’authentification à deux facteurs',
    '2fa_enable_description' => 'Activer l’authentification à deux facteurs pour renforcer la sécurité de votre compte.',
    '2fa_enable_otp' => 'Ouvrez votre application mobile pour l’authentification à deux facteurs et scannez le QR code suivant :',
    '2fa_enable_otp_help' => 'Si votre application mobile pour l’authentification à deux facteurs ne supporte pas les QR codes, entrez le code suivant :',
    '2fa_enable_otp_validate' => 'Veuillez valider le nouvel appareil que vous venez de configurer :',
    '2fa_enable_success' => 'L’authentification à deux facteurs est active',
    '2fa_enable_error' => 'Erreur lors de l’activation de l’authentification à deux facteurs',
    '2fa_enable_error_already_set' => 'L’authentification à deux facteurs est déjà activé',
    '2fa_disable_title' => 'Désactiver l’authentification à deux facteurs',
    '2fa_disable_description' => 'Désactiver l’authentification à deux facteurs pour votre compte. Attention, votre compte ne sera plus securisé !',
    '2fa_disable_success' => 'L’authentification à deux facteurs a été désactivée',
    '2fa_disable_error' => 'Erreur lors de la désactivation de l’authentification à deux facteurs',
    'u2f_title' => 'Clé de sécurité U2F',
    'u2f_enable_description' => 'Ajoutez une nouvelle clé de sécurité U2F',
    'u2f_buttonAdvise' => 'Si votre clé de sécurité dispose d’un bouton, appuyez dessus.',
    'u2f_noButtonAdvise' => 'Si ce n\'est pas le cas, enlevez-la et insérez là à nouveau.',
    'u2f_success' => 'Votre clé est détectée et validée.',
    'u2f_insertKey' => 'Insérer votre clé de sécurité.',
    'u2f_error_other_error' => 'Une erreur est survenue.',
    'u2f_error_bad_request' => 'L\'URL visitée ne correspond pas à l’App ID ou vous n\'utilisez pas HTTPS',
    'u2f_error_configuration_unsupported' => 'La configuration client n’est pas supportée.',
    'u2f_error_device_ineligible' => 'Le dispositif présenté n’est pas admissible pour cette demande. Pour une demande d’enregistrement, cela peut signifier que le jeton est déjà enregistré, et pour une demande d\'authentification, cela peut signifier que le jeton ne connaît pas les clés présentées.',
    'u2f_error_timeout' => 'Délai d’attente atteint avant que la demande ne soit satisfaite.',

    'users_list_title' => 'Utilisateurs avec accès à votre compte',
    'users_list_add_user' => 'Inviter un nouvel utilisateur',
    'users_list_you' => 'C’est vous',
    'users_list_invitations_title' => 'Invitations en attente',
    'users_list_invitations_explanation' => 'Voici les personnes que vous avez invité à rejoindre Monica comme collaborateurs.',
    'users_list_invitations_invited_by' => 'invité par :name',
    'users_list_invitations_sent_date' => 'envoyé le :date',
    'users_blank_title' => 'Vous êtes la seule personne qui a accès à ce compte.',
    'users_blank_add_title' => 'Souhaitez-vous inviter quelqu’un d’autre ?',
    'users_blank_description' => 'Cette personne aura le même accès que vous et sera en mesure d’ajouter, modifier ou supprimer les informations de contact.',
    'users_blank_cta' => 'Inviter quelqu’un',
    'users_add_title' => 'Inviter un nouvel utilisateur par couriel à votre compte',
    'users_add_description' => 'Cette personne aura les mêmes droits que vous, y compris inviter d’autres utilisateurs et les supprimer (y compris vous). Par conséquent, assurez-vous que vous faites confiance à cette personne.',
    'users_add_email_field' => 'Entrez le courriel de la personne que vous souhaitez inviter',
    'users_add_confirmation' => 'Je confirme que je veux inviter cet utilisateur dans mon compte. Cette personne aura toutes mes données d’accès et verra exactement ce que je vois.',
    'users_add_cta' => 'Inviter l’utilisateur par courriel',
    'users_accept_title' => 'Accepter l’invitation et créer un nouveau compte',
    'users_error_please_confirm' => 'Merci de confirmer que vous souhaitez bien inviter cette personne avant d’envoyer l’invitation',
    'users_error_email_already_taken' => 'Ce courriel est déjà pris. Veuillez en utiliser un autre',
    'users_error_already_invited' => 'Vous avez déjà invité cet utilisateur. Veuillez choisir une autre adresse couriel.',
    'users_error_email_not_similar' => 'Ce n’est pas le courriel de la personne qui vous a invité.',
    'users_invitation_deleted_confirmation_message' => 'L’invitation a été supprimée avec succès',
    'users_invitations_delete_confirmation' => 'Êtes-vous sûr de vouloir supprimer de cette invitation ?',
    'users_list_delete_confirmation' => 'Êtes-vous sûr de vouloir supprimer cet utilisateur de votre compte ?',
    'users_invitation_need_subscription' => 'L’ajout d’utilisateurs nécessite un abonnement.',

    'subscriptions_account_current_plan' => 'Votre plan actuel',
    'subscriptions_account_current_paid_plan' => 'Vous êtes sur l’offre :name. Merci beaucoup pour votre inscription.',
    'subscriptions_account_next_billing' => 'Votre abonnement va être renouvelé automatiquement le <strong>:date</strong>. Vous pouvez <a href=":url">annuler l’abonnement</a> quand vous le souhaitez.',
    'subscriptions_account_free_plan' => 'Vous êtes sur le plan gratuit.',
    'subscriptions_account_free_plan_upgrade' => 'Vous pouvez mettre à niveau votre compte en passant au plan :name, qui coûte $:price par mois. En voici les avantages :',
    'subscriptions_account_free_plan_benefits_users' => 'Nombre illimité d’utilisateurs',
    'subscriptions_account_free_plan_benefits_reminders' => 'Rappels par courriel',
    'subscriptions_account_free_plan_benefits_import_data_vcard' => 'Import de vos contacts au format vCard',
    'subscriptions_account_free_plan_benefits_support' => 'Supporter le projet sur le long terme, afin de pouvoir vous proposer plus de fonctionnalités.',
    'subscriptions_account_upgrade' => 'Mettre à jour votre compte',
    'subscriptions_account_upgrade_title' => 'Mettez à niveau Monica aujourd’hui et ayez des relations encore plus significatives.',
    'subscriptions_account_upgrade_choice' => 'Choisissez une offre ci-dessous et rejoignez plus de :customers personnes qui ont mis à niveau leur Monica.',
    'subscriptions_account_invoices' => 'Factures',
    'subscriptions_account_invoices_download' => 'Télécharger',
    'subscriptions_account_payment' => 'Quelle option de paiement vous convient le mieux ?',
    'subscriptions_downgrade_title' => 'Passez votre compte sur le plan gratuit',
    'subscriptions_downgrade_limitations' => 'Le compte gratuit a des limitations. Afin de pouvoir passer à ce plan, vous devez passer les points suivants :',
    'subscriptions_downgrade_rule_users' => 'Vous devez avoir un seul utilisateur dans votre compte',
    'subscriptions_downgrade_rule_users_constraint' => 'Vous avez actuellement <a href=":url">:count utilisateur</a> dans votre compte.|Vous avez actuellement <a href=":url">:count utilisateurs</a> dans votre compte.',
    'subscriptions_downgrade_rule_invitations' => 'Vous ne devez pas avoir d’invitations en attente',
    'subscriptions_downgrade_rule_invitations_constraint' => 'Vous avez actuellement <a href=":url">:count invitation en attente</a> envoyée à des personnes.|Vous avez actuellement <a href=":url">:count invitations en attente</a> envoyées à des personnes.',
    'subscriptions_downgrade_cta' => 'Passer au plan inférieur',
    'subscriptions_downgrade_success' => 'Vous êtes de retour sur l’offre gratuite !',
    'subscriptions_downgrade_thanks' => 'Merci beaucoup d’avoir essayé l’offre payante. Nous continuons à apporter de nouvelles fonctionnalités sur Monica tout le temps – vous pouvez donc revenir à l’occasion pour voir si vous pourriez à nouveau être intéressé·e pour prendre un abonnement.',
    'subscriptions_back' => 'Retourner aux paramètres',
    'subscriptions_upgrade_title' => 'Passer au plan supérieur',
    'subscriptions_upgrade_choose' => 'Vous avez choisi l’offre :plan.',
    'subscriptions_upgrade_infos' => 'Nous ne pourrions être plus heureux. Entrez vos informations de paiement ci-dessous.',
    'subscriptions_upgrade_name' => 'Nom sur la carte',
    'subscriptions_upgrade_zip' => 'Code postal',
    'subscriptions_upgrade_credit' => 'Carte de crédit ou de débit',
    'subscriptions_upgrade_submit' => 'Soumettre le Paiement',
    'subscriptions_upgrade_charge' => 'Nous débiterons votre carte de :price$ USD maintenant. Le prochain paiement aura lieu le :date. Si jamais vous changez d’avis, vous pourrez annuler à tout moment, sans poser de questions.',
    'subscriptions_upgrade_charge_handled' => 'Le paiement est géré par <a href=":url">Stripe</a>. Aucune information bancaire n’arrive sur notre serveur.',
    'subscriptions_upgrade_success' => 'Merci ! Vous êtes maintenant inscrit.',
    'subscriptions_upgrade_thanks' => 'Bienvenue dans la communauté de personnes qui essaient de rendre le monde un peu meilleur.',

    'subscriptions_pdf_title' => 'Votre abonnement :name mensuel',
    'subscriptions_plan_choose' => 'Choisir cette offre',
    'subscriptions_plan_year_title' => 'Payer annuellement',
    'subscriptions_plan_year_cost' => '45$/année',
    'subscriptions_plan_year_cost_save' => 'vous économisez 25%',
    'subscriptions_plan_year_bonus' => 'Tranquillité d’esprit pendant toute une année',
    'subscriptions_plan_month_title' => 'Payer tous les mois',
    'subscriptions_plan_month_cost' => '5$/mois',
    'subscriptions_plan_month_bonus' => 'Annuler à tout moment',
    'subscriptions_plan_include1' => 'Inclus avec votre mise à niveau :',
    'subscriptions_plan_include2' => 'Nombre illimité d’utilisateurs • Rappels par courriel • Importer des vCard • Personnalisation de la vue d’un contact',
    'subscriptions_plan_include3' => '100% des bénéfices vont à l’élaboration de ce beau projet open source.',
    'subscriptions_help_title' => 'Détails supplémentaires, qui peuvent attiser votre curiosité',
    'subscriptions_help_opensource_title' => 'Qu’est-ce qu’un projet open source ?',
    'subscriptions_help_opensource_desc' => 'Monica est un projet open source. Il est le fruit du travail d’une communauté de bénévoles qui veulent juste fournir un outil pour le bien de tous. Être un projet open source signifie que le code est disponible pour tous sur GitHub, et que tout le monde peut l’inspecter, le modifier et l’améliorer. Tout l’argent que nous récoltons sert uniquement à créer des fonctionnalités, avoir plus de serveurs puissants, aider à payer les factures. Merci pour votre aide. Nous ne pourrions rien faire sans vous – littéralement.',
    'subscriptions_help_limits_title' => 'Y a-t-il une limite au nombre de contacts que nous pouvons avoir sur l’offre gratuite ?',
    'subscriptions_help_limits_plan' => 'Absolument pas. L’offre gratuite n’a aucune limite sur le nombre de contacts que vous pouvez avoir.',
    'subscriptions_help_discounts_title' => 'Avez-vous des réductions pour les organismes sans but lucratif et les organismes d’éducation ?',
    'subscriptions_help_discounts_desc' => 'En effet ! Monica est gratuit pour les étudiants, les organismes sans but lucratif et les organismes de bienfaisance. Il suffit de contacter le <a href=":support">support</a> avec un justificatif de votre statut et nous allons appliquer ce statut spécial dans votre compte.',
    'subscriptions_help_change_title' => 'Que se passe-t-il si je change d’avis ?',
    'subscriptions_help_change_desc' => 'Vous pouvez annuler à tout moment, sans question, et par vous-même – aucun support requis. Toutefois, vous ne serez pas remboursé pour la période en cours.',

    'import_title' => 'Importer les contacts dans votre compte',
    'import_cta' => 'Importer des contacts',
    'import_stat' => 'Vous avez importé :number fichiers jusqu’à présent.',
    'import_result_stat' => ':count contact vCard envoyé (:total_imported importé, :total_skipped ignoré)|:count contacts vCard envoyés (:total_imported importés, :total_skipped ignorés)',
    'import_view_report' => 'Voir le rapport',
    'import_in_progress' => 'L’import est en cours. Veuillez recharger la page dans quelques minutes.',
    'import_upload_title' => 'Importer vos contacts depuis un fichier vCard',
    'import_upload_rules_desc' => 'Nous avons toutefois quelques règles :',
    'import_upload_rule_format' => 'Nous supportons les formats <code>.vcard</code> and <code>.vcf</code>.',
    'import_upload_rule_vcard' => 'Nous supportons le format vCard 3.0, qui est le format par défaut de l’application Contacts.app (macOS) et Google Contacts.',
    'import_upload_rule_instructions' => 'Instructions d’export pour <a href="http://osxdaily.com/2015/07/14/export-contacts-mac-os-x/" target="_blank">Contacts.app (macOS)</a> et <a href="http://www.akruto.com/backup-phone-contacts-calendar/how-to-export-google-contacts-to-csv-or-vcard/" target="_blank">Google Contacts</a>.',
    'import_upload_rule_multiple' => 'Pour le moment, si vos contacts ont plusieurs adresses courriels ou numéros de téléphone, seule la première entrée sera choisie.',
    'import_upload_rule_limit' => 'Les fichiers sont limités à 10MB.',
    'import_upload_rule_time' => 'Cela peut prendre 1 minute pour importer les contacts et les traiter. Merci de votre patience.',
    'import_upload_rule_cant_revert' => 'Veuillez vous assurer que les données sont fiables avant d’importer, car l’import ne peut être annulé.',
    'import_upload_form_file' => 'Votre fichier <code>.vcf</code> or <code>.vCard</code> :',
    'import_upload_behaviour' => 'Comportement pour l’importation :',
    'import_upload_behaviour_add' => 'Ajouter les nouveaux contacts, passer les contacts existants',
    'import_upload_behaviour_replace' => 'Remplacer les contacts existants',
    'import_upload_behaviour_help' => 'Remarque : remplacer les contacts mettra à jour toutes les données trouvées dans la vCard, mais gardera les autre valeurs existantes du contact.',
    'import_report_title' => 'Rapport d’import',
    'import_report_date' => 'Date de l’import',
    'import_report_type' => 'Type d’import',
    'import_report_number_contacts' => 'Nombre de contacts dans le fichier',
    'import_report_number_contacts_imported' => 'Nombre de contacts importés',
    'import_report_number_contacts_skipped' => 'Nombre de contacts ignorés',
    'import_report_status_imported' => 'Importés',
    'import_report_status_skipped' => 'Ignorés',
    'import_vcard_contact_exist' => 'Le contact existe déjà',
    'import_vcard_contact_no_firstname' => 'Pas de prénom (obligatoire)',
    'import_vcard_file_not_found' => 'Fichier non trouvé',
    'import_vcard_unknown_entry' => 'Nom de contact inconnu',
    'import_vcard_file_no_entries' => 'Le fichier ne contient pas de donnée',
    'import_blank_title' => 'Vous n’avez encore importé aucun contact.',
    'import_blank_question' => 'Souhaitez-vous importer vos contacts maintenant ?',
    'import_blank_description' => 'Nous pouvons importer les fichiers vCard que vous avez dans votre Google Contacts ou votre gestionnaire de contacts.',
    'import_blank_cta' => 'Importer une vCard',
    'import_need_subscription' => 'L’importation de données nécessite une souscription.',

    'tags_list_title' => 'Étiquettes',
    'tags_list_description' => 'Vous pouvez organiser vos contact avec des étiquettes. Les étiquettes sont comme des dossiers, mais vous pouvez avoir autant d’étiquettes que vous le souhaitez par contact.',
    'tags_list_contact_number' => ':count contact|:count contacts',
    'tags_list_delete_success' => 'L’étiquette a été supprimée avec succès',
    'tags_list_delete_confirmation' => 'Êtes-vous sûr de vouloir supprimer cette étiquette ? Aucun contact ne sera supprimé, seulement l’étiquette.',
    'tags_blank_title' => 'Les étiquettes sont une excellente manière de catégoriser vos contacts.',
    'tags_blank_description' => 'Les étiquettes sont comme des dossiers, mais vous pouvez avoir autant d’étiquette que vous le souhaitez par contact.',

    'api_title' => 'Accès avec l’API',
    'api_description' => 'L’API peut être utilisée pour manipuler les données de Monica depuis une application externe, comme une application mobile par exemple.',

    'api_personal_access_tokens' => 'Jeton d’accès personnel',
    'api_pao_description' => 'Faites attention à ne fournir ce jeton qu’à des personnes de confiance – elles pourront accéder à toutes vos données.',
    'api_token_title' => 'Jeton d’accès personnel',
    'api_token_create_new' => 'Créer un nouveau jeton',
    'api_token_not_created' => 'Vous n’avez pas encore créé de jeton d’accès personnel.',
    'api_token_name' => 'Nom',
    'api_token_delete' => 'Supprimer',
    'api_token_create' => 'Créer un jeton',
    'api_token_scopes' => 'Périmètres',
    'api_token_help' => 'Voici votre nouveau jeton d’accès personnel. Ceci est la seule fois où vous pourrez le voir, ne le perdez pas ! Vous pouvez dès à présent utiliser ce jeton pour lancer des requêtes avec l’API.',

    'api_oauth_clients' => 'Votre client Oauth',
    'api_oauth_clients_desc' => 'Cette section vous permet d’enregistrer votre propre client OAuth.',
    'api_oauth_title' => 'Clients OAuth',
    'api_oauth_create_new' => 'Créer de nouveaux clients',
    'api_oauth_not_created' => 'Vous n’avez pas encore créé de client OAuth.',
    'api_oauth_clientid' => 'Identifiant du client',
    'api_oauth_name' => 'Nom',
    'api_oauth_name_help' => 'Quelque chose que vos utilisateurs reconnaîtront et inspirera la confiance.',
    'api_oauth_secret' => 'Secret',
    'api_oauth_create' => 'Créer un client',
    'api_oauth_redirecturl' => 'URL de redirection',
    'api_oauth_redirecturl_help' => 'URL de rappel d’autorisation de votre application.',

    'api_authorized_clients' => 'Liste de clients autorisés',
    'api_authorized_clients_desc' => 'Cette section liste tous les clients que vous avez autorisés à accéder à votre application. Vous pouvez révoquer cette autorisation à tout moment.',
    'api_authorized_clients_title' => 'Applications autorisées',
    'api_authorized_clients_name' => 'Nom',
    'api_authorized_clients_scopes' => 'Périmètres',

    'personalization_tab_title' => 'Personnalisez votre compte',

    'personalization_title' => 'Ici vous pouvez configurer les différents paramètres de votre compte. Ces fonctionnalités sont pour les « utilisateurs avancés » qui veulent un contrôle maximal sur Monica.',
    'personalization_contact_field_type_title' => 'Types de champs de contact',
    'personalization_contact_field_type_add' => 'Ajouter un nouveau type de champ',
    'personalization_contact_field_type_description' => 'Ici vous pouvez configurer les différents type de champs de contact que vous pouvez associer à tous vos contacts. Si à l’avenir un nouveau réseau social apparaît, vous pourrez ajouter un nouveau type de moyen de contact ici.',
    'personalization_contact_field_type_table_name' => 'Nom',
    'personalization_contact_field_type_table_protocol' => 'Protocole',
    'personalization_contact_field_type_table_actions' => 'Actions',
    'personalization_contact_field_type_modal_title' => 'Ajouter un nouveau type de champ',
    'personalization_contact_field_type_modal_edit_title' => 'Editer un type de champ existant',
    'personalization_contact_field_type_modal_delete_title' => 'Supprimer un type de champ existant',
    'personalization_contact_field_type_modal_delete_description' => 'Etes-vous sûr de vouloir supprimer ce type de champ de contact ? Supprimer ce type de contact effacera TOUTES les données avec ce type de champ pour tous vos contacts.',
    'personalization_contact_field_type_modal_name' => 'Nom',
    'personalization_contact_field_type_modal_protocol' => 'Protocole (optionnel)',
    'personalization_contact_field_type_modal_protocol_help' => 'Chaque nouveau type de champ de contact peut être cliquable. Si un protocole est défini, nous l’utiliserons pour lancer l’action indiquée par le navigateur.',
    'personalization_contact_field_type_modal_icon' => 'Icone (optionnel)',
    'personalization_contact_field_type_modal_icon_help' => 'Vous pouvez associer un icône pour ce champ. Vous devez utiliser une référence vers une icône FontAwesome.',
    'personalization_contact_field_type_delete_success' => 'Le type de champ de contact a été supprimé avec succès.',
    'personalization_contact_field_type_add_success' => 'Le type de champ de contact a été ajouté avec succès.',
    'personalization_contact_field_type_edit_success' => 'Le type de champ de contact a été mis à jour avec succès.',

    'personalization_genders_title' => 'Types de genre',
    'personalization_genders_add' => 'Ajouter un nouveau type de genre',
    'personalization_genders_desc' => 'Vous pouvez définir autant de genres dont vous avez besoin. Il vous faut avoir au moins un type de genre dans votre compte.',
    'personalization_genders_modal_add' => 'Ajouter un nouveau type de genre',
    'personalization_genders_modal_question' => 'Comment ce nouveau genre devrait s’appeler ?',
    'personalization_genders_modal_edit' => 'Mettre à jour le type de genre',
    'personalization_genders_modal_edit_question' => 'Comment ce nouveau genre doit être renommé ?',
    'personalization_genders_modal_delete' => 'Supprimer le type de genre',
    'personalization_genders_modal_delete_desc' => 'Êtes-vous sûr de vouloir supprimer {name} ?',
    'personalization_genders_modal_delete_question' => 'Vous avez actuellement {count} contact qui a ce genre. Si vous supprimez ce genre, quel genre ce contact aurait ?|Vous avez actuellement {count} contacts qui ont ce genre. Si vous supprimez ce genre, quel genre ces contacts devraient avoir ?',
    'personalization_genders_modal_error' => 'Merci de choisir un genre valide depuis cette liste.',

    'personalization_reminder_rule_save' => 'Les modifications ont été enregistrées',
    'personalization_reminder_rule_title' => 'Règles de rappel',
    'personalization_reminder_rule_line' => '{count} jour avant|{count} jours avant',
    'personalization_reminder_rule_desc' => 'Pour chaque rappel que vous mettez en place, nous pouvons envoyer un courriel plusieurs jours avant que l’événement ne se passe. Vous pouvez désactiver ces notifications ici. Notez que ces notifications ne s’appliquent qu’aux rappels mensuels et annuels.',

    'personalization_module_save' => 'Les modifications ont été enregistrées',
    'personalization_module_title' => 'Fonctionnalités',
    'personalization_module_desc' => 'Certaines personnes n’ont pas besoin de toutes ces fonctionnalités. Ci-dessous vous pouvez activer ou désactiver des fonctionnalités spécifiques qui sont utilisées sur la vue d’un contact. Ces modifications s’appliqueront à tous vos contacts. Notez que si vous désactivez une de ces fonctionnalités les données ne seront pas perdues, la fonctionnalité sera simplement masquée.',

    'personalisation_paid_upgrade' => 'Il s’agit d’une fonctionnalité premium qui nécessite un abonnement payant pour être activée. Mettez à niveau votre compte en visitant Paramètres > Abonnement.',

    'reminder_time_to_send' => 'Heure du jour à laquelle les rappels doivent être envoyés',
    'reminder_time_to_send_help' => 'For your information, your next reminder will be sent on <span title="{dateTimeUtc}" class="reminder-info">{dateTime}</span>.',

    'personalization_activity_type_category_title' => 'Catégories de types d’activité',
    'personalization_activity_type_category_add' => 'Ajouter une nouvelle catégorie de type d’activité',
    'personalization_activity_type_category_table_name' => 'Nom',
    'personalization_activity_type_category_description' => 'Une activité effectuée avec l’un de vos contacts peut avoir un type et un type de catégorie. Votre compte est configuré par défaut avec un ensemble de types de catégories prédéfinies, mais vous pouvez tout personnaliser ici.',
    'personalization_activity_type_category_table_actions' => 'Actions',
    'personalization_activity_type_category_modal_add' => 'Ajouter une nouvelle catégorie de type d’activité',
    'personalization_activity_type_category_modal_edit' => 'Modifier une catégorie de type d’activité',
    'personalization_activity_type_category_modal_question' => 'Comment nommer cette nouvelle catégorie ?',
    'personalization_activity_type_add_button' => 'Ajouter un nouveau type d’activité',
    'personalization_activity_type_modal_add' => 'Ajouter un nouveau type d’activité',
    'personalization_activity_type_modal_question' => 'Comment nommer ce nouveau type d’activité ?',
    'personalization_activity_type_modal_edit' => 'Modifier un type d’activité',
    'personalization_activity_type_category_modal_delete' => 'Supprimer une catégorie de type d’activité',
    'personalization_activity_type_category_modal_delete_desc' => 'Êtes-vous sûr de vouloir supprimer cette catégorie ? La suppression entraînera la suppression de tous les types d’activités associées. Toutefois, les activités qui appartiennent à cette catégorie ne seront pas affectées par cette suppression.',
    'personalization_activity_type_modal_delete' => 'Supprimer un type d’activité',
    'personalization_activity_type_modal_delete_desc' => 'Êtes-vous sûr de vouloir supprimer ce type d’activité ? Les activités qui appartiennent à cette catégorie ne seront pas affectées par cette suppression.',
    'personalization_activity_type_modal_delete_error' => 'Impossible de trouver ce type d’activité.',
    'personalization_activity_type_category_modal_delete_error' => 'Impossible de trouver cette catégorie de type d’activité.',
];
