<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



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

    'failed' => 'بيانات الاعتماد هذه غير متطابقة مع البيانات المسجلة لدينا.',
    'throttle' => 'عدد كبير جدا من محاولات الدخول. يرجى المحاولة مرة أخرى بعد :seconds ثانية.',
    'not_authorized' => 'ليس لديك الصلاحية لتنفيذ هذا الأمر',
    'signup_disabled' => 'تسجيل الإشتراك معطل حالياً',
    'back_homepage' => 'العودة إلى الصفحة الرئيسية',
    'mfa_auth_otp' => 'المصادقة مع جهاز العامل الثنائي الخاص بك',
    'mfa_auth_u2f' => 'المصادقة باستخدام جهاز U2F',
    '2fa_title' => 'المصادقة الثنائية',
    '2fa_wrong_validation' => 'فشلت المصادقة الثنائية.',
    '2fa_one_time_password' => 'رمز المصادقة الثنائية',
    '2fa_recuperation_code' => 'أدخل رمز استرداد العامل الثنائي',
    '2fa_otp_help' => 'قم بفتح تطبيق المصادقة الثنائية في هاتفك و انسخ الرمز',
    'u2f_otp_extension' => 'إن U2F مدعوم محلياً على متصفح كروم، <a href="{urlquantum}" lang="en">فايرفوكس</a> و أوبيرا. في إصدارات فايرفوكس القديمة، قم بتثبيت <a href="{urlext}">داعم U2F الإضافي</a>.',

    'login_to_account' => 'تسجيل الدخول إلى حسابك',
    'login_again' => 'الرجاء تسجيل الدخول مجدداً لحسابك',
    'email' => 'البريد الإلكتروني',
    'password' => 'كلمة المرور',
    'login' => 'تسجيل الدخول',
    'button_remember' => 'تذكرني',
    'password_forget' => 'نسيت كلمة المرور؟',
    'password_reset' => 'إعادة تعيين كلمة مرورك',
    'signup_no_account' => 'ليس لديك حساب؟',
    'signup' => 'تسجيل الإشتراك',
    'create_account' => 'انشئ الحساب الأول عبر <a href=":url">تسجيل الإشتراك</a>',
    'change_language_title' => 'تغيير اللغة:',
    'change_language' => 'تغيير اللغة إلى :lang',

    'password_reset_title' => 'إعادة تعيين كلمة المرور',
    'password_reset_email' => 'عنوان البريد',
    'password_reset_send_link' => 'أرسل رابط إعادة تعيين كلمة المرور',
    'password_reset_password' => 'كلمة المرور',
    'password_reset_password_confirm' => 'تأكيد كلمة المرور',
    'password_reset_action' => 'إعادة تعيين كلمة المرور',
    'password_reset_email_content' => 'اضغط هنا لإعادة تعيين كلمة مرورك:',

    'register_title_welcome' => 'مرحبا بك في تطبيق Monica المثبت حديثاً',
    'register_create_account' => 'يجب أن تنشئ حساباً لتستخدم Monica',
    'register_title_create' => 'انشئ حسابك لـMonica',
    'register_login' => 'قم بـ <a href=":url">تسجيل الدخول</a> إذا كان لديك حساب مسبقاً.',
    'register_email' => 'أدخل عنوان بريد صالح',
    'register_email_example' => 'example@example.com',
    'register_firstname' => 'الاسم الأول',
    'register_firstname_example' => 'مثال: أحمد',
    'register_lastname' => 'الاسم الأخير',
    'register_lastname_example' => 'مثال: مراد',
    'register_password' => 'كلمة المرور',
    'register_password_example' => 'أدخل كلمة مرور آمنة',
    'register_password_confirmation' => 'تأكيد كلمة المرور',
    'register_action' => 'تسجيل الإشتراك',
    'register_policy' => 'بتسجيل إشتراكك تُفيد بأنك قرأت و قبِلت <a href=":url" hreflang=":hreflang">سياسة الخصوصية</a> و <a href=":urlterm" hreflang=":hreflang">شروط الإستخدام</a> الخاصة بنا.',
    'register_invitation_email' => 'لأسباب أمنية، الرجاء تحديد البريد الإلكتروني للشخص الذي قمت بدعوته للإنضمام لهذا الحساب. المعلومات موجودة في رسالة الدعوة.',

    'confirmation_title' => 'Verify Your Email Address',
    'confirmation_fresh' => 'A fresh verification link has been sent to your email address.',
    'confirmation_check' => 'Before proceeding, please check your email for a verification link.',
    'confirmation_request_another' => 'If you did not receive the email <a href=":url">click here to request another</a>.',

    'confirmation_again' => 'إذا أردت تغيير بريدك الإلكتروني يمكنك <a href=":url" class="alert-link">الضغط هنا</a>.',
    'email_change_current_email' => 'البريد الإلكتروني الحالي:',
    'email_change_title' => 'قم بتغيير عنوان بريدك',
    'email_change_new' => 'بريد إلكتروني جديد',
    'email_changed' => 'لقد تم تغيير بريدك الإلكتروني. تحقق من بريدك لتأكيده.',
];
