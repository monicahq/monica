<?php

return [
    'update' => '更新',
    'save' => '保存',
    'add' => '添加',
    'cancel' => '取消',
    'delete' => '删除',
    'edit' => '编辑',
    'upload' => '上传',
    'close' => '关闭',
    'create' => 'Create',
    'remove' => '删除',
    'revoke' => 'Revoke',
    'done' => '完成',
    'verify' => '验证',
    'for' => '为',
    'new' => 'new',
    'unknown' => '我不知道',
    'load_more' => '载入更多',
    'loading' => '正在加载...',
    'with' => '与',
    'days' => 'day|days',

    'application_title' => 'Monica – personal relationship manager',
    'application_description' => 'Monica is a tool to manage your interactions with your loved ones, friends and family.',
    'application_og_title' => 'Have better relations with your loved ones. Free Online CRM for friends and family.',

    'markdown_description' => '想用一种美观的方式格式化文本吗？我们以Markdown语法支持粗体、斜体、列表等样式。',
    'markdown_link' => '阅读文档',

    'header_settings_link' => '设置',
    'header_logout_link' => '注销',
    'header_changelog_link' => 'Product changes',

    'main_nav_cta' => '添加人员',
    'main_nav_dashboard' => '仪表盘',
    'main_nav_family' => '联系人',
    'main_nav_journal' => '记录',
    'main_nav_activities' => '活动',
    'main_nav_tasks' => '任务',

    'footer_remarks' => '有什么意见吗？',
    'footer_send_email' => '给我发电子邮件',
    'footer_privacy' => '隐私条款',
    'footer_release' => '版本说明',
    'footer_newsletter' => '新闻简报',
    'footer_source_code' => '捐助',
    'footer_version' => '版本::version',
    'footer_new_version' => '有新版本可用！',

    'footer_modal_version_whats_new' => '新增内容',
    'footer_modal_version_release_away' => '您有一个最新发布版本可更新。您应该更新实例. |您已经有:number个版本没有更新，应该更新了。',

    'breadcrumb_dashboard' => '仪表盘',
    'breadcrumb_list_contacts' => '人名单',
    'breadcrumb_journal' => '记录',
    'breadcrumb_settings' => '设置',
    'breadcrumb_settings_export' => '导出',
    'breadcrumb_settings_users' => '用户',
    'breadcrumb_settings_users_add' => '添加用户',
    'breadcrumb_settings_subscriptions' => '订阅',
    'breadcrumb_settings_import' => '导入',
    'breadcrumb_settings_import_report' => '导入报表',
    'breadcrumb_settings_import_upload' => '上传',
    'breadcrumb_settings_tags' => '标签',
    'breadcrumb_add_significant_other' => '添加其他重要',
    'breadcrumb_edit_significant_other' => '编辑其他重要',
    'breadcrumb_add_note' => '添加注释',
    'breadcrumb_edit_note' => '编辑注释',
    'breadcrumb_api' => 'API',
    'breadcrumb_edit_introductions' => '你是怎么知道的',
    'breadcrumb_settings_personalization' => '个性化',
    'breadcrumb_settings_security' => '安全',
    'breadcrumb_settings_security_2fa' => '双重验证',

    'gender_male' => '男',
    'gender_female' => '女',
    'gender_none' => '保密',

    'error_title' => '糟糕! 出错了。',
    'error_unauthorized' => '你没有权限编辑此页',
    'error_save' => 'We had an error trying to save the data.',

    'default_save_success' => 'The data has been saved.',

    // Relationship types
    // Yes, each relationship type has 8 strings associated with it.
    // This is because we need to indicate the name of the relationship type,
    // and also the name of the opposite side of this relationship (father/son),
    // and then, the feminine version of the string. Finally, in some sentences
    // in the UI, we need to include the name of the person we add the relationship
    // to.
    'relationship_type_group_love' => 'Love relationships',
    'relationship_type_group_family' => 'Family relationships',
    'relationship_type_group_friend' => 'Friend relationships',
    'relationship_type_group_work' => 'Work relationships',
    'relationship_type_group_other' => 'Other kind of relationships',

    'relationship_type_partner' => 'significant other',
    'relationship_type_partner_female' => 'significant other',
    'relationship_type_partner_with_name' => ':name’s significant other',
    'relationship_type_partner_female_with_name' => ':name’s significant other',

    'relationship_type_spouse' => 'spouse',
    'relationship_type_spouse_female' => 'spouse',
    'relationship_type_spouse_with_name' => ':name’s spouse',
    'relationship_type_spouse_female_with_name' => ':name’s spouse',

    'relationship_type_date' => 'date',
    'relationship_type_date_female' => 'date',
    'relationship_type_date_with_name' => ':name’s date',
    'relationship_type_date_female_with_name' => ':name’s date',

    'relationship_type_lover' => 'lover',
    'relationship_type_lover_female' => 'lover',
    'relationship_type_lover_with_name' => ':name’s lover',
    'relationship_type_lover_female_with_name' => ':name’s lover',

    'relationship_type_inlovewith' => 'in love with',
    'relationship_type_inlovewith_female' => 'in love with',
    'relationship_type_inlovewith_with_name' => 'someone :name is in love with',
    'relationship_type_inlovewith_female_with_name' => 'someone :name is in love with',

    'relationship_type_lovedby' => 'loved by',
    'relationship_type_lovedby_female' => 'loved by',
    'relationship_type_lovedby_with_name' => ':name’s secret lover',
    'relationship_type_lovedby_female_with_name' => ':name’s secret lover',

    'relationship_type_ex' => 'ex-boyfriend',
    'relationship_type_ex_female' => 'ex-girlfriend',
    'relationship_type_ex_with_name' => ':name’s ex-boyfriend',
    'relationship_type_ex_female_with_name' => ':name’s ex-girlfriend',

    'relationship_type_parent' => 'father',
    'relationship_type_parent_female' => 'mother',
    'relationship_type_parent_with_name' => ':name’s father',
    'relationship_type_parent_female_with_name' => ':name’s mother',

    'relationship_type_child' => 'son',
    'relationship_type_child_female' => 'daughter',
    'relationship_type_child_with_name' => ':name’s son',
    'relationship_type_child_female_with_name' => ':name’s daughter',

    'relationship_type_sibling' => 'brother',
    'relationship_type_sibling_female' => 'sister',
    'relationship_type_sibling_with_name' => ':name’s brother',
    'relationship_type_sibling_female_with_name' => ':name’s sister',

    'relationship_type_grandparent' => 'grand parent',
    'relationship_type_grandparent_female' => 'grand parent',
    'relationship_type_grandparent_with_name' => ':name’s grand parent',
    'relationship_type_grandparent_female_with_name' => ':name’s grand parent',

    'relationship_type_grandchild' => 'grand child',
    'relationship_type_grandchild_female' => 'grand child',
    'relationship_type_grandchild_with_name' => ':name’s grand child',
    'relationship_type_grandchild_female_with_name' => ':name’s grand child',

    'relationship_type_uncle' => 'uncle',
    'relationship_type_uncle_female' => 'aunt',
    'relationship_type_uncle_with_name' => ':name’s uncle',
    'relationship_type_uncle_female_with_name' => ':name’s aunt',

    'relationship_type_nephew' => 'nephew',
    'relationship_type_nephew_female' => 'niece',
    'relationship_type_nephew_with_name' => ':name’s nephew',
    'relationship_type_nephew_female_with_name' => ':name’s niece',

    'relationship_type_cousin' => 'cousin',
    'relationship_type_cousin_female' => 'cousin',
    'relationship_type_cousin_with_name' => ':name’s cousin',
    'relationship_type_cousin_female_with_name' => ':name’s cousin',

    'relationship_type_godfather' => 'godfather',
    'relationship_type_godfather_female' => 'godmother',
    'relationship_type_godfather_with_name' => ':name’s godfather',
    'relationship_type_godfather_female_with_name' => ':name’s godmother',

    'relationship_type_godson' => 'godson',
    'relationship_type_godson_female' => 'goddaughter',
    'relationship_type_godson_with_name' => ':name’s godson',
    'relationship_type_godson_female_with_name' => ':name’s goddaughter',

    'relationship_type_friend' => 'friend',
    'relationship_type_friend_female' => 'friend',
    'relationship_type_friend_with_name' => ':name’s friend',
    'relationship_type_friend_female_with_name' => ':name’s friend',

    'relationship_type_bestfriend' => 'best friend',
    'relationship_type_bestfriend_female' => 'best friend',
    'relationship_type_bestfriend_with_name' => ':name’s best friend',
    'relationship_type_bestfriend_female_with_name' => ':name’s best friend',

    'relationship_type_colleague' => 'colleague',
    'relationship_type_colleague_female' => 'colleague',
    'relationship_type_colleague_with_name' => ':name’s colleague',
    'relationship_type_colleague_female_with_name' => ':name’s colleague',

    'relationship_type_boss' => 'boss',
    'relationship_type_boss_female' => 'boss',
    'relationship_type_boss_with_name' => ':name’s boss',
    'relationship_type_boss_female_with_name' => ':name’s boss',

    'relationship_type_subordinate' => 'subordinate',
    'relationship_type_subordinate_female' => 'subordinate',
    'relationship_type_subordinate_with_name' => ':name’s subordinate',
    'relationship_type_subordinate_female_with_name' => ':name’s subordinate',

    'relationship_type_mentor' => 'mentor',
    'relationship_type_mentor_female' => 'mentor',
    'relationship_type_mentor_with_name' => ':name’s mentor',
    'relationship_type_mentor_female_with_name' => ':name’s mentor',

    'relationship_type_protege' => 'protege',
    'relationship_type_protege_female' => 'protege',
    'relationship_type_protege_with_name' => ':name’s protege',
    'relationship_type_protege_female_with_name' => ':name’s protege',
];
