<?php

/**
 * ⚠️ Editing not allowed except for 'en' language.
 *
 * @see https://github.com/monicahq/monica/blob/master/docs/contribute/translate.md for translations.
 */

return [
    'sidebar_settings' => '帐户设置',
    'sidebar_personalization' => '个性化',
    'sidebar_settings_storage' => '存储空间',
    'sidebar_settings_export' => '导出数据',
    'sidebar_settings_users' => '用户',
    'sidebar_settings_subscriptions' => '订阅',
    'sidebar_settings_import' => '导入数据',
    'sidebar_settings_tags' => 'Tag management',
    'sidebar_settings_api' => 'API',
    'sidebar_settings_dav' => 'Dav 资源',
    'sidebar_settings_security' => '安全',
    'sidebar_settings_auditlogs' => '追踪日志',

    'title_general' => '基本信息',
    'title_i18n' => '本地化',
    'title_layout' => '布局',

    'me_title' => 'Me as a contact',
    'me_help' => '这个联系人在Monica代表了 <em>你</em>',
    'me_select' => '选择联系人',
    'me_no_contact' => '没有选择联系人',
    'me_select_click' => '单击此处选择一位联系人',
    'me_remove_contact' => '删除关联',
    'me_choose' => '选择自己',
    'me_choose_placeholder' => '选择自己',

    'export_title' => '导出帐户数据',
    'export_be_patient' => 'Click the button to start the export. It may take several minutes to process the export – please be patient and do not repeatedly click the button.',
    'export_title_sql' => 'Export data to SQL',
    'export_sql_explanation' => 'Exporting your data in SQL format allows you to take your data and import it to your own Monica instance. This is only useful if you are running Monica on your own server.',
    'export_sql_cta' => 'Export data to SQL',
    'export_sql_link_instructions' => '<a href=":url">Read the instructions</a> to learn how to import this file into your instance.',

    'firstname' => '名',
    'lastname' => '姓氏',
    'name_order' => '名称顺序',
    'name_order_firstname_lastname' => '<First name> <Last name> – John Doe',
    'name_order_lastname_firstname' => '<Last name> <First name> – Doe John',
    'name_order_firstname_lastname_nickname' => '<First name> <Last name> (<Nickname>) – John Doe (Rambo)',
    'name_order_firstname_nickname_lastname' => '<First name> (<Nickname>) <Last name> – John (Rambo) Doe',
    'name_order_lastname_firstname_nickname' => '<Last name> <First name> (<Nickname>) – Doe John (Rambo)',
    'name_order_lastname_nickname_firstname' => '<Last name> (<Nickname>) <First name> – Doe (Rambo) John',
    'name_order_nickname_firstname_lastname' => '<Nickname> (<First name> <Last name>) – Rambo (John Doe)',
    'name_order_nickname_lastname_firstname' => '<Nickname> (<Last name> <First name>) – Rambo (Doe John)',
    'name_order_nickname' => '<Nickname> – Rambo',
    'currency' => '货币',
    'name' => '您的姓名: :name',
    'email' => '电子邮件地址',
    'email_placeholder' => '输入电子邮箱',
    'email_help' => 'This is the email used to login, and this is where Monica will send your reminders.',
    'timezone' => '时区',
    'temperature_scale' => '温度单位',
    'temperature_scale_fahrenheit' => '华氏度',
    'temperature_scale_celsius' => '摄氏度',
    'layout' => '布局',
    'layout_small' => '最大1200像素宽',
    'layout_big' => '浏览器的全宽度',
    'save' => '更新偏好',
    'delete_title' => '删除您的帐户',
    'delete_desc' => 'Do you wish to delete your account? Deletion is permanent and all of your data will be erased permanently. If you have a subscription, it will be cancelled immediately.',
    'delete_other_desc' => 'Your data in the main database will be deleted immediately. As described in our privacy policy, we carry out daily backups, securely encrypted backups of the database and this backup is kept for 30 days after which it is completely deleted. We cannot delete specific data from the backups we hold any earlier than this.  All of your data will be completely deleted within 30 days of your account’s deletion.',
    'reset_desc' => 'Do you wish to reset your account? This will remove all your contacts, and all of the data associated with them. Your account will not be deleted.',
    'reset_title' => '删除您的帐户',
    'reset_cta' => '重置帐户',
    'reset_notice' => 'Are you sure to reset your account? This is permanent and cannot be undone.',
    'reset_success' => 'Your account has been reset successfully.',
    'delete_notice' => 'Are you sure you want to delete your account? This is permanent and cannot be undone. All of your data will be deleted and will not be recoverable.',
    'delete_cta' => '删除帐户',
    'settings_success' => '偏好设置已更新',
    'locale' => '应用程序中使用的语言',
    'locale_help' => '您想要帮助翻译Monica或添加新语言吗？请点击 <a href=":url" target="_blank" lang="en">了解更多信息</a>。',
    'locale_ar' => '阿拉伯文',
    'locale_cs' => '捷克文',
    'locale_de' => '德文',
    'locale_en' => '英文',
    'locale_es' => '西班牙文',
    'locale_fr' => '法文',
    'locale_he' => '希伯来文',
    'locale_hr' => '克罗地亚文',
    'locale_id' => 'Indonesian',
    'locale_it' => '意大利文',
    'locale_ja' => 'Japanese',
    'locale_nl' => '荷兰文',
    'locale_pt' => '葡萄牙文',
    'locale_pt-BR' => '葡萄牙语 (巴西)',
    'locale_ru' => '俄文',
    'locale_sv' => 'Swedish',
    'locale_zh' => '简体中文',
    'locale_zh-TW' => 'Chinese Traditional',
    'locale_tr' => '土耳其文',
    'locale_en-GB' => '英语 (英国)',

    'security_title' => '安全',
    'security_help' => '更改您的帐户的安全选项。',
    'password_change' => 'Change your password',
    'password_current' => '当前密码',
    'password_current_placeholder' => '输入当前密码',
    'password_new1' => '新密码',
    'password_new1_placeholder' => 'Enter your new password',
    'password_new2' => 'Confirm your new password',
    'password_new2_placeholder' => 'Retype your new password',
    'password_btn' => '更改密码',
    '2fa_title' => '双重验证',
    '2fa_otp_title' => '用于二次验证的App',
    '2fa_enable_title' => '启用二次验证',
    '2fa_enable_description' => 'Enable Two Factor Authentication to increase the security of your account.',
    '2fa_enable_otp' => 'Open up your Two Factor Authentication mobile app and scan the following QR barcode:',
    '2fa_enable_otp_help' => 'If your Two Factor Authentication mobile app does not support QR barcodes, enter in the following code:',
    '2fa_enable_otp_validate' => 'Please validate the new device you’ve just set up:',
    '2fa_enable_success' => '双重认证已激活',
    '2fa_enable_error' => '尝试激活双重身份验证时出错',
    '2fa_enable_error_already_set' => '二次验证已激活',
    '2fa_disable_title' => '关闭双重身份验证',
    '2fa_disable_description' => 'Disable Two Factor Authentication for your account. Be careful, your account will be much less secure!',
    '2fa_disable_success' => '双重身份认证已禁用',
    '2fa_disable_error' => '尝试禁用双重身份验证时出错',

    'webauthn_title' => '安全钥匙 - WebAuthn',
    'webauthn_enable_description' => '添加一个安全钥匙',
    'webauthn_key_name_help' => '给你的钥匙起个名字',
    'webauthn_key_name' => '钥匙名称:',
    'webauthn_success' => '您的钥匙已被检测到并验证完毕。',
    'webauthn_last_use' => '最后使用: {timestamp}',
    'webauthn_delete_confirmation' => '确实要删除这个钥匙吗?',
    'webauthn_delete_success' => '钥匙已删除',
    'webauthn_insertKey' => '插入您的安全钥匙',
    'webauthn_buttonAdvise' => '如果您的安全钥匙有按钮，请按下它。',
    'webauthn_noButtonAdvise' => '如果没有, 请将其拔出并再次插入。',
    'webauthn_not_supported' => '您的游览器并不支持WebAuthn',
    'webauthn_not_secured' => 'WebAuthn只支持SSL连接，请使用https打开这个页面',
    'webauthn_error_already_used' => '这个钥匙已经注册，您无需在注册一次。',
    'webauthn_error_not_allowed' => '操作超时或不允许。',

    'recovery_title' => '恢复代码',
    'recovery_show' => '获取恢复代码',
    'recovery_copy_help' => '复制到您的剪贴板',
    'recovery_help_intro' => '以下是您的恢复代码:',
    'recovery_help_information' => '您可以使用每个恢复代码一次。',
    'recovery_clipboard' => 'Codes copied to the clipboard.',
    'recovery_generate' => 'Generate new codes…',
    'recovery_generate_help' => 'Generating new codes will invalidate previously generated codes.',
    'recovery_already_used_help' => 'This code has already been used.',

    'users_list_title' => '可以访问您的帐户的用户',
    'users_list_add_user' => '邀请新用户',
    'users_list_you' => '这是你',
    'users_list_invitations_title' => '待处理的邀请',
    'users_list_invitations_explanation' => '已邀请',
    'users_list_invitations_invited_by' => '被:name邀请',
    'users_list_invitations_sent_date' => '在:date发送',
    'users_blank_title' => '您是唯一可以访问此帐户的人。',
    'users_blank_add_title' => '你想邀请别人吗？',
    'users_blank_description' => '此人将具有您拥有的相同访问权限, 并且可以添加、编辑或删除联系人信息。',
    'users_blank_cta' => '邀请他人加入',
    'users_add_title' => 'Invite a new user to your account by email',
    'users_add_description' => 'This person will have the same access as you do, including inviting or deleting other users, including you. Make sure you trust this person before giving them access.',
    'users_add_email_field' => '输入您要邀请的人的电子邮件',
    'users_add_confirmation' => 'I confirm that I want to invite this user to my account. I understand that this person will have access to ALL of my data and see exactly what I see.',
    'users_add_cta' => '通过电子邮件邀请用户',
    'users_accept_title' => '接受邀请并新建一个账号',
    'users_error_please_confirm' => '请您先确认您要邀请此用户',
    'users_error_email_already_taken' => '这个电子邮件已经存在，请另选一个！',
    'users_error_already_invited' => '您已经邀请了此用户。请选择其他电子邮件地址。',
    'users_error_email_not_similar' => '这不是邀请人的电子邮件。',
    'users_invitation_deleted_confirmation_message' => '已成功删除邀请',
    'users_invitations_delete_confirmation' => '确实要删除此邀请吗？',
    'users_list_delete_confirmation' => '是否确实要从您的帐户中删除此用户？',
    'users_invitation_need_subscription' => '您需要升级账户才能添加更多用户',

    'subscriptions_account_current_plan' => '您当前的订阅',
    'subscriptions_account_current_paid_plan' => '您当前的订阅是：:name，感谢您的订阅。',
    'subscriptions_account_next_billing' => '您的订阅将在 <strong>:date</strong> 自动续费',
    'subscriptions_account_cancel' => '您可以随时 <a href=":url">取消订阅</a>。',
    'subscriptions_account_free_plan' => '您正在使用免费版',
    'subscriptions_account_free_plan_upgrade' => '您可以将您的帐户升级为:name, 它的成本为每月$:price。您将享有以下特权:',
    'subscriptions_account_free_plan_benefits_users' => '不限数量的用户',
    'subscriptions_account_free_plan_benefits_reminders' => '电子邮件提醒',
    'subscriptions_account_free_plan_benefits_import_data_vcard' => '从 vCard 文件导入联系人',
    'subscriptions_account_free_plan_benefits_support' => 'Support the project in the long run, so we can introduce more great features.',
    'subscriptions_account_upgrade' => '更新您的账户',
    'subscriptions_account_upgrade_title' => '立即升级您的Monica账户吧！',
    'subscriptions_account_upgrade_choice' => '在下方选择一个订阅（已有 :customers 订阅了高级版）',
    'subscriptions_account_invoices' => '发票',
    'subscriptions_account_invoices_download' => '下载',
    'subscriptions_account_invoices_subscription' => '订阅周期：:startDate 至 :endDate',
    'subscriptions_account_payment' => '哪个付费周期最适合您？',
    'subscriptions_account_confirm_payment' => '交易尚未完成，请您按此<a href=":url">确认您的付款</a>',
    'subscriptions_downgrade_title' => '将您的帐户降级为免费版',
    'subscriptions_downgrade_limitations' => '免费版的功能有限制。如果您需要降级，请您确保完成以下检查：',
    'subscriptions_downgrade_rule_users' => '您的帐户中必须只有1个用户',
    'subscriptions_downgrade_rule_users_constraint' => '您的帐户中当前有 <a href=":url">:count 个用户</a>。',
    'subscriptions_downgrade_rule_invitations' => 'You must not have any pending invitations',
    'subscriptions_downgrade_rule_invitations_constraint' => 'You currently have <a href=":url">1 pending invitation</a>.|You currently have <a href=":url">:count pending invitations</a>.',
    'subscriptions_downgrade_rule_contacts' => '您不能超过 :number 的活跃联系人',
    'subscriptions_downgrade_rule_contacts_constraint' => '当前有 <a href=":url">:count 位联系人</a>。',
    'subscriptions_downgrade_cta' => '降级',
    'subscriptions_downgrade_success' => '您已降级到免费版！',
    'subscriptions_downgrade_thanks' => 'Thanks so much for trying the paid plan. We keep adding new features on Monica all the time – so you might want to come back in the future to see if you might be interested in taking a subscription again.',
    'subscriptions_back' => '返回设置',
    'subscriptions_upgrade_title' => '升级您的帐户',
    'subscriptions_upgrade_choose' => '您选择了:plan',
    'subscriptions_upgrade_infos' => '请在下方输入您的付款信息：',
    'subscriptions_upgrade_name' => '持卡人姓名',
    'subscriptions_upgrade_zip' => '邮政编码',
    'subscriptions_upgrade_credit' => '信用卡或借记卡',
    'subscriptions_upgrade_submit' => '支付{amount}',
    'subscriptions_upgrade_charge' => 'We’ll charge your card :price now. The next charge will be on :date. If you ever change your mind, you can cancel at any time, no questions asked.',
    'subscriptions_upgrade_charge_handled' => '支付服务由第三方支付平台 <a href=":url">Stripe</a> 提供，我们无法接触到您的个人信息。',
    'subscriptions_upgrade_success' => '感谢您的订阅！',
    'subscriptions_upgrade_thanks' => '欢迎来到让世界变得更美好的社区。',

    'subscriptions_payment_confirm_title' => '确认您的 :amount 付款',
    'subscriptions_payment_confirm_information' => '需要额外信息来处理您的付款，请您补充下列付款信息。',
    'subscriptions_payment_succeeded_title' => '支付成功',
    'subscriptions_payment_succeeded' => '此交易已经完成。',
    'subscriptions_payment_cancelled_title' => '付款已取消',
    'subscriptions_payment_cancelled' => '您的付款已被取消。',
    'subscriptions_payment_error_name' => '请提供您的姓名',
    'subscriptions_payment_success' => '您的付款已成功',

    'subscriptions_pdf_title' => '您的:name每月订阅',
    'subscriptions_plan_choose' => '选择此计划',
    'subscriptions_plan_year_title' => '按年度支付',
    'subscriptions_plan_year_cost' => '$45 美元/年',
    'subscriptions_plan_year_cost_save' => '您可以节省 25%',
    'subscriptions_plan_year_bonus' => '一整年的安心',
    'subscriptions_plan_month_title' => '按月支付',
    'subscriptions_plan_month_cost' => '$5 美元/月',
    'subscriptions_plan_month_bonus' => '随时取消',
    'subscriptions_plan_include1' => '您将享有以下特权：',
    'subscriptions_plan_include2' => '无限添加联系人·无限的用户数量·电子邮件提醒·导入 vCard ·个性化的联系人信息',
    'subscriptions_plan_include3' => '收入的100% 用于此项目的开发。',
    'subscriptions_help_title' => '您可能还关心',
    'subscriptions_help_opensource_title' => '什么是开源项目？',
    'subscriptions_help_opensource_desc' => 'Monica is an open source project.  This means it is built by a community who wants to build a great tool for the greater good. Being open source means the code is publicly available on GitHub, and everyone can inspect it, modify it or enhance it. All the money we raise is dedicated to building better features, paying for more powerful servers, and paying other costs. Thanks for your help. We couldn’t do it without you.',
    'subscriptions_help_limits_title' => 'Is there a limit to the number of contacts we can have on the free plan?',
    'subscriptions_help_limits_plan' => '是的。免费版您能拥有:number位联系人。',
    'subscriptions_help_discounts_title' => '你们对非盈利机构和学生有优惠吗？',
    'subscriptions_help_discounts_desc' => '当然！Monica免费为学生，非盈利机构提供服务。您只需要提交一下材料给我们的 <a href=":support">支持人员</a>。',
    'subscriptions_help_change_title' => '如果我改变主意怎么办？',
    'subscriptions_help_change_desc' => 'You can cancel anytime, no questions asked, and all by yourself – no need to contact support. However, you will not be refunded for the current period.',

    'stripe_error_card' => '您的卡被拒，原因是：:message',
    'stripe_error_api_connection' => '与Stripe的通信失败，请稍候重试。',
    'stripe_error_rate_limit' => '与Stripe的通信次数过多，请稍候再试。',
    'stripe_error_invalid_request' => '无效的参数，请稍后再试。',
    'stripe_error_authentication' => 'Stripe授权失败',

    'import_title' => '在您的帐户中导入联系人',
    'import_cta' => '上载联系人',
    'import_stat' => '您目前为止导入了:number个文件。',
    'import_result_stat' => '上传了包含 :total_contacts 个联系人的 vCard (:total_imported imported, :total_skipped skipped)',
    'import_view_report' => '查看报告',
    'import_in_progress' => '导入正在进行中。在一分钟内重新加载页面。',
    'import_upload_title' => '从 vCard 文件导入联系人',
    'import_upload_rules_desc' => '但是, 我们有一些规则:',
    'import_upload_rule_format' => '我们支持 <code>vcard</code> 和 <code>vcf</code> 文件。',
    'import_upload_rule_vcard' => 'We support the vCard 3.0 format, which is the default format for macOS’s Contacts.app and Google Contacts.',
    'import_upload_rule_instructions' => 'Export instructions for <a href=":url1" target="_blank" rel="noopener noreferrer">macOS Contacts.app</a> and <a href=":url2" target="_blank" rel="noopener noreferrer">Google Contacts</a>.',
    'import_upload_rule_multiple' => 'If your contacts have multiple email addresses or phone numbers, only the first entry will be saved.',
    'import_upload_rule_limit' => 'Files are limited to 10 MB.',
    'import_upload_rule_time' => 'It might take up to a minute to upload the contacts and process them. Please be patient.',
    'import_upload_rule_cant_revert' => 'Please make sure data is accurate before uploading, as you can’t undo the upload.',
    'import_upload_form_file' => '你的 <code>.vcf</code> 或 <code>. vCard</code> 文件:',
    'import_upload_behaviour' => '导入偏好:',
    'import_upload_behaviour_add' => 'Add new contacts and skip existing',
    'import_upload_behaviour_replace' => '替换现有条目',
    'import_upload_behaviour_help' => 'Replacing will replace all data found in the vCard, but will keep existing contact fields.',
    'import_report_title' => '导入报表',
    'import_report_date' => '导入日期',
    'import_report_type' => '导入类型',
    'import_report_number_contacts' => '文件中的联系人数',
    'import_report_number_contacts_imported' => '导入的联系人数量',
    'import_report_number_contacts_skipped' => '跳过的联系人数',
    'import_report_status_imported' => '导入',
    'import_report_status_skipped' => '跳过',
    'import_vcard_parse_error' => '分析 vcard 项时出错',
    'import_vcard_contact_exist' => '联系人已存在',
    'import_vcard_contact_no_firstname' => 'No first name (mandatory)',
    'import_vcard_file_not_found' => '文件不存在',
    'import_vcard_unknown_entry' => '未知的联系人姓名',
    'import_vcard_file_no_entries' => '文件不包含联系人',
    'import_blank_title' => '您暂无导入的联系人。',
    'import_blank_question' => '是否立即导入联系人？',
    'import_blank_description' => '我们可以从 Google Contacts 或您的Contact manager那里导入您的 vCard 文件。',
    'import_blank_cta' => '导入 vCard',
    'import_need_subscription' => '您需要订阅才能导入联系人',

    'tags_list_title' => '标签',
    'tags_list_description' => '您可以通过设置来标记联系人。标记的工作方式类似于文件夹, 但可以向联系人添加多个标记。若要添加新标记, 请在联系人中添加即可。',
    'tags_list_contact_number' => ':count 个联系人',
    'tags_list_delete_success' => '标签已成功删除',
    'tags_list_delete_confirmation' => '确实要删除该标签吗？不会删除任何联系人, 只有标签。',
    'tags_blank_title' => '标签是对您的联系人进行分类的一种很好的方式。',
    'tags_blank_description' => 'Tags work like folders, but you can add more than one tag to a contact. Go to a contact and tag a friend, right below the name. Once a contact is tagged, come back here to manage all the tags in your account.',

    'api_title' => 'API 访问',
    'api_description' => 'API 可以用来从外部应用程序操纵Monica的数据, 例如移动应用程序。',
    'api_help' => '要使用 API，必须要有一个Token。 您可以创建个人访问 Token，也可以授权OAuth 客户端为您创建它。 查看 <a href=":url">API 文档</a>获取详情',
    'api_endpoint' => '此 Monica 实例的 API 终端是：',

    'api_personal_access_tokens' => '个人访问令牌',
    'api_pao_description' => '请确保将此token授予您信任的源-因为它们允许您访问所有数据。',
    'api_token_title' => '个人访问 Token',
    'api_token_create_new' => '创建密钥',
    'api_token_not_created' => '您没有已创建的访问密钥',
    'api_token_name' => 'Token 名称',
    'api_token_expire' => '过期于 {date}',
    'api_token_delete' => '删除',
    'api_token_create' => '创建密钥',
    'api_token_scopes' => '作用域',
    'api_token_help' => '这是您的个人访问密钥，我们只会展示一次，请妥善保管。您现在可以使用这个密钥进行API请求',

    'api_oauth_clients' => '您的 Oauth 客户端',
    'api_oauth_clients_desc' => '您可以注册自己的 OAuth 客户端。',
    'api_oauth_clients_desc2' => '使用此客户端ID请求一个新的Token，并将授权码转换为Token。请参阅 <a href="{url}">Laravel Passport文档</a> 获取更多信息。',
    'api_oauth_title' => 'OAuth 客户端',
    'api_oauth_create_new' => '创建新的客户端',
    'api_oauth_edit' => '编辑客户端',
    'api_oauth_not_created' => '您尚未创建Oauth客户端',
    'api_oauth_clientid' => '客户端 ID',
    'api_oauth_name' => '名称',
    'api_oauth_name_help' => '安全码',
    'api_oauth_secret' => '密钥',
    'api_oauth_create' => '创建客户端',
    'api_oauth_redirecturl' => '重定向URL',
    'api_oauth_redirecturl_help' => '应用程序的授权回调 URL。',

    'api_authorized_clients' => '授权客户端列表',
    'api_authorized_clients_desc' => '本节列出了您授权访问应用程序的所有客户端，您可以随时撤销此授权。',
    'api_authorized_clients_title' => '已授权的应用',
    'api_authorized_clients_none' => 'There are no authorized clients yet.',
    'api_authorized_clients_name' => '名称',
    'api_authorized_clients_scopes' => '作用域',

    'personalization_tab_title' => '个性化您的帐户',

    'personalization_title' => 'Here you will find different settings to configure your account. These features are intended for “power users” who want maximum control over Monica.',
    'personalization_contact_field_type_title' => '联系人字段类型',
    'personalization_contact_field_type_add' => '添加新字段类型',
    'personalization_contact_field_type_description' => 'You can configure all the different types of contact fields that you can associate to all your contacts. For example, if a new social network appears in the future, you will be able to add this new way of communicating with your contacts right here.',
    'personalization_contact_field_type_table_name' => '名称',
    'personalization_contact_field_type_table_protocol' => '协议',
    'personalization_contact_field_type_table_actions' => '行动',
    'personalization_contact_field_type_modal_title' => '添加新的联系人字段类型',
    'personalization_contact_field_type_modal_edit_title' => '编辑现有联系人字段类型',
    'personalization_contact_field_type_modal_delete_title' => '删除现有联系人字段类型',
    'personalization_contact_field_type_modal_delete_description' => 'Are you sure you want to delete this contact field type? Deleting this type of contact field will delete ALL the data with this type for all of your contacts.',
    'personalization_contact_field_type_modal_name' => '名称',
    'personalization_contact_field_type_modal_protocol' => '协议 (可选)',
    'personalization_contact_field_type_modal_protocol_help' => '每个新的联系人字段类型都可以选定。如果设置了协议, 我们将使用它来触发设置的操作。',
    'personalization_contact_field_type_modal_icon' => '图标 (可选)',
    'personalization_contact_field_type_modal_icon_help' => '您可以将图标与此联系人字段类型关联。您需要添加对Font Awesome图标的引用。',
    'personalization_contact_field_type_delete_success' => 'The contact field type has been successfully deleted.',
    'personalization_contact_field_type_add_success' => '已成功添加联系人字段类型。',
    'personalization_contact_field_type_edit_success' => '联系人字段类型已成功更新。',

    'personalization_genders_title' => '性别类型',
    'personalization_genders_add' => '添加新的性别类型',
    'personalization_genders_desc' => '你可以根据需要定义尽可能多的性别。您的帐户中至少需要一种性别类型。',
    'personalization_genders_modal_add' => '添加性别类型',
    'personalization_genders_modal_edit' => '更新性别类型',
    'personalization_genders_modal_name' => '名称',
    'personalization_genders_modal_name_help' => '在联系人页面显示性别的名称',
    'personalization_genders_modal_sex' => '性别',
    'personalization_genders_modal_sex_help' => '在导入/导出 VCard 时用于定义关系',
    'personalization_genders_modal_default' => '选择新联系人的默认性别',
    'personalization_genders_modal_delete' => '删除性别类型',
    'personalization_genders_modal_delete_desc' => 'Are you sure you want to delete the gender “{name}”?',
    'personalization_genders_modal_delete_question' => 'You currently have {count} contact with this gender. If you delete this gender, what gender should this contact have?|You currently have {count} contacts with this gender. If you delete this gender, what gender should these contacts have?',
    'personalization_genders_modal_delete_question_default' => 'This gender is the default one. If you delete this gender, which one will be the new default?',
    'personalization_genders_modal_error' => 'Please choose a gender from the list.',
    'personalization_genders_list_contact_number' => '{count} 个联系人|{count} 个联系人',
    'personalization_genders_table_name' => '名称',
    'personalization_genders_table_sex' => '性别',
    'personalization_genders_table_default' => '默认',
    'personalization_genders_default' => '默认性别',
    'personalization_genders_make_default' => '更改默认性别',
    'personalization_genders_select_default' => '选择默认性别',
    'personalization_genders_m' => '男性',
    'personalization_genders_f' => '女性',
    'personalization_genders_o' => '其他',
    'personalization_genders_u' => '未知',
    'personalization_genders_n' => '无或不适用',

    'personalization_reminder_rule_save' => '更改已保存',
    'personalization_reminder_rule_title' => '提醒规则',
    'personalization_reminder_rule_line' => '提前 {count} 天|提前 {count} 天',
    'personalization_reminder_rule_desc' => 'For every reminder that you set, Monica can send you an email a number of days before the event happens.  You can adjust these notification settings here. These notifications only apply to monthly and yearly reminders.',

    'personalization_module_save' => '更改已被保存',
    'personalization_module_title' => '功能',
    'personalization_module_desc' => 'You may not need all of Monica’s features. Below you can toggle specific features that are used on a contact sheet. This change will affect ALL your contacts. Turning off a feature does not delete any data, it simply hides the feature.',

    'personalisation_paid_upgrade' => '这是一个高级功能，需要付费订阅才能激活。通过访问 <a href=":url">设置 > 订阅</a> 来升级您的帐户。',
    'personalisation_paid_upgrade_vue' => '这是一个高级功能，需要付费订阅才能激活。通过访问 <a href="{url}">设置 > 订阅</a> 来升级您的帐户。',

    'reminder_time_to_send' => 'Time of the day reminders will be sent',
    'reminder_time_to_send_help' => 'Your next reminder is scheduled to be sent on <span title="{dateTimeUtc}" class="reminder-info">{dateTime}</span>.',

    'personalization_activity_type_category_title' => '活动分类',
    'personalization_activity_type_category_add' => '增加一个活动分类',
    'personalization_activity_type_category_table_name' => '名称',
    'personalization_activity_type_category_description' => 'An activity with one of your contacts can have a type and a category type. Your account comes with a set of predefined category types by default, but you can customize these here.',
    'personalization_activity_type_category_table_actions' => '行动',
    'personalization_activity_type_category_modal_add' => '增加活动分类',
    'personalization_activity_type_category_modal_edit' => '编辑活动分类',
    'personalization_activity_type_category_modal_question' => 'What should we name this new category?',
    'personalization_activity_type_add_button' => '增加一个活动',
    'personalization_activity_type_modal_add' => '增加一个活动',
    'personalization_activity_type_modal_question' => 'What should we name this new activity type?',
    'personalization_activity_type_modal_edit' => '编辑活动',
    'personalization_activity_type_category_modal_delete' => '删除活动分类',
    'personalization_activity_type_category_modal_delete_desc' => 'Are you sure you want to delete this category? Deleting it will delete all associated activity types. Activities that belong to this category will not be affected by this deletion.',
    'personalization_activity_type_modal_delete' => '删除活动',
    'personalization_activity_type_modal_delete_desc' => '您真的要删除这个活动吗？',
    'personalization_activity_type_modal_delete_error' => '我们无法找到这个活动',
    'personalization_activity_type_category_modal_delete_error' => '我们无法找到这个活动分类',

    'personalization_life_event_category_title' => 'Life event categories',
    'personalization_live_event_category_table_name' => 'Name',
    'personalization_life_event_category_description' => 'A life event can have a type and a category. Your account comes with a set of predefined categories and types by default, but you can customize life event types here.',
    'personalization_live_event_category_table_actions' => 'Actions',
    'personalization_life_event_type_add_button' => 'Add a new life event type',
    'personalization_life_event_type_modal_add' => 'Add a new life event type',
    'personalization_life_event_type_modal_question' => 'What should we name this new life event type?',
    'personalization_life_event_type_modal_edit' => 'Edit a life event type',
    'personalization_life_event_type_modal_delete' => 'Delete a life event type',
    'personalization_life_event_type_modal_delete_desc' => 'Are you sure you want to delete this life event type? Life events that belong to this type will be deleted by performing this action.',
    'personalization_life_event_type_modal_delete_error' => 'We can’t find this life event type.',

    'personalization_life_event_category_work_education' => '工作与教育',
    'personalization_life_event_category_family_relationships' => '家庭与恋爱',
    'personalization_life_event_category_home_living' => '家与生活',
    'personalization_life_event_category_travel_experiences' => '旅行与经历',
    'personalization_life_event_category_health_wellness' => '健康与饮食',

    'personalization_life_event_type_new_job' => '新工作',
    'personalization_life_event_type_retirement' => '退休',
    'personalization_life_event_type_new_school' => '新学校',
    'personalization_life_event_type_study_abroad' => '留学',
    'personalization_life_event_type_volunteer_work' => '志愿者工作',
    'personalization_life_event_type_published_book_or_paper' => '出版一本书或一篇论文',
    'personalization_life_event_type_military_service' => '兵役',
    'personalization_life_event_type_first_met' => '第一次见面',
    'personalization_life_event_type_new_relationship' => '新关系',
    'personalization_life_event_type_engagement' => '订婚',
    'personalization_life_event_type_marriage' => '婚姻',
    'personalization_life_event_type_anniversary' => '周年纪念日',
    'personalization_life_event_type_expecting_a_baby' => '想要孩子',
    'personalization_life_event_type_new_child' => '新的孩子',
    'personalization_life_event_type_new_family_member' => '新的家庭成员',
    'personalization_life_event_type_new_pet' => '新宠物',
    'personalization_life_event_type_end_of_relationship' => '结束了一段关系',
    'personalization_life_event_type_loss_of_a_loved_one' => '失去心爱的人',
    'personalization_life_event_type_moved' => '搬家了',
    'personalization_life_event_type_bought_a_home' => '买了新房子',
    'personalization_life_event_type_home_improvement' => '装修',
    'personalization_life_event_type_holidays' => '假日',
    'personalization_life_event_type_new_vehicle' => '新车',
    'personalization_life_event_type_new_roommate' => '新室友',
    'personalization_life_event_type_overcame_an_illness' => '熬过了疾病',
    'personalization_life_event_type_quit_a_habit' => '戒掉一个习惯',
    'personalization_life_event_type_new_eating_habits' => '新的饮食习惯',
    'personalization_life_event_type_weight_loss' => '减肥',
    'personalization_life_event_type_wear_glass_or_contact' => 'Started wearing glasses or contacts',
    'personalization_life_event_type_broken_bone' => 'Broke a bone',
    'personalization_life_event_type_removed_braces' => 'Had braces removed',
    'personalization_life_event_type_surgery' => 'Had surgery',
    'personalization_life_event_type_dentist' => 'Had dental treatment',
    'personalization_life_event_type_new_sport' => 'Started playing a new sport',
    'personalization_life_event_type_new_hobby' => 'Took up a new hobby',
    'personalization_life_event_type_new_instrument' => 'Started learning a new instrument',
    'personalization_life_event_type_new_language' => 'Started learning a new language',
    'personalization_life_event_type_tattoo_or_piercing' => '纹身或耳洞',
    'personalization_life_event_type_new_license' => '新驾照',
    'personalization_life_event_type_travel' => '旅行',
    'personalization_life_event_type_achievement_or_award' => '成就或奖项',
    'personalization_life_event_type_changed_beliefs' => '改变信仰',
    'personalization_life_event_type_first_word' => '第一次发言',
    'personalization_life_event_type_first_kiss' => '初吻',

    'storage_title' => '存储空间',
    'storage_account_info' => 'Your account limit is :accountLimit MB. Your current usage is :currentAccountSize MB (about :percentUsage%).',
    'storage_upgrade_notice' => '升级您的帐户, 以便上传文档和照片。',
    'storage_description' => '在这里, 您可以看到上传的有关您的联系人的所有文档和照片。',

    'dav_title' => 'WebDAV',
    'dav_description' => '在这里, 您可以找到所有设置, 以便为 Carddav 和 CalDAV 导出使用 webdav 资源。',
    'dav_copy_help' => '复制到您的剪贴板',
    'dav_clipboard_copied' => '值已复制到剪贴板',
    'dav_url_base' => '所有CardDAV和CalDAV资源的基本 url:',
    'dav_connect_help' => '您可以在手机或计算机上使用此基本 url 连接您的联系人和/或日历。',
    'dav_connect_help2' => 'Use your login (email) and create an API token as the password to authenticate.',
    'dav_url_carddav' => '用于联系资源的CardDAV',
    'dav_url_caldav_birthdays' => '用于生日资源的 caldav url:',
    'dav_url_caldav_tasks' => '用于任务资源的 caldav url:',
    'dav_title_carddav' => 'CardDAV',
    'dav_title_caldav' => 'CalDAV',
    'dav_carddav_export' => '导出一个文件中的所有联系人',
    'dav_caldav_birthdays_export' => '在一个文件中导出所有生日',
    'dav_caldav_tasks_export' => '导出一个文件中的所有任务',

    'archive_title' => 'Archive all of the contacts in your account',
    'archive_desc' => 'This will archive all of the contacts in your account.',
    'archive_cta' => 'Archive all of your contacts',

    'logs_title' => 'Everything that has happened to this account',
    'logs_actor' => 'Actor',
    'logs_timestamp' => 'Timestamp',
    'logs_description' => 'Description',
    'logs_subject' => 'Subject',
    'logs_size' => 'Size (Kb)',
    'logs_object' => 'Object',
];
