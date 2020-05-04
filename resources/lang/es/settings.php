<?php

return [
    'sidebar_settings' => 'Configuración de cuenta',
    'sidebar_personalization' => 'Personalización',
    'sidebar_settings_storage' => 'Almacenamiento',
    'sidebar_settings_export' => 'Exportar datos',
    'sidebar_settings_users' => 'Usuarios',
    'sidebar_settings_subscriptions' => 'Subscripción',
    'sidebar_settings_import' => 'Importar datos',
    'sidebar_settings_tags' => 'Administrar tags',
    'sidebar_settings_api' => 'API',
    'sidebar_settings_dav' => 'Recursos DAV',
    'sidebar_settings_security' => 'Seguridad',
    'sidebar_settings_auditlogs' => 'Audit logs',

    'title_general' => 'General Information',
    'title_i18n' => 'International settings',
    'title_layout' => 'Layout',

    'me_title' => 'My contact',
    'me_help' => 'This is the contact that represents <em>you</em> in Monica',
    'me_select' => 'Select a contact',
    'me_no_contact' => 'No contact selected yet.',
    'me_select_click' => 'Click here to select a contact.',
    'me_remove_contact' => 'Remove the association',
    'me_choose' => 'Choose yourself',
    'me_choose_placeholder' => 'Choose yourself',

    'export_title' => 'Exportar los datos de tu cuenta',
    'export_be_patient' => 'Click the button to start the export. It might take several minutes to process the export – please be patient and do not spam the button.',
    'export_title_sql' => 'Exportar a SQL',
    'export_sql_explanation' => 'Exportar tus datos en formato SQL te permite tomar tu propia información he importarla en tu propia instancia de Monica. Esto es valioso solo si tú tienes tu propio servidor.',
    'export_sql_cta' => 'Exportar a SQL',
    'export_sql_link_instructions' => 'Nota: <a href=":url">lee las instrucciones</a> para aprender más sobre como importar este archivo en tu propia instancia.',

    'firstname' => 'Nombre',
    'lastname' => 'Apellidos',
    'name_order' => 'Orden de los nombres',
    'name_order_firstname_lastname' => '<Nombre> <Apellido> - John Doe',
    'name_order_lastname_firstname' => '<Apellido> <Nombre> - Doe John',
    'name_order_firstname_lastname_nickname' => '<Nombre> <Apellido> (<Apodo>) - John Doe (Rambo)',
    'name_order_firstname_nickname_lastname' => '<Nombre> (<Apodo>) <Apellido> - John (Rambo) Doe',
    'name_order_lastname_firstname_nickname' => '<Apellido> <Nombre> (<Apodo>) - Doe John (Rambo)',
    'name_order_lastname_nickname_firstname' => '<Apellido> (<Apodo>) <Nombre> - Doe (Rambo) John',
    'name_order_nickname_firstname_lastname' => '<Nickname> (<First name> <Last name>) - Rambo (John Doe)',
    'name_order_nickname_firstname_lastname' => '<Nickname> (<Lasst name> <First name>) - Rambo (Doe John)',
    'name_order_nickname' => '<Apodo> - Rambo',
    'currency' => 'Moneda',
    'name' => 'Tu nombre: :name',
    'email' => 'Correo electrónico',
    'email_placeholder' => 'Ingrese un email',
    'email_help' => 'Este es el email usado para identificarte, y es dónde recibiras tus recordatorios.',
    'timezone' => 'Zona horaria',
    'temperature_scale' => 'Escala de temperatura',
    'temperature_scale_fahrenheit' => 'Fahrenheit',
    'temperature_scale_celsius' => 'Celsius',
    'layout' => 'Disposición',
    'layout_small' => 'Máximo 1200 pixels de ancho',
    'layout_big' => 'Usar ancho del navegador',
    'save' => 'Actualiza preferencias',
    'delete_title' => 'Eliminar tu cuenta',
    'delete_desc' => 'Do you wish to delete your account? Warning: deletion is permanent and all your data will be erased permanently. Your subscription (if you have any) will also be immediately cancelled.',
    'delete_other_desc' => 'Just to be clear: your data in the main database will be deleted immediately. However, as described in our privacy policy, we do daily backups of the database in case of failure and this backup is kept for 30 days – then it’s completely deleted. It’s unrealistic to imagine that we can go in all the backups to delete your specific data. By the way, this data is encrypted on very secure Amazon servers and no one has the encryption key except us. Therefore, your data will completely disappear in 30 days from all the backups, and no one will know this data ever existed in the first place.',
    'reset_desc' => '¿Deseas resetear tu cuenta? Esto borrará todos tus contactos, y todos los datos asociados a ellos. Tu cuenta no será borrada.',
    'reset_title' => 'Resetear tu cuenta',
    'reset_cta' => 'Resetear tu cuenta',
    'reset_notice' => '¿Estás seguro que desea resetear tu cuenta? No se puede deshacer.',
    'reset_success' => 'Tu cuenta ha sido reseteada satisfactoriamente',
    'delete_notice' => '¿Está seguro que desea eliminar su cuenta? No se puede deshacer.',
    'delete_cta' => 'Eliminar cuenta',
    'settings_success' => 'Preferencias actualizadas!',
    'locale' => 'Idiomas utilizados en la aplicación',
    'locale_help' => 'Do you want to help translating Monica or add a new language? Please follow <a href=":url" target="_blank" lang="en">this link for more information</a>.',
    'locale_ar' => 'Árabe',
    'locale_cs' => 'Checo',
    'locale_de' => 'Alemán',
    'locale_en' => 'Ingles',
    'locale_es' => 'Español',
    'locale_fr' => 'Frances',
    'locale_he' => 'Hebreo',
    'locale_hr' => 'Croata',
    'locale_it' => 'Italiano',
    'locale_ja' => 'Japanese',
    'locale_nl' => 'Alemán',
    'locale_pt' => 'Portugues',
    'locale_pt-BR' => 'Portuguese (Brazil)',
    'locale_ru' => 'Ruso',
    'locale_zh' => 'Chino simplificado',
    'locale_tr' => 'Turco',
    'locale_en-GB' => 'English (United Kingdom)',

    'security_title' => 'Seguridad',
    'security_help' => 'Cambiar configuración de seguridad para tu cuenta.',
    'password_change' => 'Cambiar contraseña',
    'password_current' => 'Contraseña actual',
    'password_current_placeholder' => 'Introduce tu contraseña actual',
    'password_new1' => 'Nueva contraseña',
    'password_new1_placeholder' => 'Introduce una nueva contraseña',
    'password_new2' => 'Confirmación',
    'password_new2_placeholder' => 'Vuelve a escribir tu nueva contraseña',
    'password_btn' => 'Cambiar Contraseña',
    '2fa_title' => 'Autenticación en dos pasos',
    '2fa_otp_title' => 'Aplicación móvil de autenticación en dos pasos',
    '2fa_enable_title' => 'Activar autenticación de dos pasos',
    '2fa_enable_description' => 'Activar autenticación en dos pasos para aumentar la seguridad de tu cuenta.',
    '2fa_enable_otp' => 'Abre tu aplicación móvil de autenticación en dos pasos y escanea el siguente código QR:',
    '2fa_enable_otp_help' => 'Si tu aplicación móvil no soporta códigos QR, introduce el siguiente código:',
    '2fa_enable_otp_validate' => 'Por favor, valida el dispositivo que acabas de configurar:',
    '2fa_enable_success' => 'Autenticación en dos pasos activada',
    '2fa_enable_error' => 'Se ha producido al activar la Autenticación en dos pasos',
    '2fa_enable_error_already_set' => 'Two Factor Authentication is already activated',
    '2fa_disable_title' => 'Disable Two Factor Authentication',
    '2fa_disable_description' => 'Disable Two Factor Authentication for your account. Be careful, your account will not be secured anymore !',
    '2fa_disable_success' => 'Two Factor Authentication disabled',
    '2fa_disable_error' => 'Error when trying to disable Two Factor Authentication',

    'webauthn_title' => 'Security key — WebAuthn protocol',
    'webauthn_enable_description' => 'Add a new security key',
    'webauthn_key_name_help' => 'Give your key a name.',
    'webauthn_key_name' => 'Key name:',
    'webauthn_success' => 'Your key is detected and validated.',
    'webauthn_last_use' => 'Last use: {timestamp}',
    'webauthn_delete_confirmation' => 'Are you sure you want to delete this key?',
    'webauthn_delete_success' => 'Key deleted',
    'webauthn_insertKey' => 'Insert your security key.',
    'webauthn_buttonAdvise' => 'If your security key has a button, press it.',
    'webauthn_noButtonAdvise' => 'If it does not, remove it and insert it again.',
    'webauthn_not_supported' => 'Your browser doesn’t currently support WebAuthn.',
    'webauthn_not_secured' => 'WebAuthn only supports secure connections. Please load this page with https scheme.',
    'webauthn_error_already_used' => 'This key is already registered. It’s not necessary to register it again.',
    'webauthn_error_not_allowed' => 'The operation either timed out or was not allowed.',

    'recovery_title' => 'Recovery codes',
    'recovery_show' => 'Get recovery codes',
    'recovery_copy_help' => 'Copy codes in your clipboard',
    'recovery_help_intro' => 'These are your recovery codes:',
    'recovery_help_information' => 'You can use each recovery code once.',
    'recovery_clipboard' => 'Codes copied in the clipboard',
    'recovery_generate' => 'Generate new codes...',
    'recovery_generate_help' => 'Be aware that generating new codes will invalidate previously generated codes',
    'recovery_already_used_help' => 'This code has already been used',

    'users_list_title' => 'Users with access to your account',
    'users_list_add_user' => 'Invite a new user',
    'users_list_you' => 'That’s you',
    'users_list_invitations_title' => 'Invitaciones pendientes',
    'users_list_invitations_explanation' => 'Below are the people you’ve invited to join Monica as a collaborator.',
    'users_list_invitations_invited_by' => 'invited by :name',
    'users_list_invitations_sent_date' => 'sent on :date',
    'users_blank_title' => 'You are the only one who has access to this account.',
    'users_blank_add_title' => 'Would you like to invite someone else?',
    'users_blank_description' => 'This person will have the same access that you have, and will be able to add, edit or delete contact information.',
    'users_blank_cta' => 'Invitar a alguien',
    'users_add_title' => 'Invitar un nuevo usuario por email a tu cuenta',
    'users_add_description' => 'This person will have the same rights as you do, including inviting other users and deleting them (including you). Therefore, make sure you trust this person.',
    'users_add_email_field' => 'Enter the email of the person you want to invite',
    'users_add_confirmation' => 'I confirm that I want to invite this user to my account. This person will access ALL my data and see exactly what I see.',
    'users_add_cta' => 'Invitar usuario por email',
    'users_accept_title' => 'Accept invitation and create a new account',
    'users_error_please_confirm' => 'Please confirm that you want to invite this user before proceeding with the invitation',
    'users_error_email_already_taken' => 'This email is already taken. Please choose another one',
    'users_error_already_invited' => 'You already have invited this user. Please choose another email address.',
    'users_error_email_not_similar' => 'This is not the email of the person who’ve invited you.',
    'users_invitation_deleted_confirmation_message' => 'The invitation has been successfully deleted',
    'users_invitations_delete_confirmation' => 'Are you sure you want to delete this invitation?',
    'users_list_delete_confirmation' => '¿Estás seguro que deseas borar este usuario de tu cuenta?',
    'users_invitation_need_subscription' => 'Añadir más usuarios requiere una suscripción.',

    'subscriptions_account_current_plan' => 'Tu plan actual',
    'subscriptions_account_current_paid_plan' => 'Tú plan actual es :name. Muchas gracias por tu suscripción.',
    'subscriptions_account_next_billing' => 'Your subscription will auto-renew on <strong>:date</strong>.',
    'subscriptions_account_cancel' => 'You can <a href=":url">cancel subscription</a> anytime.',
    'subscriptions_account_free_plan' => 'You are on the free plan.',
    'subscriptions_account_free_plan_upgrade' => 'You can upgrade your account to the :name plan, which costs $:price per month. Here are the advantages:',
    'subscriptions_account_free_plan_benefits_users' => 'Unlimited number of users',
    'subscriptions_account_free_plan_benefits_reminders' => 'Reminders by email',
    'subscriptions_account_free_plan_benefits_import_data_vcard' => 'Import your contacts with vCard',
    'subscriptions_account_free_plan_benefits_support' => 'Support the project on the long run, so we can introduce more great features.',
    'subscriptions_account_upgrade' => 'Upgrade your account',
    'subscriptions_account_upgrade_title' => 'Upgrade Monica today and have more meaningful relationships.',
    'subscriptions_account_upgrade_choice' => 'Pick a plan below and join over :customers persons who upgraded their Monica.',
    'subscriptions_account_invoices' => 'Invoices',
    'subscriptions_account_invoices_download' => 'Download',
    'subscriptions_account_invoices_subscription' => 'Subscription from :startDate to :endDate',
    'subscriptions_account_payment' => 'Which payment option fits you best?',
    'subscriptions_account_confirm_payment' => 'Your payment is currently incomplete, please <a href=":url">confirm your payment</a>.',
    'subscriptions_downgrade_title' => 'Downgrade your account to the free plan',
    'subscriptions_downgrade_limitations' => 'The free plan has limitations. In order to be able to downgrade, you need to pass the checklist below:',
    'subscriptions_downgrade_rule_users' => 'You must have only 1 user in your account',
    'subscriptions_downgrade_rule_users_constraint' => 'You currently have <a href=":url">1 user</a> in your account.|You currently have <a href=":url">:count users</a> in your account.',
    'subscriptions_downgrade_rule_invitations' => 'You must not have pending invitations',
    'subscriptions_downgrade_rule_invitations_constraint' => 'You currently have <a href=":url">1 pending invitation</a> sent to people.|You currently have <a href=":url">:count pending invitations</a> sent to people.',
    'subscriptions_downgrade_rule_contacts' => 'You must not have more than :number active contacts',
    'subscriptions_downgrade_rule_contacts_constraint' => 'You currently have <a href=":url">1 contact</a>.|You currently have <a href=":url">:count contacts</a>.',
    'subscriptions_downgrade_cta' => 'Downgrade',
    'subscriptions_downgrade_success' => 'You are back to the Free plan!',
    'subscriptions_downgrade_thanks' => 'Thanks so much for having tried the paid plan. We keep adding new features on Monica all the time – so you might want to come back in the future to see if you might be interested in taking a subscription again.',
    'subscriptions_back' => 'Volver a ajustes',
    'subscriptions_upgrade_title' => 'Upgrade your account',
    'subscriptions_upgrade_choose' => 'You picked the :plan plan.',
    'subscriptions_upgrade_infos' => 'We couldn’t be happier. Enter your payment info below.',
    'subscriptions_upgrade_name' => 'Name on card',
    'subscriptions_upgrade_zip' => 'ZIP or postal code',
    'subscriptions_upgrade_credit' => 'Credit or debit card',
    'subscriptions_upgrade_submit' => 'Pay {amount}',
    'subscriptions_upgrade_charge' => 'We’ll charge your card :price now. The next charge will be on :date. If you ever change your mind, you can cancel anytime, no questions asked.',
    'subscriptions_upgrade_charge_handled' => 'The payment is handled by <a href=":url">Stripe</a>. No card information touches our server.',
    'subscriptions_upgrade_success' => 'Thank you! You are now subscribed.',
    'subscriptions_upgrade_thanks' => 'Welcome to the community of people who try to make the world a better place.',

    'subscriptions_payment_confirm_title' => 'Confirm your :amount payment',
    'subscriptions_payment_confirm_information' => 'Extra confirmation is needed to process your payment. Please confirm your payment by filling out your payment details below.',
    'subscriptions_payment_succeeded_title' => 'Payment Successful',
    'subscriptions_payment_succeeded' => 'This payment was already successfully confirmed.',
    'subscriptions_payment_cancelled_title' => 'Payment Cancelled',
    'subscriptions_payment_cancelled' => 'This payment was cancelled.',
    'subscriptions_payment_error_name' => 'Please provide your name.',
    'subscriptions_payment_success' => 'The payment was successful.',

    'subscriptions_pdf_title' => 'Your :name monthly subscription',
    'subscriptions_plan_choose' => 'Choose this plan',
    'subscriptions_plan_year_title' => 'Pay annually',
    'subscriptions_plan_year_cost' => '$45/year',
    'subscriptions_plan_year_cost_save' => 'you’ll save 25%',
    'subscriptions_plan_year_bonus' => 'Peace of mind for a whole year',
    'subscriptions_plan_month_title' => 'Pay monthly',
    'subscriptions_plan_month_cost' => '$5/month',
    'subscriptions_plan_month_bonus' => 'Cancel any time',
    'subscriptions_plan_include1' => 'Included with your upgrade:',
    'subscriptions_plan_include2' => 'Unlimited number of contacts • Unlimited number of users • Reminders by email • Import with vCard • Personalization of the contact sheet',
    'subscriptions_plan_include3' => '100% of the profits go the development of this great open source project.',
    'subscriptions_help_title' => 'Additional details you may be curious about',
    'subscriptions_help_opensource_title' => 'What is an open source project?',
    'subscriptions_help_opensource_desc' => 'Monica is an open source project. This means it is built by an entirely benevolent community who just wants to provide a great tool for the greater good. Being open source means the code is publicly available on GitHub, and everyone can inspect it, modify it or enhance it. All the money we raise is dedicated to build better features, have more powerful servers, help pay the bills. Thanks for your help. We couldn’t do it without you – literally.',
    'subscriptions_help_limits_title' => 'Is there any limit to the number of contacts we can have on the free plan?',
    'subscriptions_help_limits_plan' => 'Yes. Free plans let you manage :number contacts.',
    'subscriptions_help_discounts_title' => 'Do you have discounts for non-profits and education?',
    'subscriptions_help_discounts_desc' => 'We do! Monica is free for students, and free for non-profits and charities. Just contact <a href=":support">the support</a> with a proof of your status and we’ll apply this special status in your account.',
    'subscriptions_help_change_title' => 'What if I change my mind?',
    'subscriptions_help_change_desc' => 'You can cancel anytime, no questions asked, and all by yourself – no need to contact support or whatever. However, you will not be refunded for the current period.',

    'stripe_error_card' => 'Your card was declined. Decline message is: :message',
    'stripe_error_api_connection' => 'Network communication with Stripe failed. Try again later.',
    'stripe_error_rate_limit' => 'Too many requests with Stripe right now. Try again later.',
    'stripe_error_invalid_request' => 'Invalid parameters. Try again later.',
    'stripe_error_authentication' => 'Wrong authentication with Stripe',

    'import_title' => 'Import contacts in your account',
    'import_cta' => 'Upload contacts',
    'import_stat' => 'You’ve imported :number files so far.',
    'import_result_stat' => 'Uploaded vCard with 1 contact (:total_imported imported, :total_skipped skipped)|Uploaded vCard with :total_contacts contacts (:total_imported imported, :total_skipped skipped)',
    'import_view_report' => 'View report',
    'import_in_progress' => 'The import is in progress. Reload the page in one minute.',
    'import_upload_title' => 'Import your contacts from a vCard file',
    'import_upload_rules_desc' => 'We do however have some rules:',
    'import_upload_rule_format' => 'We support <code>.vcard</code> and <code>.vcf</code> files.',
    'import_upload_rule_vcard' => 'We support the vCard 3.0 format, which is the default format for Contacts.app (macOS) and Google Contacts.',
    'import_upload_rule_instructions' => 'Export instructions for <a href=":url1" target="_blank" rel="noopener noreferrer">Contacts.app (macOS)</a> and <a href=":url2" target="_blank" rel="noopener noreferrer">Google Contacts</a>.',
    'import_upload_rule_multiple' => 'For now, if your contacts have multiple email addresses or phone numbers, only the first entry will be picked up.',
    'import_upload_rule_limit' => 'Files are limited to 10MB.',
    'import_upload_rule_time' => 'It might take up to 1 minute to upload the contacts and process them. Be patient.',
    'import_upload_rule_cant_revert' => 'Make sure data is accurate before uploading, as you can’t undo the upload.',
    'import_upload_form_file' => 'Your <code>.vcf</code> or <code>.vCard</code> file:',
    'import_upload_behaviour' => 'Import behaviour:',
    'import_upload_behaviour_add' => 'Add new contacts, skip existing',
    'import_upload_behaviour_replace' => 'Replace existing contacts',
    'import_upload_behaviour_help' => 'Note: Replacing will replace all data found in the vCard, but will keep existing contact fields.',
    'import_report_title' => 'Importando reporte',
    'import_report_date' => 'Fecha de importación',
    'import_report_type' => 'Tipo de importación',
    'import_report_number_contacts' => 'Number of contacts in the file',
    'import_report_number_contacts_imported' => 'Number of imported contacts',
    'import_report_number_contacts_skipped' => 'Number of skipped contacts',
    'import_report_status_imported' => 'Importados',
    'import_report_status_skipped' => 'Skipped',
    'import_vcard_parse_error' => 'Error when parsing the vCard entry',
    'import_vcard_contact_exist' => 'Contact already exists',
    'import_vcard_contact_no_firstname' => 'No firstname (mandatory)',
    'import_vcard_file_not_found' => 'File not found',
    'import_vcard_unknown_entry' => 'Unknown contact name',
    'import_vcard_file_no_entries' => 'File contains no entries',
    'import_blank_title' => 'You haven’t imported any contacts yet.',
    'import_blank_question' => 'Would you like to import contacts now?',
    'import_blank_description' => 'We can import vCard files that you can get from Google Contacts or your Contact manager.',
    'import_blank_cta' => 'Import vCard',
    'import_need_subscription' => 'Importing data requires a subscription.',

    'tags_list_title' => 'Tags',
    'tags_list_description' => 'You can organize your contacts by setting up tags. Tags work like folders, but you can add more than one tag to a contact. To add a new tag, add it on the contact itself.',
    'tags_list_contact_number' => '1 contact|:count contacts',
    'tags_list_delete_success' => 'The tag has been successfully deleted',
    'tags_list_delete_confirmation' => 'Are you sure you want to delete the tag? No contacts will be deleted, only the tag.',
    'tags_blank_title' => 'Tags are a great way of categorizing your contacts.',
    'tags_blank_description' => 'Tags work like folders, but you can add more than one tag to a contact. Go to a contact and tag a friend, right below the name. Once a contact is tagged, go back here to manage all the tags in your account.',

    'api_title' => 'API access',
    'api_description' => 'The API can be used to manipulate Monica’s data from an external application, like a mobile application for instance.',
    'api_help' => 'To use the API, a token is mandatory. You can either create a personal access token (Bearer authentication), or authorize an OAuth client to create it for you. See <a href=":url">API documentation</a>.',
    'api_endpoint' => 'The API endpoint for this Monica instance is:',

    'api_personal_access_tokens' => 'Personal access tokens',
    'api_pao_description' => 'Make sure you give this token to a source you trust – as they allow you to access all your data.',
    'api_token_title' => 'Personal Access Tokens',
    'api_token_create_new' => 'Create New Token',
    'api_token_not_created' => 'You have not created any personal access tokens.',
    'api_token_name' => 'Token name',
    'api_token_expire' => 'Expires at {date}',
    'api_token_delete' => 'Delete',
    'api_token_create' => 'Create Token',
    'api_token_scopes' => 'Scopes',
    'api_token_help' => 'Here is your new personal access token. This is the only time it will be shown so don’t lose it! You may now use this token to make API requests.',

    'api_oauth_clients' => 'Your OAuth clients',
    'api_oauth_clients_desc' => 'This section lets you register your own OAuth clients.',
    'api_oauth_clients_desc2' => 'Use this client id to request a new token, and convert authorization codes to access tokens. See <a href="{url}">Laravel Passport documentation</a> for more information.',
    'api_oauth_title' => 'OAuth Clients',
    'api_oauth_create_new' => 'Create New Client',
    'api_oauth_edit' => 'Edit Client',
    'api_oauth_not_created' => 'You have not created any OAuth clients.',
    'api_oauth_clientid' => 'Client ID',
    'api_oauth_name' => 'Name',
    'api_oauth_name_help' => 'Something your users will recognize and trust.',
    'api_oauth_secret' => 'Secret',
    'api_oauth_create' => 'Create Client',
    'api_oauth_redirecturl' => 'Redirect URL',
    'api_oauth_redirecturl_help' => 'Your application’s authorization callback URL.',

    'api_authorized_clients' => 'List of authorized clients',
    'api_authorized_clients_desc' => 'This section lists all the clients you’ve authorized to access your application data. You can revoke this authorization at anytime.',
    'api_authorized_clients_title' => 'Authorized Applications',
    'api_authorized_clients_none' => 'There is no authorized client yet.',
    'api_authorized_clients_name' => 'Name',
    'api_authorized_clients_scopes' => 'Scopes',

    'personalization_tab_title' => 'Personalize your account',

    'personalization_title' => 'Here you can find different settings to configure your account. These features are more for “power users” who want maximum control over Monica.',
    'personalization_contact_field_type_title' => 'Contact field types',
    'personalization_contact_field_type_add' => 'Add new field type',
    'personalization_contact_field_type_description' => 'Here you can configure all the different types of contact fields that you can associate to all your contacts. If in the future, a new social network appears, you will be able to add this new type of ways of contacting your contacts right here.',
    'personalization_contact_field_type_table_name' => 'Name',
    'personalization_contact_field_type_table_protocol' => 'Protocol',
    'personalization_contact_field_type_table_actions' => 'Actions',
    'personalization_contact_field_type_modal_title' => 'Add a new contact field type',
    'personalization_contact_field_type_modal_edit_title' => 'Edit an existing contact field type',
    'personalization_contact_field_type_modal_delete_title' => 'Delete an existing contact field type',
    'personalization_contact_field_type_modal_delete_description' => 'Are you sure you want to delete this contact field type? Deleting this type of contact field will delete ALL the data with this type for all your contacts.',
    'personalization_contact_field_type_modal_name' => 'Name',
    'personalization_contact_field_type_modal_protocol' => 'Protocol (optional)',
    'personalization_contact_field_type_modal_protocol_help' => 'Each new contact field type can be clickable. If a protocol is set, we will use it to trigger the action that is set.',
    'personalization_contact_field_type_modal_icon' => 'Icon (optional)',
    'personalization_contact_field_type_modal_icon_help' => 'You can associate an icon with this contact field type. You need to add a reference to a Font Awesome icon.',
    'personalization_contact_field_type_delete_success' => 'The contact field type has been deleted with success.',
    'personalization_contact_field_type_add_success' => 'The contact field type has been successfully added.',
    'personalization_contact_field_type_edit_success' => 'The contact field type has been successfully updated.',

    'personalization_genders_title' => 'Gender types',
    'personalization_genders_add' => 'Add new gender type',
    'personalization_genders_desc' => 'You can define as many genders as you need to. You need at least one gender type in your account.',
    'personalization_genders_modal_add' => 'Add gender type',
    'personalization_genders_modal_edit' => 'Update gender type',
    'personalization_genders_modal_name' => 'Name',
    'personalization_genders_modal_name_help' => 'The name used to display the gender on a contact page.',
    'personalization_genders_modal_sex' => 'Sex',
    'personalization_genders_modal_sex_help' => 'Used to define the relationships, and during the VCard import/export process.',
    'personalization_genders_modal_default' => 'Select the default gender for a new contact',
    'personalization_genders_modal_delete' => 'Delete gender type',
    'personalization_genders_modal_delete_desc' => 'Are you sure you want to delete {name}?',
    'personalization_genders_modal_delete_question' => 'You currently have {count} contact that has this gender. If you delete this gender, what gender should this contact have?|You currently have {count} contacts that have this gender. If you delete this gender, what gender should these contacts have?',
    'personalization_genders_modal_delete_question_default' => 'This gender is the default one. If you delete this gender, which one will be the next default?',
    'personalization_genders_modal_error' => 'Please choose a valid gender from the list.',
    'personalization_genders_list_contact_number' => '{count} contact|{count} contacts',
    'personalization_genders_table_name' => 'Name',
    'personalization_genders_table_sex' => 'Sex',
    'personalization_genders_table_default' => 'Default',
    'personalization_genders_default' => 'Default gender',
    'personalization_genders_make_default' => 'Change default gender',
    'personalization_genders_select_default' => 'Select default gender',
    'personalization_genders_m' => 'Male',
    'personalization_genders_f' => 'Female',
    'personalization_genders_o' => 'Other',
    'personalization_genders_u' => 'Unknown',
    'personalization_genders_n' => 'None or not applicable',

    'personalization_reminder_rule_save' => 'The change has been saved',
    'personalization_reminder_rule_title' => 'Reminder rules',
    'personalization_reminder_rule_line' => '{count} day before|{count} days before',
    'personalization_reminder_rule_desc' => 'For every reminder that you set, we can send you an email some days before the event happens. You can toggle those notifications here. Note that those notifications only apply to monthly and yearly reminders.',

    'personalization_module_save' => 'The change has been saved',
    'personalization_module_title' => 'Features',
    'personalization_module_desc' => 'Some people don’t need all the features. Below you can toggle specific features that are used on a contact sheet. This change will affect ALL your contacts. Note that if you turn off one of these features, data will not be lost - we will simply hide the feature.',

    'personalisation_paid_upgrade' => 'This is a premium feature that requires a Paid subscription to be active. Upgrade your account by visiting <a href=":url">Settings > Subscription</a>.',
    'personalisation_paid_upgrade_vue' => 'This is a premium feature that requires a Paid subscription to be active. Upgrade your account by visiting <a href="{url}">Settings > Subscription</a>.',

    'reminder_time_to_send' => 'Time of the day reminders should be sent',
    'reminder_time_to_send_help' => 'For your information, your next reminder will be sent on <span title="{dateTimeUtc}" class="reminder-info">{dateTime}</span>.',

    'personalization_activity_type_category_title' => 'Activity type categories',
    'personalization_activity_type_category_add' => 'Add a new activity type category',
    'personalization_activity_type_category_table_name' => 'Name',
    'personalization_activity_type_category_description' => 'An activity done with one of your contact can have a type and a category type. Your account comes by default with a set of predefined category types, but you can customize everything here.',
    'personalization_activity_type_category_table_actions' => 'Actions',
    'personalization_activity_type_category_modal_add' => 'Add a new activity type category',
    'personalization_activity_type_category_modal_edit' => 'Edit an activity type category',
    'personalization_activity_type_category_modal_question' => 'How should we name this new category?',
    'personalization_activity_type_add_button' => 'Add a new activity type',
    'personalization_activity_type_modal_add' => 'Add a new activity type',
    'personalization_activity_type_modal_question' => 'How should we name this new activity type?',
    'personalization_activity_type_modal_edit' => 'Edit an activity type',
    'personalization_activity_type_category_modal_delete' => 'Delete an activity type category',
    'personalization_activity_type_category_modal_delete_desc' => 'Are you sure you want to delete this category? Deleting it will delete all associated activity types. However, activities that belong to this category will not be affected by this deletion.',
    'personalization_activity_type_modal_delete' => 'Delete an activity type',
    'personalization_activity_type_modal_delete_desc' => 'Are you sure you want to delete this activity type? Activities that belong to this category will not be affected by this deletion.',
    'personalization_activity_type_modal_delete_error' => 'We can’t find this activity type.',
    'personalization_activity_type_category_modal_delete_error' => 'We can’t find this activity type category.',

    'personalization_life_event_category_work_education' => 'Work & education',
    'personalization_life_event_category_family_relationships' => 'Family & relationships',
    'personalization_life_event_category_home_living' => 'Home & living',
    'personalization_life_event_category_travel_experiences' => 'Travel & experiences',
    'personalization_life_event_category_health_wellness' => 'Health & wellness',

    'personalization_life_event_type_new_job' => 'New job',
    'personalization_life_event_type_retirement' => 'Retirement',
    'personalization_life_event_type_new_school' => 'New school',
    'personalization_life_event_type_study_abroad' => 'Study abroad',
    'personalization_life_event_type_volunteer_work' => 'Volunteer work',
    'personalization_life_event_type_published_book_or_paper' => 'Published a book or paper',
    'personalization_life_event_type_military_service' => 'Military service',
    'personalization_life_event_type_first_met' => 'First met',
    'personalization_life_event_type_new_relationship' => 'New relationship',
    'personalization_life_event_type_engagement' => 'Engagement',
    'personalization_life_event_type_marriage' => 'Marriage',
    'personalization_life_event_type_anniversary' => 'Anniversary',
    'personalization_life_event_type_expecting_a_baby' => 'Expecting a baby',
    'personalization_life_event_type_new_child' => 'New child',
    'personalization_life_event_type_new_family_member' => 'New family member',
    'personalization_life_event_type_new_pet' => 'New pet',
    'personalization_life_event_type_end_of_relationship' => 'End of relationship',
    'personalization_life_event_type_loss_of_a_loved_one' => 'Loss of a loved one',
    'personalization_life_event_type_moved' => 'Moved',
    'personalization_life_event_type_bought_a_home' => 'Bought a home',
    'personalization_life_event_type_home_improvement' => 'Home improvement',
    'personalization_life_event_type_holidays' => 'Holidays',
    'personalization_life_event_type_new_vehicle' => 'New vehicle',
    'personalization_life_event_type_new_roommate' => 'New roommate',
    'personalization_life_event_type_overcame_an_illness' => 'Overcame an illness',
    'personalization_life_event_type_quit_a_habit' => 'Quit a habit',
    'personalization_life_event_type_new_eating_habits' => 'New eating habits',
    'personalization_life_event_type_weight_loss' => 'Weight loss',
    'personalization_life_event_type_wear_glass_or_contact' => 'Wear glass or contact',
    'personalization_life_event_type_broken_bone' => 'Broken bone',
    'personalization_life_event_type_removed_braces' => 'Removed braces',
    'personalization_life_event_type_surgery' => 'Surgery',
    'personalization_life_event_type_dentist' => 'Dentist',
    'personalization_life_event_type_new_sport' => 'New sport',
    'personalization_life_event_type_new_hobby' => 'New hobby',
    'personalization_life_event_type_new_instrument' => 'New instrument',
    'personalization_life_event_type_new_language' => 'New language',
    'personalization_life_event_type_tattoo_or_piercing' => 'Tattoo or piercing',
    'personalization_life_event_type_new_license' => 'New license',
    'personalization_life_event_type_travel' => 'Travel',
    'personalization_life_event_type_achievement_or_award' => 'Achievement or award',
    'personalization_life_event_type_changed_beliefs' => 'Changed beliefs',
    'personalization_life_event_type_first_word' => 'First word',
    'personalization_life_event_type_first_kiss' => 'First kiss',

    'storage_title' => 'Storage',
    'storage_account_info' => 'Your account limit: :accountLimit Mb /  Your current usage: :currentAccountSize Mb (:percentUsage%)',
    'storage_upgrade_notice' => 'Upgrade your account to be able to upload documents and photos.',
    'storage_description' => 'Here you can see all the documents and photos uploaded about your contacts.',

    'dav_title' => 'WebDAV',
    'dav_description' => 'Here you can find all settings to use WebDAV resources for CardDAV and CalDAV exports.',
    'dav_copy_help' => 'Copy into your clipboard',
    'dav_clipboard_copied' => 'Value copied into your clipboard',
    'dav_url_base' => 'Base url for all CardDAV and CalDAV resources:',
    'dav_connect_help' => 'You can connect your contacts and/or calendars with this base url on you phone or computer.',
    'dav_connect_help2' => 'Use your login (email) and password, or create an API token to authenticate.',
    'dav_url_carddav' => 'CardDAV url for Contacts resource:',
    'dav_url_caldav_birthdays' => 'CalDAV url for Birthdays resources:',
    'dav_url_caldav_tasks' => 'CalDAV url for Tasks resources:',
    'dav_title_carddav' => 'CardDAV',
    'dav_title_caldav' => 'CalDAV',
    'dav_carddav_export' => 'Export all contacts in one file',
    'dav_caldav_birthdays_export' => 'Export all birthdays in one file',
    'dav_caldav_tasks_export' => 'Export all tasks in one file',

    'archive_title' => 'Archive all your contacts in your account',
    'archive_desc' => 'This will archive all the contacts in your account.',
    'archive_cta' => 'Archive all your contacts',

    'logs_title' => 'Everything that happened to this account',
    'logs_author' => 'By :name on :date',
];
