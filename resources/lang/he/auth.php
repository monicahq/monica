<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'פרטי הזהות האלה אינם תואמים את רישומינו.',
    'throttle' => 'בוצעו יותר מדי ניסיונות כניסה כושלים. נא לנסות שוב בעוד :seconds שניות.',
    'not_authorized' => 'אין לך הרשאה להריץ את הפעולה הזאת',
    'signup_disabled' => 'ההרשמה מושבתת כרגע',
    'back_homepage' => 'חזרה לדף הבית',
    'mfa_auth_otp' => 'אימות עם ההתקן שלך לאימות דו־שלבי',
    'mfa_auth_u2f' => 'אימות עם התקן U2F',
    'mfa_auth_webauthn' => 'אימות עם מפתח אבטחה (WebAuthn)',
    '2fa_title' => 'אימות דו־שלבי',
    '2fa_wrong_validation' => 'האימות הדו־שלבי נכשל.',
    '2fa_one_time_password' => 'קוד אימות דו־שלבי',
    '2fa_recuperation_code' => 'נא להקליד את קוד השחזור לאימות הדו־שלבי',
    '2fa_otp_help' => 'יש לפתוח את יישומון האימות הדו־שלבי שלך ולהעתיק את הקוד',
    'u2f_otp_extension' => 'ב־Chrome, ב־<a href="{urlquantum}" lang="en">Firefox</a> וב־Opera קיימת תמיכה מובנית ב־U2F. בגרסאות ישנות של Firefox יש להתקין את <a href="{urlext}">תוספת התמיכה ב־U2F</a>.',

    'login_to_account' => 'כניסה לחשבון שלך',
    'login_with_recovery' => 'כניסה עם קוד שחזור',
    'login_again' => 'נא להיכנס לחשבונך פעם נוספת',
    'email' => 'דוא״ל',
    'password' => 'ססמה',
    'recovery' => 'קוד שחזור',
    'login' => 'כניסה',
    'button_remember' => 'לשמור את הפרטים שלי',
    'password_forget' => 'שכחת את הססמה שלך?',
    'password_reset' => 'איפוס הססמה שלך',
    'use_recovery' => 'באפשרותך להשתמש גם ב<a href=":url">קוד שחזור</a>',
    'signup_no_account' => 'אין לך חשבון?',
    'signup' => 'הרשמה',
    'create_account' => 'ניתן ליצור את החשבון הראשון על ידי <a href=":url">הרשמה</a>',
    'change_language_title' => 'החלפת שפה:',
    'change_language' => 'החלפת השפה ל:lang',

    'password_reset_title' => 'איפוס ססמה',
    'password_reset_email' => 'כתובת דוא״ל',
    'password_reset_send_link' => 'שליחת קישור לאיפוס הססמה',
    'password_reset_password' => 'ססמה',
    'password_reset_password_confirm' => 'אימות הססמה',
    'password_reset_action' => 'איפוס ססמה',
    'password_reset_email_content' => 'יש ללחוץ כאן כדי לאפס את הססמה שלך:',

    'register_title_welcome' => 'ברוך בואך לעותק החדש של מוניקה שזה עתה התקנת',
    'register_create_account' => 'עליך ליצור חשבון כדי להשתמש במוניקה',
    'register_title_create' => 'יצירת חשבון אצל מוניקה',
    'register_login' => 'ניתן <a href=":url">להיכנס</a> אם כבר יש לך חשבון.',
    'register_email' => 'נא להקליד כתובת דוא״ל תקנית',
    'register_email_example' => 'you@home',
    'register_firstname' => 'שם פרטי',
    'register_firstname_example' => 'למשל: ירון',
    'register_lastname' => 'שם משפחה',
    'register_lastname_example' => 'למשל: כהן',
    'register_password' => 'ססמה',
    'register_password_example' => 'נא להקליד ססמה מאובטחת',
    'register_password_confirmation' => 'אימות ססמה',
    'register_action' => 'רישום',
    'register_policy' => 'הרשמה מאמתת שקראת והסכמת ל<a href=":url" hreflang=":hreflang">מדיניות הפרטיות</a> ול<a href=":urlterm" hreflang=":hreflang">תנאי השימוש</a> שלנו.',
    'register_invitation_email' => 'מטעמי אבטחה, נא לציין את כתובת הדוא״ל של מי שהזמין אותך להצטרף לחשבון הזה. המידע הזה מופיע בהודעת ההזמנה.',

    'confirmation_title' => 'אימות כתובת הדוא״ל שלך',
    'confirmation_fresh' => 'נשלח קישור אימות טרי לכתובת הדוא״ל שלך.',
    'confirmation_check' => 'בטרם המשך התהליך, נא לחפש את קישור האימות בתיבת הדוא״ל שלך.',
    'confirmation_request_another' => 'אם לא קיבלת את ההודעה בדוא״ל <a :action>יש ללחוץ כאן כדי לבקש אחת נוספת</a>.',

    'confirmation_again' => 'כדי לשנות את כתובת הדוא״ל שלך נא <a href=":url" class="alert-link">ללחוץ כאן</a>.',
    'email_change_current_email' => 'כתובת הדוא״ל הנוכחית:',
    'email_change_title' => 'החלפת כתובת הדוא״ל שלך',
    'email_change_new' => 'כתובת דוא״ל חדשה',
    'email_changed' => 'כתובת הדוא״ל שלך הוחלפה. נא לבדוק בתיבת הדוא״ל שלך כדי לאמת אותה.',
];
