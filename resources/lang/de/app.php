<?php

return [
    'update' => 'Aktualisieren',
    'save' => 'Speichern',
    'add' => 'Hinzufügen',
    'cancel' => 'Abbrechen',
    'delete' => 'Löschen',
    'edit' => 'Bearbeiten',
    'upload' => 'Hochladen',
    'close' => 'Schließen',
    'create' => 'Erstellen',
    'remove' => 'Entfernen',
    'revoke' => 'Aufheben',
    'done' => 'Fertig',
    'verify' => 'Überprüfe',
    'for' => 'für',
    'new' => 'Neu',
    'unknown' => 'Ich weiß es nicht',
    'load_more' => 'Lade mehr',
    'loading' => 'Lade mehr...',
    'with' => 'mit',
    'days' => 'Tag|Tage',

    'application_title' => 'Monica – persönlicher Beziehungsmanager',
    'application_description' => 'Monica ist ein Werkzeug, um Ihre Interaktionen mit Ihren Lieben, Freunden und Familie zu verwalten.',
    'application_og_title' => 'Haben Sie bessere Beziehungen zu Ihren Lieben. Kostenloses Online CRM für Freunde und Familie.',

    'markdown_description' => 'Du möchtest deinen Test schöner formatieren? Monica unterstützt Markdown.',
    'markdown_link' => 'Öffne die Dokumentation',

    'header_settings_link' => 'Einstellungen',
    'header_logout_link' => 'Ausloggen',
    'header_changelog_link' => 'Produktänderungen',

    'main_nav_cta' => 'Person hinzufügen',
    'main_nav_dashboard' => 'Dashboard',
    'main_nav_family' => 'Personen',
    'main_nav_journal' => 'Tagebuch',
    'main_nav_activities' => 'Aktivitäten',
    'main_nav_tasks' => 'Aufgaben',

    'footer_remarks' => 'Eine Anmerkung?',
    'footer_send_email' => 'Schick\' mir eine E-Mail',
    'footer_privacy' => 'Datenschutzrichtlinie',
    'footer_release' => 'Versionshinweise',
    'footer_newsletter' => 'Newsletter',
    'footer_source_code' => 'Monica bei GitHub',
    'footer_version' => 'Version: :version',
    'footer_new_version' => 'Es ist eine neue Version verfügbar',

    'footer_modal_version_whats_new' => 'Was gibt\'s Neues',
    'footer_modal_version_release_away' => 'Du bist ein Release hinter der neuesten verfügbaren Version. Du solltest deine Installation updaten.|Du bist :number Releases hinter der neuesten verfügbaren Version. Du solltest deine Installation updaten.',

    'breadcrumb_dashboard' => 'Dashboard',
    'breadcrumb_list_contacts' => 'Kontaktliste',
    'breadcrumb_journal' => 'Tagebuch',
    'breadcrumb_settings' => 'Einstellungen',
    'breadcrumb_settings_export' => 'Export',
    'breadcrumb_settings_users' => 'Benutzer',
    'breadcrumb_settings_users_add' => 'Benutzer hinzufügen',
    'breadcrumb_settings_subscriptions' => 'Abonnement',
    'breadcrumb_settings_import' => 'Import',
    'breadcrumb_settings_import_report' => 'Import-Bericht',
    'breadcrumb_settings_import_upload' => 'Hochladen',
    'breadcrumb_settings_tags' => 'Markierungen',
    'breadcrumb_add_significant_other' => 'Lebensgefährte hinzufügen',
    'breadcrumb_edit_significant_other' => 'Lebensgefährte bearbeiten',
    'breadcrumb_add_note' => 'Notiz hinzufügen',
    'breadcrumb_edit_note' => 'Notiz bearbeiten',
    'breadcrumb_api' => 'API',
    'breadcrumb_edit_introductions' => 'Wie habt ihr euch getroffen',
    'breadcrumb_settings_personalization' => 'Personalisierung',
    'breadcrumb_settings_security' => 'Sicherheit',
    'breadcrumb_settings_security_2fa' => 'Zwei-Faktor-Authentifizierung',

    'gender_male' => 'Männlich',
    'gender_female' => 'Weiblich',
    'gender_none' => 'Möchte ich nicht angeben',

    'error_title' => 'Whoops! Da lief etwas falsch.',
    'error_unauthorized' => 'Du darfst das leider nicht, da du nicht angemeldet bist.',
    'error_save' => 'Beim Versuch die Daten zu speichern ist ein Fehler aufgetreten.',

    'default_save_success' => 'Die Daten wurden gespeichert.',

    'compliance_title' => 'Entschuldige die Unterbrechung.',
    'compliance_desc' => 'Wir haben unsere <a href=":urlterm" hreflang=":hreflang">AGBs</a> und <a href=":url" hreflang=":hreflang">Datenschutzerklärung</a> geändert. Wir sind gesetzlich dazu verpflichtet zu verlangen, dass du beides durchliest und akzeptierst, damit du deinen Account weiter nutzen kannst.',
    'compliance_desc_end' => 'Wir machen nichts Böses mit deinen Daten oder deinem Account und werden das auch nie tun.',
    'compliance_terms' => 'Neue AGB und Datenschutzerklärung akzeptieren',

    // Relationship types
    // Yes, each relationship type has 8 strings associated with it.
    // This is because we need to indicate the name of the relationship type,
    // and also the name of the opposite side of this relationship (father/son),
    // and then, the feminine version of the string. Finally, in some sentences
    // in the UI, we need to include the name of the person we add the relationship
    // to.
    'relationship_type_group_love' => 'Liebesbeziehungen',
    'relationship_type_group_family' => 'Familienverhältnisse',
    'relationship_type_group_friend' => 'Freundschaftsbeziehungen',
    'relationship_type_group_work' => 'Arbeitsverhältnisse',
    'relationship_type_group_other' => 'Andere Art von Beziehungen',

    'relationship_type_partner' => 'Lebensgefährte',
    'relationship_type_partner_female' => 'Lebensgefährtin',
    'relationship_type_partner_with_name' => ':names Lebensgefährte',
    'relationship_type_partner_female_with_name' => ':names Lebensgefährtin',

    'relationship_type_spouse' => 'Ehegatte',
    'relationship_type_spouse_female' => 'Ehegattin',
    'relationship_type_spouse_with_name' => ':names Ehegatten',
    'relationship_type_spouse_female_with_name' => ':names Ehegattin',

    'relationship_type_date' => 'Verabredung',
    'relationship_type_date_female' => 'Verabredung',
    'relationship_type_date_with_name' => ':names Verabredung',
    'relationship_type_date_female_with_name' => ':names Verabredung',

    'relationship_type_lover' => 'Liebhaber',
    'relationship_type_lover_female' => 'Liebhaberin',
    'relationship_type_lover_with_name' => ':names Liebhaber',
    'relationship_type_lover_female_with_name' => ':names Liebhaberin',

    'relationship_type_inlovewith' => 'verliebt in',
    'relationship_type_inlovewith_female' => 'verliebt in',
    'relationship_type_inlovewith_with_name' => ':name ist verliebt in',
    'relationship_type_inlovewith_female_with_name' => ':name ist verliebt in',

    'relationship_type_lovedby' => 'geliebt von',
    'relationship_type_lovedby_female' => 'geliebt von',
    'relationship_type_lovedby_with_name' => ':names heimlicher Verehrer',
    'relationship_type_lovedby_female_with_name' => ':names heimliche Verehrerin',

    'relationship_type_ex' => 'Ex-Freund',
    'relationship_type_ex_female' => 'Ex-Freundin',
    'relationship_type_ex_with_name' => ':name\'s Ex-Freund',
    'relationship_type_ex_female_with_name' => ':name\'s Ex-Freundin',

    'relationship_type_parent' => 'Vater',
    'relationship_type_parent_female' => 'Mutter',
    'relationship_type_parent_with_name' => ':names Vater',
    'relationship_type_parent_female_with_name' => ':name\'s Mutter',

    'relationship_type_child' => 'Sohn',
    'relationship_type_child_female' => 'Tochter',
    'relationship_type_child_with_name' => ':names Sohn',
    'relationship_type_child_female_with_name' => ':names Tochter',

    'relationship_type_sibling' => 'Bruder',
    'relationship_type_sibling_female' => 'Schwester',
    'relationship_type_sibling_with_name' => ':names Bruder',
    'relationship_type_sibling_female_with_name' => ':names Schwester',

    'relationship_type_grandparent' => 'Großvater',
    'relationship_type_grandparent_female' => 'Großmutter',
    'relationship_type_grandparent_with_name' => ':name\'s Großvater',
    'relationship_type_grandparent_female_with_name' => ':name\'s Großmutter',

    'relationship_type_grandchild' => 'Enkel',
    'relationship_type_grandchild_female' => 'Enkelin',
    'relationship_type_grandchild_with_name' => ':name\'s Enkel',
    'relationship_type_grandchild_female_with_name' => ':name\'s Enkelin',

    'relationship_type_uncle' => 'Onkel',
    'relationship_type_uncle_female' => 'Tante',
    'relationship_type_uncle_with_name' => ':name\'s Onkel',
    'relationship_type_uncle_female_with_name' => ':name\'s Tante',

    'relationship_type_nephew' => 'Neffe',
    'relationship_type_nephew_female' => 'Nichte',
    'relationship_type_nephew_with_name' => ':name\'s Neffe',
    'relationship_type_nephew_female_with_name' => ':name\'s Nichte',

    'relationship_type_cousin' => 'Cousin',
    'relationship_type_cousin_female' => 'Cousine',
    'relationship_type_cousin_with_name' => ':name\'s Cousin',
    'relationship_type_cousin_female_with_name' => ':name\'s Cousine',

    'relationship_type_godfather' => 'Pate',
    'relationship_type_godfather_female' => 'Patin',
    'relationship_type_godfather_with_name' => ':names Pate',
    'relationship_type_godfather_female_with_name' => ':names Patin',

    'relationship_type_godson' => 'Patenkind',
    'relationship_type_godson_female' => 'Patenkind',
    'relationship_type_godson_with_name' => ':name\'s Patenkind',
    'relationship_type_godson_female_with_name' => ':name\'s Patenkind',

    'relationship_type_friend' => 'Freund',
    'relationship_type_friend_female' => 'Freundin',
    'relationship_type_friend_with_name' => ':name\'s Freund',
    'relationship_type_friend_female_with_name' => ':name\'s Freundin',

    'relationship_type_bestfriend' => 'Bester Freund',
    'relationship_type_bestfriend_female' => 'Beste Freundin',
    'relationship_type_bestfriend_with_name' => ':name\'s bester Freund',
    'relationship_type_bestfriend_female_with_name' => ':name\'s beste Freundin',

    'relationship_type_colleague' => 'Kollege',
    'relationship_type_colleague_female' => 'Kollegin',
    'relationship_type_colleague_with_name' => ':name\'s Kollege',
    'relationship_type_colleague_female_with_name' => ':name\'s Kollegin',

    'relationship_type_boss' => 'Chef',
    'relationship_type_boss_female' => 'Chefin',
    'relationship_type_boss_with_name' => ':name\'s Chef',
    'relationship_type_boss_female_with_name' => ':name\'s Chefin',

    'relationship_type_subordinate' => ':name\'s Untergebener',
    'relationship_type_subordinate_female' => ':name\'s Untergebene',
    'relationship_type_subordinate_with_name' => ':name\'s Mitarbeiter',
    'relationship_type_subordinate_female_with_name' => ':name\'s Mitarbeiterin',

    'relationship_type_mentor' => 'Mentor',
    'relationship_type_mentor_female' => 'Mentorin',
    'relationship_type_mentor_with_name' => ':name\'s Mentor',
    'relationship_type_mentor_female_with_name' => ':name\'s Mentorin',

    'relationship_type_protege' => 'Schützling',
    'relationship_type_protege_female' => 'Schützling',
    'relationship_type_protege_with_name' => ':name\'s Schützling',
    'relationship_type_protege_female_with_name' => ':name\'s Schützling',

    'relationship_type_ex_husband' => 'Ex-Mann',
    'relationship_type_ex_husband_female' => 'Ex-Frau',
    'relationship_type_ex_husband_with_name' => ':name’s Ex-Mann',
    'relationship_type_ex_husband_female_with_name' => ':name’s Ex-Frau',
];
