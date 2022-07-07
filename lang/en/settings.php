<?php

return [
    'user_settings' => 'User settings',
    'user_preferences' => 'User preferences',
    'notification_channels' => 'Notification channels',
    'account_settings' => 'Account settings',
    'manage_users' => 'Manage users',
    'personalize_your_contacts_data' => 'Personalize your contacts data',
    'cancel_your_account' => 'Cancel your account',

    /***************************************************************
     * USER PREFERENCES
     **************************************************************/

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
];
