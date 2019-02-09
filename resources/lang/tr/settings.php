<?php

return [
    'sidebar_settings' => 'Hesap ayarları',
    'sidebar_personalization' => 'Kişiselleştirme',
    'sidebar_settings_storage' => 'Saklama alanı',
    'sidebar_settings_export' => 'Verileri dışa aktar',
    'sidebar_settings_users' => 'Kullanıcılar',
    'sidebar_settings_subscriptions' => 'Abonelik',
    'sidebar_settings_import' => 'Verileri içe aktar',
    'sidebar_settings_tags' => 'Etiket Yönetimi',
    'sidebar_settings_api' => 'API',
    'sidebar_settings_dav' => 'DAV Resources',
    'sidebar_settings_security' => 'Güvenlik',

    'export_title' => 'Hesap bilgilerini dışarı aktar',
    'export_be_patient' => 'Dışarı aktarma işlemini başlatmak için butona tıklayın. Bu işlem birkaç dakika sürebilir - lütfen sabırlı olun butona sürekli basmayın.',
    'export_title_sql' => 'SQL Olarak Aktar',
    'export_sql_explanation' => 'Exporting your data in SQL format allows you to take your data and import it to your own Monica instance. This is only valuable if you do have your own server.',
    'export_sql_cta' => 'SQL Olarak Aktar',
    'export_sql_link_instructions' => 'Note: <a href=":url">read the instructions</a> to learn more about importing this file to your instance.',

    'firstname' => 'Ad',
    'lastname' => 'Soyad',
    'name_order' => 'İsim gösterimi',
    'name_order_firstname_lastname' => '<Ad> <Soyad> - Ahmet Doğan',
    'name_order_lastname_firstname' => '<Soyad> <Ad> - Doğan Ahmet',
    'name_order_firstname_lastname_nickname' => '<First name> <Last name> (<Nickname>) - John Doe (Rambo)',
    'name_order_firstname_nickname_lastname' => '<First name> (<Nickname>) <Last name> - Ali Doğan (Ahmet)',
    'name_order_lastname_firstname_nickname' => '<Last name> <First name> (<Nickname>) - Doe John (Rambo)',
    'name_order_lastname_nickname_firstname' => '<Last name> (<Nickname>) <First name> - John doe (Rambo)',
    'name_order_nickname' => '<Nickname> - Ahmet',
    'currency' => 'Para Birimi',
    'name' => 'Adınız: :name',
    'email' => 'E-posta adresi',
    'email_placeholder' => 'E-posta girin',
    'email_help' => 'This is the email used to login, and this is where you’ll receive your reminders.',
    'timezone' => 'Zaman Dilimi',
    'temperature_scale' => 'Temperature scale',
    'temperature_scale_fahrenheit' => 'Fahrenheit',
    'temperature_scale_celsius' => 'Celsius',
    'layout' => 'Görünüm',
    'layout_small' => 'En fazla 1200 piksel genişliğinde',
    'layout_big' => 'Full width of the browser',
    'save' => 'Update preferences',
    'delete_title' => 'Hesabınızı silin',
    'delete_desc' => 'Do you wish to delete your account? Warning: deletion is permanent and all your data will be erased permamently.',
    'reset_desc' => 'Do you wish to reset your account? This will remove all your contacts, and the data associated with them. Your account will not be deleted.',
    'reset_title' => 'Hesabınızı sıfırlayın',
    'reset_cta' => 'Hesabı sıfırla',
    'reset_notice' => 'Are you sure to reset your account? There is no turning back.',
    'reset_success' => 'Your account has been reset successfully',
    'delete_notice' => 'Are you sure to delete your account? There is no turning back.',
    'delete_cta' => 'Hesabı sil',
    'settings_success' => 'Preferences updated!',
    'locale' => 'Language used in the app',
    'locale_ar' => 'Arapça',
    'locale_cs' => 'Çekce',
    'locale_de' => 'Almanca',
    'locale_en' => 'İngilizce',
    'locale_es' => 'İspanyolca',
    'locale_fr' => 'Fransızca',
    'locale_he' => 'İbranice',
    'locale_hr' => 'Hırvatca',
    'locale_it' => 'İtalyanca',
    'locale_nl' => 'Flemenkçe',
    'locale_pt' => 'Portekizce',
    'locale_pt-BR' => 'Brazilian',
    'locale_ru' => 'Rusça',
    'locale_zh' => 'Çince (Basitleştirilmiş)',
    'locale_tr' => 'Türkçe',

    'security_title' => 'Güvenlik',
    'security_help' => 'Change security matters for your account.',
    'password_change' => 'Şifre değişikliği',
    'password_current' => 'Geçerli şifre',
    'password_current_placeholder' => 'Geçerli şifrenizi giriniz',
    'password_new1' => 'Yeni şifre',
    'password_new1_placeholder' => 'Yeni şifrenizi girin',
    'password_new2' => 'Doğrulama',
    'password_new2_placeholder' => 'Yeni şifrenizi tekrar girin',
    'password_btn' => 'Şifreyi Değiştir',
    '2fa_title' => 'İki Aşamalı Kimlik Doğrulaması',
    '2fa_otp_title' => 'İki Aşamalı Kimlik Doğrulaması mobil uygulama',
    '2fa_enable_title' => 'İki aşamalı kimlik doğrulamasını etkinleştir',
    '2fa_enable_description' => 'Hesap güvenliğinizi artırmak için iki aşamalı kimlik doğrulamasını etkinleştirin.',
    '2fa_enable_otp' => 'Open up your two factor authentication mobile app and scan the following QR barcode:',
    '2fa_enable_otp_help' => 'If your two factor authentication mobile app does not support QR barcodes, enter in the following code:',
    '2fa_enable_otp_validate' => 'Please validate the new device you’ve just set:',
    '2fa_enable_success' => 'İki aşamalı kimlik doğrulaması etkinleştirildi',
    '2fa_enable_error' => 'Error when trying to activate Two Factor Authentication',
    '2fa_enable_error_already_set' => 'İki aşamalı kimlik doğrulaması zaten etkinleştirildi',
    '2fa_disable_title' => 'İki Aşamalı Kimlik Doğrulamasını devre dışı bırak',
    '2fa_disable_description' => 'Disable Two Factor Authentication for your account. Be careful, your account will not be secured anymore !',
    '2fa_disable_success' => 'Two Factor Authentication disabled',
    '2fa_disable_error' => 'Error when trying to disable Two Factor Authentication',
    'u2f_title' => 'U2F security key',
    'u2f_enable_description' => 'Add a new U2F security key',
    'u2f_key_name_help' => 'Give your key a name.',
    'u2f_key_name' => 'Key name:',
    'u2f_buttonAdvise' => 'If your security key has a button, press it.',
    'u2f_noButtonAdvise' => 'If it does not, remove it and insert it again.',
    'u2f_success' => 'Your key is detected and validated.',
    'u2f_insertKey' => 'Insert your security key.',
    'u2f_error_other_error' => 'An error occurred.',
    'u2f_error_bad_request' => 'The visited URL doesn’t match the App ID or your are not using HTTPS',
    'u2f_error_configuration_unsupported' => 'Client configuration is not supported.',
    'u2f_error_device_ineligible' => 'The presented device is not eligible for this request. For a registration request this may mean that the token is already registered, and for a sign request it may mean that the token does not know the presented key handle.',
    'u2f_error_timeout' => 'Timeout reached before request could be satisfied.',
    'u2f_last_use' => 'Last use: {timestamp}',
    'u2f_delete_confirmation' => 'Are you sure you want to delete this key?',
    'u2f_delete_success' => 'Key deleted',

    'recovery_title' => 'Kurtarma kodları',
    'recovery_show' => 'Kurtarma kodlarını göster',
    'recovery_copy_help' => 'Copy codes in your clipboard',
    'recovery_help_intro' => 'These are your recovery codes:',
    'recovery_help_information' => 'You can use each recovery code once.',
    'recovery_clipboard' => 'Codes copied in the clipboard',
    'recovery_generate' => 'Generate new codes...',
    'recovery_generate_help' => 'Be aware that generating new codes will invalidate previoulsy generated codes',
    'recovery_already_used_help' => 'Bu kod zaten kullanılmış',

    'users_list_title' => 'Users with access to your account',
    'users_list_add_user' => 'Yeni kullanıcı davet et',
    'users_list_you' => 'Sizin Listeniz',
    'users_list_invitations_title' => 'Bekleyen davetler',
    'users_list_invitations_explanation' => 'Below are the people you’ve invited to join Monica as a collaborator.',
    'users_list_invitations_invited_by' => 'davet eden :name',
    'users_list_invitations_sent_date' => 'sent on :date',
    'users_blank_title' => 'You are the only one who has access to this account.',
    'users_blank_add_title' => 'Would you like to invite someone else?',
    'users_blank_description' => 'This person will have the same access that you have, and will be able to add, edit or delete contact information.',
    'users_blank_cta' => 'Invite someone',
    'users_add_title' => 'Invite a new user by email to your account',
    'users_add_description' => 'This person will have the same rights as you do, including inviting other users and deleting them (including you). Therefore, make sure you trust this person.',
    'users_add_email_field' => 'Enter the email of the person you want to invite',
    'users_add_confirmation' => 'I confirm that I want to invite this user to my account. This person will access ALL my data and see exactly what I see.',
    'users_add_cta' => 'Invite user by email',
    'users_accept_title' => 'Accept invitation and create a new account',
    'users_error_please_confirm' => 'Please confirm that you want to invite this user before proceeding with the invitation',
    'users_error_email_already_taken' => 'This email is already taken. Please choose another one',
    'users_error_already_invited' => 'You already have invited this user. Please choose another email address.',
    'users_error_email_not_similar' => 'This is not the email of the person who’ve invited you.',
    'users_invitation_deleted_confirmation_message' => 'The invitation has been successfully deleted',
    'users_invitations_delete_confirmation' => 'Are you sure you want to delete this invitation?',
    'users_list_delete_confirmation' => 'Are you sure to delete this user from your account?',
    'users_invitation_need_subscription' => 'Adding more users requires a subscription.',

    'subscriptions_account_current_plan' => 'Your current plan',
    'subscriptions_account_current_paid_plan' => 'You are on the :name plan. Thanks so much for being a subscriber.',
    'subscriptions_account_next_billing' => 'Your subscription will auto-renew on <strong>:date</strong>. You can <a href=":url">cancel subscription</a> anytime.',
    'subscriptions_account_free_plan' => 'You are on the free plan.',
    'subscriptions_account_free_plan_upgrade' => 'You can upgrade your account to the :name plan, which costs $:price per month. Here are the advantages:',
    'subscriptions_account_free_plan_benefits_users' => 'Unlimited number of users',
    'subscriptions_account_free_plan_benefits_reminders' => 'Reminders by email',
    'subscriptions_account_free_plan_benefits_import_data_vcard' => 'Import your contacts with vCard',
    'subscriptions_account_free_plan_benefits_support' => 'Support the project on the long run, so we can introduce more great features.',
    'subscriptions_account_upgrade' => 'Hesabınızı yükseltin',
    'subscriptions_account_upgrade_title' => 'Upgrade Monica today and have more meaningful relationships.',
    'subscriptions_account_upgrade_choice' => 'Pick a plan below and join over :customers persons who upgraded their Monica.',
    'subscriptions_account_invoices' => 'Faturalar',
    'subscriptions_account_invoices_download' => 'İndir',
    'subscriptions_account_payment' => 'Which payment option fits you best?',
    'subscriptions_downgrade_title' => 'Downgrade your account to the free plan',
    'subscriptions_downgrade_limitations' => 'The free plan has limitations. In order to be able to downgrade, you need to pass the checklist below:',
    'subscriptions_downgrade_rule_users' => 'You must have only 1 user in your account',
    'subscriptions_downgrade_rule_users_constraint' => 'You currently have <a href=":url">1 user</a> in your account.|You currently have <a href=":url">:count users</a> in your account.',
    'subscriptions_downgrade_rule_invitations' => 'You must not have pending invitations',
    'subscriptions_downgrade_rule_invitations_constraint' => 'You currently have <a href=":url">1 pending invitation</a> sent to people.|You currently have <a href=":url">:count pending invitations</a> sent to people.',
    'subscriptions_downgrade_rule_contacts' => 'You must not have more than :number contacts',
    'subscriptions_downgrade_rule_contacts_constraint' => 'You currently have <a href=":url">1 contact</a>.|You currently have <a href=":url">:count contacts</a>.',
    'subscriptions_downgrade_cta' => 'Downgrade',
    'subscriptions_downgrade_success' => 'You are back to the Free plan!',
    'subscriptions_downgrade_thanks' => 'Thanks so much for having tried the paid plan. We keep adding new features on Monica all the time – so you might want to come back in the future to see if you might be interested in taking a subscription again.',
    'subscriptions_back' => 'Back to settings',
    'subscriptions_upgrade_title' => 'Upgrade your account',
    'subscriptions_upgrade_choose' => 'You picked the :plan plan.',
    'subscriptions_upgrade_infos' => 'We couldn’t be happier. Enter your payment info below.',
    'subscriptions_upgrade_name' => 'Name on card',
    'subscriptions_upgrade_zip' => 'ZIP or postal code',
    'subscriptions_upgrade_credit' => 'Credit or debit card',
    'subscriptions_upgrade_submit' => 'Submit Payment',
    'subscriptions_upgrade_charge' => 'We’ll charge your card USD $:price now. The next charge will be on :date. If you ever change your mind, you can cancel anytime, no questions asked.',
    'subscriptions_upgrade_charge_handled' => 'The payment is handled by <a href=":url">Stripe</a>. No card information touches our server.',
    'subscriptions_upgrade_success' => 'Thank you! You are now subscribed.',
    'subscriptions_upgrade_thanks' => 'Welcome to the community of people who try to make the world a better place.',

    'subscriptions_pdf_title' => 'Your :name monthly subscription',
    'subscriptions_plan_choose' => 'Choose this plan',
    'subscriptions_plan_year_title' => 'Pay annually',
    'subscriptions_plan_year_cost' => '$45/year',
    'subscriptions_plan_year_cost_save' => 'you’ll save 25%',
    'subscriptions_plan_year_bonus' => 'Peace of mind for a whole year',
    'subscriptions_plan_month_title' => 'Aylık ödeme',
    'subscriptions_plan_month_cost' => '$5/ay',
    'subscriptions_plan_month_bonus' => 'Cancel any time',
    'subscriptions_plan_include1' => 'Included with your upgrade:',
    'subscriptions_plan_include2' => 'Unlimited number of contacts • Unlimited number of users • Reminders by email • Import with vCard • Personalization of the contact sheet',
    'subscriptions_plan_include3' => '100% of the profits go the development of this great open source project.',
    'subscriptions_help_title' => 'Additional details you may be curious about',
    'subscriptions_help_opensource_title' => 'What is an open source project?',
    'subscriptions_help_opensource_desc' => 'Monica is an open source project. This means it is built by an entirely benevolent community who just wants to provide a great tool for the greater good. Being open source means the code is publicly available on GitHub, and everyone can inspect it, modify it or enhance it. All the money we raise is dedicated to build better features, have more powerful servers, help pay the bills. Thanks for your help. We couldn’t do it without you – litterally.',
    'subscriptions_help_limits_title' => 'Is there any limit to the number of contacts we can have on the free plan?',
    'subscriptions_help_limits_plan' => 'Yes. Free plans let you manage :number contacts.',
    'subscriptions_help_discounts_title' => 'Do you have discounts for non-profits and education?',
    'subscriptions_help_discounts_desc' => 'We do! Monica is free for students, and free for non-profits and charities. Just contact <a href=":support">the support</a> with a proof of your status and we’ll apply this special status in your account.',
    'subscriptions_help_change_title' => 'What if I change my mind?',
    'subscriptions_help_change_desc' => 'You can cancel anytime, no questions asked, and all by yourself – no need to contact support or whatever. However, you will not be refunded for the current period.',

    'stripe_error_card' => 'Your card was declined. Decline message is: :message',
    'stripe_error_api_connection' => 'Network communication with Stripe failed. Try again later.',
    'stripe_error_rate_limit' => 'Too many requests with Stripe right now. Try again later.',
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
    'import_upload_rule_instructions' => 'Export instructions for <a href="http://osxdaily.com/2015/07/14/export-contacts-mac-os-x/" target="_blank">Contacts.app (macOS)</a> and <a href="http://www.akruto.com/backup-phone-contacts-calendar/how-to-export-google-contacts-to-csv-or-vcard/" target="_blank">Google Contacts</a>.',
    'import_upload_rule_multiple' => 'For now, if your contacts have multiple email addresses or phone numbers, only the first entry will be picked up.',
    'import_upload_rule_limit' => 'Files are limited to 10MB.',
    'import_upload_rule_time' => 'It might take up to 1 minute to upload the contacts and process them. Be patient.',
    'import_upload_rule_cant_revert' => 'Make sure data is accurate before uploading, as you can’t undo the upload.',
    'import_upload_form_file' => 'Your <code>.vcf</code> or <code>.vCard</code> file:',
    'import_upload_behaviour' => 'Import behaviour:',
    'import_upload_behaviour_add' => 'Add new contacts, skip existing',
    'import_upload_behaviour_replace' => 'Replace existing contacts',
    'import_upload_behaviour_help' => 'Note: Replacing will replace all data found in the vCard, but will keep existing contact fields.',
    'import_report_title' => 'Importing report',
    'import_report_date' => 'Date of the import',
    'import_report_type' => 'Type of import',
    'import_report_number_contacts' => 'Number of contacts in the file',
    'import_report_number_contacts_imported' => 'Number of imported contacts',
    'import_report_number_contacts_skipped' => 'Number of skipped contacts',
    'import_report_status_imported' => 'Imported',
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

    'tags_list_title' => 'Etiketler',
    'tags_list_description' => 'You can organize your contacts by setting up tags. Tags work like folders, but you can add more than one tag to a contact. To add a new tag, add it on the contact itself.',
    'tags_list_contact_number' => '1 bağlantı|:count bağlantı',
    'tags_list_delete_success' => 'Etiket başarıyla silindi',
    'tags_list_delete_confirmation' => 'Etiketi silmek istediğinizden emin misiniz? Bağlantılar silinmeyecek, sadece etiket silinecektir.',
    'tags_blank_title' => 'Tags are a great way of categorizing your contacts.',
    'tags_blank_description' => 'Tags work like folders, but you can add more than one tag to a contact. Go to a contact and tag a friend, right below the name. Once a contact is tagged, go back here to manage all the tags in your account.',

    'api_title' => 'API erişimi',
    'api_description' => 'The API can be used to manipulate Monica’s data from an external application, like a mobile application for instance.',

    'api_personal_access_tokens' => 'Personal access tokens',
    'api_pao_description' => 'Make sure you give this token to a source you trust – as they allow you to access all your data.',
    'api_token_title' => 'Personal access token',
    'api_token_create_new' => 'Create New Token',
    'api_token_not_created' => 'You have not created any personal access tokens.',
    'api_token_name' => 'İsim',
    'api_token_delete' => 'Sil',
    'api_token_create' => 'Create Token',
    'api_token_scopes' => 'Scopes',
    'api_token_help' => 'Here is your new personal access token. This is the only time it will be shown so don’t lose it! You may now use this token to make API requests.',

    'api_oauth_clients' => 'Your Oauth clients',
    'api_oauth_clients_desc' => 'This section lets you register your own OAuth clients.',
    'api_oauth_title' => 'Oauth Clients',
    'api_oauth_create_new' => 'Create New Client',
    'api_oauth_edit' => 'Edit Client',
    'api_oauth_not_created' => 'You have not created any OAuth clients.',
    'api_oauth_clientid' => 'Client ID',
    'api_oauth_name' => 'Ad',
    'api_oauth_name_help' => 'Something your users will recognize and trust.',
    'api_oauth_secret' => 'Gizli',
    'api_oauth_create' => 'Create Client',
    'api_oauth_redirecturl' => 'Redirect URL',
    'api_oauth_redirecturl_help' => 'Your application’s authorization callback URL.',

    'api_authorized_clients' => 'List of authorized clients',
    'api_authorized_clients_desc' => 'This section lists all the clients you’ve authorized to access your application. You can revoke this authorization at anytime.',
    'api_authorized_clients_title' => 'Authorized Applications',
    'api_authorized_clients_name' => 'Ad',
    'api_authorized_clients_scopes' => 'Scopes',

    'personalization_tab_title' => 'Personalize your account',

    'personalization_title' => 'Here you can find different settings to configure your account. These features are more for “power users” who want maximum control over Monica.',
    'personalization_contact_field_type_title' => 'Contact field types',
    'personalization_contact_field_type_add' => 'Add new field type',
    'personalization_contact_field_type_description' => 'Here you can configure all the different types of contact fields that you can associate to all your contacts. If in the future, a new social network appears, you will be able to add this new type of ways of contacting your contacts right here.',
    'personalization_contact_field_type_table_name' => 'İsim',
    'personalization_contact_field_type_table_protocol' => 'Protokol',
    'personalization_contact_field_type_table_actions' => 'Eylemler',
    'personalization_contact_field_type_modal_title' => 'Add a new contact field type',
    'personalization_contact_field_type_modal_edit_title' => 'Edit an existing contact field type',
    'personalization_contact_field_type_modal_delete_title' => 'Delete an existing contact field type',
    'personalization_contact_field_type_modal_delete_description' => 'Are you sure you want to delete this contact field type? Deleting this type of contact field will delete ALL the data with this type for all your contacts.',
    'personalization_contact_field_type_modal_name' => 'İsim',
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
    'personalization_genders_modal_question' => 'How should this new gender be called?',
    'personalization_genders_modal_edit' => 'Update gender type',
    'personalization_genders_modal_edit_question' => 'How should this new gender be renamed?',
    'personalization_genders_modal_delete' => 'Delete gender type',
    'personalization_genders_modal_delete_desc' => 'Are you sure you want to delete {name}?',
    'personalization_genders_modal_delete_question' => 'You currently have {count} contact that has this gender. If you delete this gender, what gender should this contact have?|You currently have {count} contacts that have this gender. If you delete this gender, what gender should these contacts have?',
    'personalization_genders_modal_error' => 'Please choose a valid gender from the list.',

    'personalization_reminder_rule_save' => 'The change has been saved',
    'personalization_reminder_rule_title' => 'Reminder rules',
    'personalization_reminder_rule_line' => '{count} day before|{count} days before',
    'personalization_reminder_rule_desc' => 'For every reminder that you set, we can send you an email some days before the event happens. You can toggle those notifications here. Note that those notifications only apply to monthly and yearly reminders.',

    'personalization_module_save' => 'The change has been saved',
    'personalization_module_title' => 'Features',
    'personalization_module_desc' => 'Some people don’t need all the features. Below you can toggle specific features that are used on a contact sheet. This change will affect ALL your contacts. Note that if you turn off one of these features, data will not be lost - we will simply hide the feature.',

    'personalisation_paid_upgrade' => 'This is a premium feature that requires a Paid subscription to be active. Upgrade your account by visiting Settings > Subscription.',

    'reminder_time_to_send' => 'Time of the day reminders should be sent',
    'reminder_time_to_send_help' => 'For your information, your next reminder will be sent on <span title="{dateTimeUtc}" class="reminder-info">{dateTime}</span>.',

    'personalization_activity_type_category_title' => 'Activity type categories',
    'personalization_activity_type_category_add' => 'Add a new activity type category',
    'personalization_activity_type_category_table_name' => 'Ad',
    'personalization_activity_type_category_description' => 'An activity done with one of your contact can have a type and a category type. Your account comes by default with a set of predefined category types, but you can customize everything here.',
    'personalization_activity_type_category_table_actions' => 'Eylemler',
    'personalization_activity_type_category_modal_add' => 'Yeni bir etkinlik türü kategorisi ekle',
    'personalization_activity_type_category_modal_edit' => 'Bir etkinlik türü kategorisini düzenle',
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
    'personalization_life_event_category_family_relationships' => 'Aile & ilişkiler',
    'personalization_life_event_category_home_living' => 'Ev & yaşam',
    'personalization_life_event_category_travel_experiences' => 'Seyahat & deneyimler',
    'personalization_life_event_category_health_wellness' => 'Sağlık & Fitness',

    'personalization_life_event_type_new_job' => 'Yeni iş',
    'personalization_life_event_type_retirement' => 'Emeklilik',
    'personalization_life_event_type_new_school' => 'Yeni okul',
    'personalization_life_event_type_study_abroad' => 'Yurtdışında Eğitim',
    'personalization_life_event_type_volunteer_work' => 'Gönüllü çalışma',
    'personalization_life_event_type_published_book_or_paper' => 'Kitap ya da makale yayını',
    'personalization_life_event_type_military_service' => 'Askerlik hizmeti',
    'personalization_life_event_type_first_met' => 'First met',
    'personalization_life_event_type_new_relationship' => 'New relationship',
    'personalization_life_event_type_engagement' => 'Engagement',
    'personalization_life_event_type_marriage' => 'Marriage',
    'personalization_life_event_type_anniversary' => 'Anniversary',
    'personalization_life_event_type_expecting_a_baby' => 'Expecting a baby',
    'personalization_life_event_type_new_child' => 'Yeni çocuk',
    'personalization_life_event_type_new_family_member' => 'Yeni aile üyesi',
    'personalization_life_event_type_new_pet' => 'Yeni evcil hayvan',
    'personalization_life_event_type_end_of_relationship' => 'End of relationship',
    'personalization_life_event_type_loss_of_a_loved_one' => 'Loss of a loved one',
    'personalization_life_event_type_moved' => 'Taşındı',
    'personalization_life_event_type_bought_a_home' => 'Bought a home',
    'personalization_life_event_type_home_improvement' => 'Home improvement',
    'personalization_life_event_type_holidays' => 'Tatiller',
    'personalization_life_event_type_new_vehicle' => 'New vehicle',
    'personalization_life_event_type_new_roommate' => 'Yeni oda arkadaşı',
    'personalization_life_event_type_overcame_an_illness' => 'Overcame an illness',
    'personalization_life_event_type_quit_a_habit' => 'Quit a habit',
    'personalization_life_event_type_new_eating_habits' => 'New eating habits',
    'personalization_life_event_type_weight_loss' => 'Weight loss',
    'personalization_life_event_type_wear_glass_or_contact' => 'Gözlük ya da lens kullanımı',
    'personalization_life_event_type_broken_bone' => 'Kırık kemik',
    'personalization_life_event_type_removed_braces' => 'Kaldırılan diş telleri',
    'personalization_life_event_type_surgery' => 'Ameliyat',
    'personalization_life_event_type_dentist' => 'Diş Hekimi',
    'personalization_life_event_type_new_sport' => 'Yeni spor',
    'personalization_life_event_type_new_hobby' => 'Yeni hobi',
    'personalization_life_event_type_new_instrument' => 'Yeni enstrüman',
    'personalization_life_event_type_new_language' => 'Yeni dil',
    'personalization_life_event_type_tattoo_or_piercing' => 'Dövme veya piercing',
    'personalization_life_event_type_new_license' => 'Yeni lisans',
    'personalization_life_event_type_travel' => 'Seyahat',
    'personalization_life_event_type_achievement_or_award' => 'Başarılar ya da Ödüller',
    'personalization_life_event_type_changed_beliefs' => 'Değişen inançlar',
    'personalization_life_event_type_first_word' => 'İlk kelime',
    'personalization_life_event_type_first_kiss' => 'İlk öpücük',

    'storage_title' => 'Depolama',
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
];
