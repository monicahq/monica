<?php

return [
    'user_settings' => 'User settings',
    'user_preferences' => 'User preferences',
    'notification_channels' => 'Notification channels',
    'account_settings' => 'Account settings',
    'manage_users' => 'Manage users',
    'manage_storage' => 'Manage storage',
    'personalize_your_contacts_data' => 'Personalize your contacts data',
    'cancel_your_account' => 'Cancel your account',

    /***************************************************************
     * USER PREFERENCES
     **************************************************************/

    'user_preferences_help_title' => 'Help',
    'user_preferences_help_current_language' => 'Display help links in the interface to help you (English only)',

    'user_preferences_locale_title' => 'Language of the application',
    'user_preferences_locale_current_language' => 'Current language:',
    'user_preferences_locale_en' => 'English',
    'user_preferences_locale_fr' => 'French',

    'user_preferences_name_order_title' => 'Customize how contacts should be displayed',
    'user_preferences_name_order_description' => 'You can customize how contacts should be displayed according to your own taste/culture. Perhaps you would want to use James Bond instead of Bond James. Here, you can define it at will.',
    'user_preferences_name_order_current' => 'Current way of displaying contact names:',
    'user_preferences_name_order_example' => 'Contacts will be shown as follow:',
    'user_preferences_name_order_first_name_last_name' => 'First name Last name',
    'user_preferences_name_order_last_name_first_name' => 'Last name First name',
    'user_preferences_name_order_first_name_last_name_nickname' => 'First name Last name (nickname)',
    'user_preferences_name_order_nickname' => 'nickname',
    'user_preferences_name_order_custom' => 'Custom name order',

    'user_preferences_date_title' => 'How should we display dates',
    'user_preferences_date_description' => 'You can choose how you want Monica to display dates in the application.',
    'user_preferences_date_name' => 'Current way of displaying dates:',

    'user_preferences_number_format_title' => 'How should we display numerical values',
    'user_preferences_number_format_description' => 'Current way of displaying numbers:',

    'user_preferences_timezone_title' => 'Timezone',
    'user_preferences_timezone_description' => 'Regardless of where you are located in the world, have dates displayed in your own timezone.',
    'user_preferences_timezone_current' => 'Current timezone:',

    'user_preferences_map_title' => 'What should we use to display maps?',
    'user_preferences_map_current' => 'Current site used to display maps:',
    'user_preferences_map_site_google_maps' => 'Google Maps',
    'user_preferences_map_site_google_maps_description' => 'Google Maps offers the best accuracy and details, but it is not ideal from a privacy standpoint.',
    'user_preferences_map_site_open_street_maps' => 'Open Street Maps',
    'user_preferences_map_site_open_street_maps_description' => 'Open Street Maps is a great privacy alternative, but offers less details.',

    /***************************************************************
     * NOTIFICATION CHANNELS
     **************************************************************/

    'notification_channel_type_email' => 'Email',
    'notification_channel_type_telegram' => 'Telegram',
    'notification_channels_title' => 'Configure how we should notify you',
    'notification_channels_description' => 'You can be notified through different channels: emails, a Telegram message, on Facebook. You decide.',
    'notification_channels_email_title' => 'Via email',
    'notification_channels_email_cta' => 'Add an email',
    'notification_channels_email_field' => 'Which email address should we send the notification to?',
    'notification_channels_email_name' => 'Give this email address a name',
    'notification_channels_email_at' => 'At which time should we send the notification, when the reminder occurs?',
    'notification_channels_email_at_word' => 'At',
    'notification_channels_email_help' => 'We’ll send an email to this email address that you will need to confirm before we can send notifications to this address.',
    'notification_channels_email_sent_at' => 'Sent at :time',
    'notification_channels_email_deactivate' => 'Deactivate',
    'notification_channels_email_activate' => 'Activate',
    'notification_channels_email_send_test' => 'Send test',
    'notification_channels_email_send_success' => 'Test email sent!',
    'notification_channels_email_log' => 'View log',
    'notification_channels_verif_email_sent' => 'Verification email sent',
    'notification_channels_blank' => 'Add an email to be notified when a reminder occurs.',
    'notification_channels_success_email' => 'The test email has been sent',
    'notification_channels_success_channel' => 'The channel has been updated',
    'notification_channels_email_added' => 'The email has been added',
    'notification_channels_email_destroy_confirm' => 'Are you sure? You can always add the email back later on if you want.',
    'notification_channels_email_destroy_success' => 'The email address has been deleted',
    'notification_channels_log_title' => 'History of the notification sent',
    'notification_channels_log_type' => 'Type:',
    'notification_channels_log_label' => 'Label:',
    'notification_channels_log_help' => 'This page shows all the notifications that have been sent in this channel in the past. It primeraly serves as a way to debug in case you don’t receive the notification you’ve set up.',
    'notification_channels_log_blank' => 'You haven’t received a notification in this channel yet.',
    'notification_channels_telegram_title' => 'Via Telegram',
    'notification_channels_telegram_cta' => 'Setup Telegram',
    'notification_channels_telegram_blank' => 'You haven’t setup Telegram yet.',
    'notification_channels_telegram_delete_confirm' => 'Are you sure? You can always add Telegram back later on if you want.',
    'notification_channels_telegram_not_set' => 'You have not setup Telegram in your environment variables yet.',
    'notification_channels_telegram_test_notification' => 'This is a test notification for :name',
    'notification_channels_telegram_test_notification_sent' => 'Notification sent',
    'notification_channels_telegram_destroy_success' => 'The Telegram channel has been deleted',
    'notification_channels_telegram_added' => 'The Telegram channel has been added',
    'notification_channels_telegram_linked' => 'Your account is linked',
    'notification_channels_test_success_telegram' => 'The notification has been sent',

    /***************************************************************
     * USER MANAGEMENT
     **************************************************************/

    'users_management_title' => 'All users in this account',
    'users_management_cta' => 'Invite a new user',
    'users_management_administrator' => 'administrator',
    'users_management_regular_user' => 'Regular user',
    'users_management_administrator_role' => 'Administrator',
    'users_management_invitation_sent' => 'Invitation sent',
    'users_management_permission' => 'What permission should :name have?',
    'users_management_administrator_role_help' => 'Can do everything, including adding or removing other users, managing billing and closing the account.',
    'users_management_update_success' => 'The user has been updated',
    'users_management_delete_confirmation' => 'Are you sure? This can’t be recovered.',
    'users_management_delete_success' => 'The user has been deleted',
    'users_management_new_title' => 'Invite someone',
    'users_management_new_description' => 'This user will be part of your account, but won’t get access to all the vaults in this account unless you give specific access to them. This person will be able to create vaults as well.',
    'users_management_new_email' => 'Email address to send the invitation to',
    'users_management_new_permission' => 'What permission should the user have?',
    'users_management_new_cta' => 'Send invitation',
    'users_management_new_success' => 'Invitation sent',

    /***************************************************************
     * PERSONNALIZE
     **************************************************************/

    'personalize_title' => 'Personalize your account',
    'personalize_title_manage_template' => 'Manage templates',
    'personalize_title_manage_module' => 'Manage modules',
    'personalize_title_manage_rel_types' => 'Manage relationship types',
    'personalize_title_manage_life_event_categories' => 'Manage life event categories',
    'personalize_title_manage_group_types' => 'Manage group types',
    'personalize_title_manage_activity_types' => 'Manage activity types',
    'personalize_title_manage_pronouns' => 'Manage pronouns',
    'personalize_title_manage_genders' => 'Manage genders',
    'personalize_title_manage_adress_types' => 'Manage address types',
    'personalize_title_manage_contact_information_types' => 'Manage contact information types',
    'personalize_title_manage_call_reasons' => 'Manage call reasons',
    'personalize_title_manage_pet_categories' => 'Manage pet categories',
    'personalize_title_manage_gift_occasions' => 'Manage gift occasions',
    'personalize_title_manage_gift_states' => 'Manage gift states',
    'personalize_title_manage_currencies' => 'Manage currencies',

    /***************************************************************
     * PERSONNALIZE TEMPLATES
     **************************************************************/

    'personalize_templates_title' => 'All the templates',
    'personalize_templates_cta' => 'Add a template',
    'personalize_templates_help' => 'Templates let you customize what data should be displayed on your contacts. You can define as many templates as you want, and choose which template should be used on which contact.',
    'personalize_templates_help_2' => 'You need at least one template for contacts to be displayed. Without a template, Monica won’t know which information it should display.',
    'personalize_templates_new_name' => 'Name of the new template',
    'personalize_templates_edit_name' => 'Name',
    'personalize_templates_blank' => 'Create at least one template to use Monica.',
    'personalize_templates_new_success' => 'The template has been created',
    'personalize_templates_update_success' => 'The template has been updated',
    'personalize_templates_destroy_confirmation' => 'Are you sure? This will remove the template from all contacts, but won’t delete the contacts themselves.',
    'personalize_templates_destroy_success' => 'The template has been deleted',
    'personalize_template_show_title' => 'This template will define what information are displayed on a contact page.',
    'personalize_template_show_description' => 'A template is made of pages, and in each page, there are modules. How data is displayed is entirely up to you.',
    'personalize_template_show_description_2' => 'Note that removing a module from a page will not delete the actual data on your contact pages. It will simply hide it.',
    'personalize_template_show_page_title' => 'Pages',
    'personalize_template_show_page_cant_moved' => 'Can’t be moved or deleted',
    'personalize_template_show_page_cta' => 'Add a page',
    'personalize_template_show_page_new_name' => 'Name',
    'personalize_template_show_page_blank' => 'Create at least one page to display contact’s data.',
    'personalize_template_show_module_title' => 'Modules in this page',
    'personalize_template_show_module_cta' => 'Add a module',
    'personalize_template_show_module_available_modules' => 'Available modules:',
    'personalize_template_show_module_already_used' => 'Already used on this page',
    'personalize_template_show_module_add_module' => 'Add at least one module.',
    'personalize_template_show_module_select' => 'Please select a page on the left to load modules.',
    'personalize_template_show_module_add_success' => 'The module has been added',
    'personalize_template_show_module_remove_success' => 'The module has been removed',
    'personalize_template_show_module_order_success' => 'The order has been saved',

    /***************************************************************
     * PERSONNALIZE RELATIONSHIP TYPES
     **************************************************************/

    'personalize_relationship_types_title' => 'All the relationship types',
    'personalize_relationship_types_cta' => 'Add a relationship type',
    'personalize_relationship_types_help_1' => 'When you define a relationship between two contacts, for instance a father-son relationship, Monica creates two relations, one for each contact:',
    'personalize_relationship_types_help_2' => 'a father-son relation—shown on the father page,',
    'personalize_relationship_types_help_3' => 'a son-father relation—shown on the son page.',
    'personalize_relationship_types_help_4' => 'We call them a relation, and its reverse relation. For each relation you define, you need to define its counterpart.',
    'personalize_relationship_types_new_name' => 'Name of the group type',
    'personalize_relationship_types_new_relationship_name' => 'Name of the relationship',
    'personalize_relationship_types_new_relationship_reverse_name' => 'Name of the reverse relationship',
    'personalize_relationship_types_add_relationship' => 'Add a relationship type',
    'personalize_relationship_types_blank' => 'Relationship types let you link contacts and document how they are connected.',
    'personalize_relationship_types_group_update_success' => 'The group type has been updated',
    'personalize_relationship_types_group_destroy_confirm' => 'Are you sure? This will delete all the relationships of this type for all the contacts that were using it.',
    'personalize_relationship_types_group_destroy_success' => 'The group type has been deleted',
    'personalize_relationship_types_new_success' => 'The relationship type has been created',
    'personalize_relationship_types_update_success' => 'The relationship type has been updated',
    'personalize_relationship_types_destroy_confirm' => 'Are you sure? This will delete all the relationships of this type for all the contacts that were using it.',
    'personalize_relationship_types_destroy_success' => 'The relationship type has been deleted',

    /***************************************************************
     * PERSONNALIZE CONTACT TYPE INFORMATION
     **************************************************************/

    'personalize_contact_information_types_title' => 'All the contact information types',
    'personalize_contact_information_types_cta' => 'Add a type',
    'personalize_contact_information_types_new_name' => 'Name',
    'personalize_contact_information_types_new_protocol' => 'Protocol',
    'personalize_contact_information_types_new_protocol_help' => 'A contact information can be clickable. For instance, a phone number can be clickable and launch the default application in your computer. If you do not know the protocol for the type you are adding, you can simply omit this field.',
    'personalize_contact_information_types_protocol' => 'Protocol: :name',
    'personalize_contact_information_types_blank' => 'Contact information types let you define how you can contact all your contacts (phone, email, …).',
    'personalize_contact_information_types_new_success' => 'The contact information type has been created',
    'personalize_contact_information_types_edit_success' => 'The contact information type has been updated',
    'personalize_contact_information_types_delete_success' => 'The contact information type has been deleted',
    'personalize_contact_information_types_blank' => 'Are you sure? This will remove the contact information types from all contacts, but won’t delete the contacts themselves.',

    /***************************************************************
     * STORAGE
     **************************************************************/

    'storage_title' => 'Storage',
    'storage_account_limit' => 'Your account limit',
    'storage_account_current_usage' => 'Your account current usage',
    'storage_type_document' => 'Documents',
    'storage_type_avatar' => 'Avatars',
    'storage_type_photo' => 'Photos',
];
