<?php

return [
    'sidebar_settings' => 'Account settings',
    'sidebar_settings_export' => 'Export data',
    'sidebar_settings_users' => 'Users',
    'sidebar_settings_subscriptions' => 'Subscription',

    'export_title' => 'Export your account data',
    'export_be_patient' => 'Click the button to start the export. It might take several minutes to process the export - please be patient and do not spam the button.',
    'export_title_sql' => 'Export to SQL',
    'export_sql_explanation' => 'Exporting your data in SQL format allows you to take your data and import it to your own Monica instance. This is only valuable if you do have your own server.',
    'export_sql_cta' => 'Export to SQL',
    'export_sql_link_instructions' => 'Note: <a href=":url">read the instructions</a> to learn more about importing this file to your instance.',

    'currency' => 'Валюта',
    'name' => 'Ваше имя: :firstname :lastname',
    'email' => 'Email',
    'email_placeholder' => 'Введите email',
    'email_help' => 'Этот email используется в качетве логина и на него вы будете получать напоминания.',
    'timezone' => 'Часовой пояс',
    'layout' => 'Дизайн',
    'layout_small' => 'Максимум шириной в 1200 пикселей',
    'layout_big' => 'Шириной во весь экран',
    'save' => 'Обновить настройки',
    'delete_notice' => 'Вы уверены что хотите удалить свой аккаунт? Пути назад нет.',
    'delete_cta' => 'Удалить аккаунт',
    'locale' => 'Язык',
    'locale_en' => 'Английский',
    'locale_fr' => 'Французкий',
    'locale_ru' => 'Русский',

    'users_list_title' => 'Users with access to your account',
    'users_list_add_user' => 'Invite a new user',
    'users_list_you' => 'That\'s you',
    'users_list_invitations_title' => 'Pending invitations',
    'users_list_invitations_explanation' => 'Below are the people you\'ve invited to join Monica as a collaborator.',
    'users_list_invitations_invited_by' => 'invited by :name',
    'users_list_invitations_sent_date' => 'sent on :date',
    'users_blank_title' => 'You are the only one who has access to this account.',
    'users_blank_add_title' => 'Would you like to invite someone else?',
    'users_blank_description' => 'This person will have the same access than you have, and will be able to add, edit or delete contact information.',
    'users_blank_cta' => 'Invite someone',
    'users_add_title' => 'Invite a new user by email to your account',
    'users_add_description' => 'This person will have the same rights as you do, including inviting other users and deleting them (including you). Therefore, make sure you trust this person.',
    'users_add_email_field' => 'Enter the email of the person you want to invite',
    'users_add_confirmation' => 'I confirm that I want to invite this user to my account. This person will access ALL my data and see exactly what I see.',
    'users_add_cta' => 'Invite user by email',
    'users_error_please_confirm' => 'Please confirm that you want to invite this before proceeding with the invitation',
    'users_error_email_already_taken' => 'This email is already taken. Please choose another one',
    'users_error_already_invited' => 'You already have invited this user. Please choose another email address.',
    'users_error_email_not_similar' => 'This is not the email of the person who\'ve invited you.',
    'users_invitation_deleted_confirmation_message' => 'The invitation has been successfully deleted',
    'users_invitations_delete_confirmation' => 'Are you sure you want to delete this invitation?',
    'users_list_delete_confirmation' => 'Are you sure to delete this user from your account?',

    'subscriptions_account_current_plan' => 'Your current plan',
    'subscriptions_account_paid_plan' => 'You are on the :name plan. It costs $:price every month.',
    'subscriptions_account_next_billing' => 'Your subscription will auto-renew on <strong>:date</strong>. You can <a href=":url">cancel subscription</a> anytime.',
    'subscriptions_account_free_plan' => 'You are on the free plan.',
    'subscriptions_account_upgrade' => 'Upgrade your account',
    'subscriptions_account_invoices' => 'Invoices',
    'subscriptions_account_invoices_download' => 'Download',
    'subscriptions_downgrade_title' => 'Downgrade your account to the free plan',
    'subscriptions_downgrade_limitations' => 'The free plan has limitations. In order to be able to downgrade, you need to pass the checklist below:',
    'subscriptions_downgrade_rule_users' => 'You must have only 1 user in your account',
    'subscriptions_downgrade_rule_users_constraint' => 'You currently have <a href=":url">:count users</a> in your account.',
    'subscriptions_downgrade_rule_invitations' => 'You must not have pending invitations',
    'subscriptions_downgrade_rule_invitations_constraint' => 'You currently have <a href="/settings/users/invitations">:count pending invitations</a> sent to people.',
    'subscriptions_downgrade_cta' => 'Downgrade',
    'subscriptions_upgrade_title' => 'Upgrade your account',
    'subscriptions_upgrade_description' => 'Please enter your card details below. Monica uses <a href="https://stripe.com">Stripe</a> to process your payments securely. No credit card information are stored on our servers.',
    'subscriptions_upgrade_credit' => 'Credit or debit card',
    'subscriptions_upgrade_warning' => 'Your account will be instantly updated. You can upgrade, downgrade, or cancel any time. When you cancel, you will never be charged again. However, you will not be refunded for the current month.',
    'subscriptions_upgrade_cta' => ' Charge my card $:price every month',
    'subscriptions_pdf_title' => 'Your :name monthly subscription',
];
