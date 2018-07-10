<?php

return [
    'update' => 'עדכון',
    'save' => 'שמירה',
    'add' => 'הוספה',
    'cancel' => 'ביטול',
    'delete' => 'מחיקה',
    'edit' => 'עריכה',
    'upload' => 'העלאה',
    'close' => 'סגירה',
    'create' => 'יצירה',
    'remove' => 'הסרה',
    'revoke' => 'שלילה',
    'done' => 'סיום',
    'verify' => 'אימות',
    'for' => 'for',
    'new' => 'new',
    'unknown' => 'לא ידוע לי',
    'load_more' => 'לטעון עוד',
    'loading' => 'בטעינה…',
    'with' => 'עם',
    'days' => 'day|days',

    'application_title' => 'מוניקה - ניהול יחסים בינאישיים',
    'application_description' => 'מוניקה היא כלי לניהול הקשרים החברתיים שלך עם אהוביך, חבריך ומשפחתך.',
    'application_og_title' => 'חיזוק הקשר עם אהוביך. מערכת ניהול מקוונת בחינם לקשרים עם חברים ומשפחה.',

    'markdown_description' => 'רוצה להוסיף קצת עניין לטקסט שלך? במערכת זו קיימת תמיכה ב־Markdown כדי להוסיף הדגשה, הטיה, רשימות ועוד.',
    'markdown_link' => 'קריאת התיעוד',

    'header_settings_link' => 'הגדרות',
    'header_logout_link' => 'יציאה',
    'header_changelog_link' => 'שינויים במוצר',

    'main_nav_cta' => 'הוספת אנשים',
    'main_nav_dashboard' => 'לוח מחוונים',
    'main_nav_family' => 'אנשי קשר',
    'main_nav_journal' => 'יומן',
    'main_nav_activities' => 'פעילויות',
    'main_nav_tasks' => 'משימות',

    'footer_remarks' => 'יש הערות?',
    'footer_send_email' => 'ניתן לשלוח לי דוא״ל',
    'footer_privacy' => 'מדיניות פרטיות',
    'footer_release' => 'הערות הוצאה לאור',
    'footer_newsletter' => 'רשימת דיוור',
    'footer_source_code' => 'תרומה',
    'footer_version' => 'גרסה: :version',
    'footer_new_version' => 'גרסה חדשה זמינה',

    'footer_modal_version_whats_new' => 'מה חדש',
    'footer_modal_version_release_away' => 'גרסה זו יצאה לאור גרסה אחת לפני הגרסה העדכנית הנוכחית. עליך לעדכן את העותק שלך.|גרסה זו יצאה לאור :number גרסאות לפני הגרסה העדכנית הנוכחית. עליך לעדכן את העותק שלך.',

    'breadcrumb_dashboard' => 'לוח מחוונים',
    'breadcrumb_list_contacts' => 'רשימת אנשים',
    'breadcrumb_journal' => 'יומן',
    'breadcrumb_settings' => 'הגדרות',
    'breadcrumb_settings_export' => 'יצוא',
    'breadcrumb_settings_users' => 'משתמשים',
    'breadcrumb_settings_users_add' => 'הוספת משתמש',
    'breadcrumb_settings_subscriptions' => 'הרשמה',
    'breadcrumb_settings_import' => 'יבוא',
    'breadcrumb_settings_import_report' => 'דוח יבוא',
    'breadcrumb_settings_import_upload' => 'העלאה',
    'breadcrumb_settings_tags' => 'תגיות',
    'breadcrumb_add_significant_other' => 'הוספת קשר זוגי',
    'breadcrumb_edit_significant_other' => 'עריכת קשר זוגי',
    'breadcrumb_add_note' => 'הוספת הערה',
    'breadcrumb_edit_note' => 'עריכת הערה',
    'breadcrumb_api' => 'API',
    'breadcrumb_edit_introductions' => 'איך הכרתם',
    'breadcrumb_settings_personalization' => 'התאמה אישית',
    'breadcrumb_settings_security' => 'אבטחה',
    'breadcrumb_settings_security_2fa' => 'אימות דו־שלבי',

    'gender_male' => 'גבר',
    'gender_female' => 'אישה',
    'gender_none' => 'שמור במערכת',

    'error_title' => 'אופס! משהו השתבש.',
    'error_unauthorized' => 'אין לך את ההרשאה לערוך את המשאב הזה.',
    'error_save' => 'אירעה שגיאה בעת שמירת הנתונים.',

    'default_save_success' => 'הנתונים נשמרו.',

    'compliance_title' => 'סליחה על ההפרעה.',
    'compliance_desc' => 'ערכנו את <a href=":urlterm" hreflang=":hreflang">תנאי השימוש</a> ואת <a href=":url" hreflang=":hreflang">מדיניות הפרטיות</a> שלנו. מכוח החוק עלינו לבקש ממך לעיין בשינויים ולאשר את הסכמתך להם כדי להמשיך לאפשר לך להשתמש בחשבונך.',
    'compliance_desc_end' => 'אנו לא משתמשים בנתונים או בחשבון שלך לאף מטרה זדונית וגם לא נעשה זאת בעתיד.',
    'compliance_terms' => 'קבלת התנאים ומדיניות הפרטיות החדשים',

    // Relationship types
    // Yes, each relationship type has 8 strings associated with it.
    // This is because we need to indicate the name of the relationship type,
    // and also the name of the opposite side of this relationship (father/son),
    // and then, the feminine version of the string. Finally, in some sentences
    // in the UI, we need to include the name of the person we add the relationship
    // to.
    'relationship_type_group_love' => 'קשרים רומנטיים',
    'relationship_type_group_family' => 'קשרים משפחתיים',
    'relationship_type_group_friend' => 'קשרים חברתיים',
    'relationship_type_group_work' => 'קשרי עבודה',
    'relationship_type_group_other' => 'סוגי קשר אחרים',

    'relationship_type_partner' => 'בן זוג',
    'relationship_type_partner_female' => 'בת זוג',
    'relationship_type_partner_with_name' => 'בן הזוג של :name',
    'relationship_type_partner_female_with_name' => 'בת הזוג של :name',

    'relationship_type_spouse' => 'בעל',
    'relationship_type_spouse_female' => 'אישה',
    'relationship_type_spouse_with_name' => 'בעלה של :name',
    'relationship_type_spouse_female_with_name' => 'אשתו של :name',

    'relationship_type_date' => 'יוצא קבוע',
    'relationship_type_date_female' => 'יוצאת קבוע',
    'relationship_type_date_with_name' => 'יוצא קבוע עם :name',
    'relationship_type_date_female_with_name' => 'יוצאת קבוע עם :name',

    'relationship_type_lover' => 'מאהב',
    'relationship_type_lover_female' => 'מאהבת',
    'relationship_type_lover_with_name' => 'מאהב של :name',
    'relationship_type_lover_female_with_name' => 'מאהבת של :name',

    'relationship_type_inlovewith' => 'מאוהב',
    'relationship_type_inlovewith_female' => 'מאוהבת',
    'relationship_type_inlovewith_with_name' => 'מושא אהבתו של :name',
    'relationship_type_inlovewith_female_with_name' => 'מושא אהבתה של :name',

    'relationship_type_lovedby' => 'נאהב על ידי',
    'relationship_type_lovedby_female' => 'נאהבת על ידי',
    'relationship_type_lovedby_with_name' => 'מאהב סודי של :name',
    'relationship_type_lovedby_female_with_name' => 'מאהבת סודית של :name',

    'relationship_type_ex' => 'חבר לשעבר',
    'relationship_type_ex_female' => 'חברה לשעבר',
    'relationship_type_ex_with_name' => 'חבר לשעבר של :name',
    'relationship_type_ex_female_with_name' => 'חברה לשעבר של :name',

    'relationship_type_parent' => 'אבא',
    'relationship_type_parent_female' => 'אימא',
    'relationship_type_parent_with_name' => 'אבא של :name',
    'relationship_type_parent_female_with_name' => 'אימא של :name',

    'relationship_type_child' => 'בן',
    'relationship_type_child_female' => 'בת',
    'relationship_type_child_with_name' => 'בן של :name',
    'relationship_type_child_female_with_name' => 'בת של :name',

    'relationship_type_sibling' => 'אח',
    'relationship_type_sibling_female' => 'אחות',
    'relationship_type_sibling_with_name' => 'אח של :name',
    'relationship_type_sibling_female_with_name' => 'אחות של :name',

    'relationship_type_grandparent' => 'סבא',
    'relationship_type_grandparent_female' => 'סבתא',
    'relationship_type_grandparent_with_name' => 'סבא של :name',
    'relationship_type_grandparent_female_with_name' => 'סבתא של :name',

    'relationship_type_grandchild' => 'נכד',
    'relationship_type_grandchild_female' => 'נכדה',
    'relationship_type_grandchild_with_name' => 'נכד של :name',
    'relationship_type_grandchild_female_with_name' => 'נכדה של :name',

    'relationship_type_uncle' => 'דוד',
    'relationship_type_uncle_female' => 'דודה',
    'relationship_type_uncle_with_name' => 'דוד של :name',
    'relationship_type_uncle_female_with_name' => 'דודה של :name',

    'relationship_type_nephew' => 'אחיין',
    'relationship_type_nephew_female' => 'אחיינית',
    'relationship_type_nephew_with_name' => 'אחיין של :name',
    'relationship_type_nephew_female_with_name' => 'אחיינית של :name',

    'relationship_type_cousin' => 'בן דוד',
    'relationship_type_cousin_female' => 'בת דודה',
    'relationship_type_cousin_with_name' => 'בן דוד של :name',
    'relationship_type_cousin_female_with_name' => 'בת דודה של :name',

    'relationship_type_godfather' => 'סנדק',
    'relationship_type_godfather_female' => 'סנדקית',
    'relationship_type_godfather_with_name' => 'הסנדק של :name',
    'relationship_type_godfather_female_with_name' => 'הסנדקית של :name',

    'relationship_type_godson' => 'בן סנדקות',
    'relationship_type_godson_female' => 'בת סנדקות',
    'relationship_type_godson_with_name' => 'בן הסנדקות של :name',
    'relationship_type_godson_female_with_name' => 'בת הסנדקות של :name',

    'relationship_type_friend' => 'חבר',
    'relationship_type_friend_female' => 'חברה',
    'relationship_type_friend_with_name' => 'חבר של :name',
    'relationship_type_friend_female_with_name' => 'חברה של :name',

    'relationship_type_bestfriend' => 'החבר הטוב ביותר',
    'relationship_type_bestfriend_female' => 'החברה הטובה ביותר',
    'relationship_type_bestfriend_with_name' => 'החבר הכי טוב של :name',
    'relationship_type_bestfriend_female_with_name' => 'החברה הכי טובה של :name',

    'relationship_type_colleague' => 'עמית לעבודה',
    'relationship_type_colleague_female' => 'עמיתה לעבודה',
    'relationship_type_colleague_with_name' => 'עמית לעבודה של :name',
    'relationship_type_colleague_female_with_name' => 'עמיתה לעבודה של :name',

    'relationship_type_boss' => 'מנהל',
    'relationship_type_boss_female' => 'מנהלת',
    'relationship_type_boss_with_name' => 'מנהל של :name',
    'relationship_type_boss_female_with_name' => 'מנהלת של :name',

    'relationship_type_subordinate' => 'כפוף',
    'relationship_type_subordinate_female' => 'כפופה',
    'relationship_type_subordinate_with_name' => 'כפוף ל:name',
    'relationship_type_subordinate_female_with_name' => 'כפופה ל:name',

    'relationship_type_mentor' => 'חונך',
    'relationship_type_mentor_female' => 'חונכת',
    'relationship_type_mentor_with_name' => 'חונך של :name',
    'relationship_type_mentor_female_with_name' => 'חונך של :name',

    'relationship_type_protege' => 'חניך',
    'relationship_type_protege_female' => 'חניכה',
    'relationship_type_protege_with_name' => 'חניך של :name',
    'relationship_type_protege_female_with_name' => 'חניכה של :name',

    'relationship_type_ex_husband' => 'גרוש',
    'relationship_type_ex_husband_female' => 'גרושה',
    'relationship_type_ex_husband_with_name' => 'הגרוש של :name',
    'relationship_type_ex_husband_female_with_name' => 'הגרושה של :name',
];
