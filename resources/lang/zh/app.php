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
    'create' => '创建',
    'remove' => '删除',
    'revoke' => '撤销',
    'done' => '完成',
    'verify' => '验证',
    'for' => '为',
    'new' => '新',
    'unknown' => '我不知道',
    'load_more' => '载入更多',
    'loading' => '正在加载...',
    'with' => '与',
    'days' => '天|天',

    'application_title' => 'Monica – 您的私人社交关系管家',
    'application_description' => 'Monica是用来收集并管理您与亲朋好友之间的关系的得力助手。',
    'application_og_title' => '促进你们之间的感情。一个免费且开源的面向亲朋好友的CRM工具',

    'markdown_description' => '想用一种美观的方式格式化文本吗？我们以Markdown语法支持粗体、斜体、列表等样式。',
    'markdown_link' => '阅读文档',

    'header_settings_link' => '设置',
    'header_logout_link' => '注销',
    'header_changelog_link' => '更新日志',

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
    'error_save' => '当储存数据时出现了一个错误',

    'default_save_success' => '数据已被保存',

    'compliance_title' => '抱歉，打扰您一下',
    'compliance_desc' => '我们更新了<a href=":urlterm" hreflang=":hreflang">用户协议</a> 以及 <a href=":url" hreflang=":hreflang">隐私政策</a>，您需要阅读并同意才能继续使用您的账号。',
    'compliance_desc_end' => '我们会保护您的隐私安全。',
    'compliance_terms' => '我已阅读并同意',

    // Relationship types
    // Yes, each relationship type has 8 strings associated with it.
    // This is because we need to indicate the name of the relationship type,
    // and also the name of the opposite side of this relationship (father/son),
    // and then, the feminine version of the string. Finally, in some sentences
    // in the UI, we need to include the name of the person we add the relationship
    // to.
    'relationship_type_group_love' => '恋爱关系',
    'relationship_type_group_family' => '家庭关系',
    'relationship_type_group_friend' => '朋友关系',
    'relationship_type_group_work' => '工作关系',
    'relationship_type_group_other' => '其他关系',

    'relationship_type_partner' => '搭档',
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

    'relationship_type_ex_husband' => 'ex husband',
    'relationship_type_ex_husband_female' => 'ex wife',
    'relationship_type_ex_husband_with_name' => ':name’s ex husband',
    'relationship_type_ex_husband_female_with_name' => ':name’s ex wife',
];
