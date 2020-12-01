<?php

/**
 * ⚠️ Edition not allowed except for 'en' language.
 *
 * @see https://github.com/monicahq/monica/blob/master/docs/contribute/translate.md for translations.
 */

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

    'failed' => 'These credentials do not match our records.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'not_authorized' => 'You are not authorized to execute this action',
    'signup_disabled' => 'Registration is currently disabled',
    'signup_error' => 'An error occured trying to register the user',
    'back_homepage' => 'Back to homepage',
    'mfa_auth_otp' => 'Authenticate with your two factor device',
    'mfa_auth_webauthn' => 'Authenticate with a security key (WebAuthn)',
    '2fa_title' => 'Two Factor Authentication',
    '2fa_wrong_validation' => 'Ο έλεγχος ταυτότητας δύο παραγόντων απέτυχε.',
    '2fa_one_time_password' => 'Κωδικός ελέγχου ταυτότητας δύο παραγόντων',
    '2fa_recuperation_code' => 'Πληκτρολογήστε έναν κωδικό ελέγχου ταυτότητας δύο παραγόντων',
    '2fa_otp_help' => 'Ανοίξτε την εφαρμογή ελέγχου ταυτότητας δύο παραγόντων στο κινητό σας και αντιγράψτε τον κωδικό',

    'login_to_account' => 'Συνδεθείτε στο λογαριασμό σας',
    'login_with_recovery' => 'Συνδεθείτε με έναν κωδικό ανάκτησης',
    'login_again' => 'Παρακαλούμε συνδεθείτε στο λογαριασμό σας ξανά',
    'email' => 'Email',
    'password' => 'Κωδικός',
    'recovery' => 'Κωδικός ανάκτησης',
    'login' => 'Σύνδεση',
    'button_remember' => 'Να με θυμάσαι',
    'password_forget' => 'Ξεχάσατε τον κωδικό πρόσβασης;',
    'password_reset' => 'Επαναφορά κωδικού πρόσβασης',
    'use_recovery' => 'Ή μπορείτε να χρησιμοποιήσετε έναν <a href=":url">κωδικό επαναφοράς</a>',
    'signup_no_account' => 'Δεν έχετε λογαριασμό;',
    'signup' => 'Εγγραφή',
    'create_account' => 'Δημιουργήστε τον πρώτο λογαριασμό με <a href=":url">εγγραφή</a>',
    'change_language_title' => 'Αλλαγή γλώσσας:',
    'change_language' => 'Αλλαγή γλώσσας σε :lang',

    'password_reset_title' => 'Επαναφορά κωδικού πρόσβασης',
    'password_reset_email' => 'Διεύθυνση E-mail',
    'password_reset_send_link' => 'Αποστολή συνδέσμου επαναφοράς κωδικού πρόσβασης',
    'password_reset_password' => 'Κωδικός πρόσβασης',
    'password_reset_password_confirm' => 'Επιβεβαίωση Κωδικού πρόσβασης',
    'password_reset_action' => 'Επαναφορά Κωδικού πρόσβασης',
    'password_reset_email_content' => 'Κάντε κλικ εδώ για να επαναφέρετε τον κωδικό πρόσβασής σας:',

    'register_title_welcome' => 'Καλώς ήλθατε στην νέα εγκατάσταση του Monica',
    'register_create_account' => 'Πρέπει να δημιουργήσετε έναν λογαριασμό για να χρησιμοποιήσετε το Monica',
    'register_title_create' => 'Δημιουργήστε το λογαριασμό Monica',
    'register_login' => '<a href=":url">Συνδεθείτε</a> αν έχετε ήδη λογαριασμό.',
    'register_email' => 'Εισάγετε μια έγκυρη διεύθυνση email',
    'register_email_example' => 'εσείς@σπίτι',
    'register_firstname' => 'Όνομα',
    'register_firstname_example' => 'π.χ. Κώστας',
    'register_lastname' => 'Επώνυμο',
    'register_lastname_example' => 'π.χ. Παπαδόπουλος',
    'register_password' => 'Κωδικός πρόσβασης',
    'register_password_example' => 'Εισάγετε έναν ασφαλή κωδικό',
    'register_password_confirmation' => 'Επιβεβαίωση Κωδικού πρόσβασης',
    'register_action' => 'Εγγραφή',
    'register_policy' => 'Η εγγραφή σας σημαίνει ότι διαβάσατε και συμφωνείτε με την <a href=":url" hreflang=":hreflang">Πολιτική απορρήτου</a> και τους <a href=":urlterm" hreflang=":hreflang">Όρους χρήσης</a>.',
    'register_invitation_email' => 'Για λόγους ασφαλείας, αναφέρετε τη διεύθυνση ηλεκτρονικού ταχυδρομείου του ατόμου που σας προσκάλεσε να εγγραφείτε σε αυτόν τον λογαριασμό. Αυτές οι πληροφορίες παρέχονται στο email πρόσκλησης.',

    'confirmation_title' => 'Επιβεβαίωση διεύθυνσης email',
    'confirmation_fresh' => 'Ένα επιβεβαιωτικό email στάλθηκε στην διεύθυνση ηλεκτρονικού ταχυδρομείου σας.',
    'confirmation_check' => 'Πριν προχωρήσετε, παρακαλώ ελέγξτε το email σας για τον σύνδεσμο επαλήθευσης.',
    'confirmation_request_another' => 'Αν δεν έχετε παραλάβει το email <a :action>πατήστε εδώ για να αποστείλουμε νέο</a>.',

    'confirmation_again' => 'Αν θέλετε να αλλάξετε την διεύθυνση email σας <a href=":url" class="alert-link">πατήστε εδώ</a>.',
    'email_change_current_email' => 'Διεύθυνση email αυτή τη στιγμή:',
    'email_change_title' => 'Αλλάξτε την διεύθυνση email σας',
    'email_change_new' => 'New email address',
    'email_changed' => 'Your email address has been changed. Check your mailbox to validate it.',
];
