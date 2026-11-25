<?php

declare(strict_types=1);

namespace App\Helpers;

use InvalidArgumentException;

/**
 * Addresses on the public marketing site, in the reader's language when the site has it. The slugs
 * are copied from the marketing repository's config.php, since each language spells its own.
 */
class MarketingSite
{
    /** @var array<string, string> */
    private const array LOCALES = [
        'fr_FR' => 'fr',
        'de_DE' => 'de',
        'es_ES' => 'es',
        'pt_BR' => 'pt',
        'pt_PT' => 'pt',
        'nl_NL' => 'nl',
        'zh_CN' => 'zh',
        'ru_RU' => 'ru',
    ];

    /** @var array<string, array<string, string>> */
    private const array PAGES = [
        'home' => ['en' => '', 'fr' => '', 'de' => '', 'es' => '', 'pt' => '', 'nl' => '', 'zh' => '', 'ru' => ''],
        'pricing' => ['en' => 'pricing', 'fr' => 'tarifs', 'de' => 'preise', 'es' => 'precios', 'pt' => 'precos', 'nl' => 'prijzen', 'zh' => 'pricing', 'ru' => 'tseny'],
        'blog' => ['en' => 'blog', 'fr' => 'blog', 'de' => 'blog', 'es' => 'blog', 'pt' => 'blog', 'nl' => 'blog', 'zh' => 'blog', 'ru' => 'blog'],
        'sales' => ['en' => 'sales', 'fr' => 'ventes', 'de' => 'vertrieb', 'es' => 'ventas', 'pt' => 'vendas', 'nl' => 'verkoop', 'zh' => 'sales', 'ru' => 'prodazhi'],
        'people' => ['en' => 'features/people', 'fr' => 'fonctionnalites/personnes', 'de' => 'funktionen/menschen', 'es' => 'funcionalidades/personas', 'pt' => 'recursos/pessoas', 'nl' => 'functies/mensen', 'zh' => 'features/people', 'ru' => 'vozmozhnosti/lyudi'],
        'details' => ['en' => 'features/details', 'fr' => 'fonctionnalites/details', 'de' => 'funktionen/details', 'es' => 'funcionalidades/detalles', 'pt' => 'recursos/detalhes', 'nl' => 'functies/details', 'zh' => 'features/details', 'ru' => 'vozmozhnosti/detali'],
        'relationships' => ['en' => 'features/relationships', 'fr' => 'fonctionnalites/relations', 'de' => 'funktionen/beziehungen', 'es' => 'funcionalidades/relaciones', 'pt' => 'recursos/relacionamentos', 'nl' => 'functies/relaties', 'zh' => 'features/relationships', 'ru' => 'vozmozhnosti/otnosheniya'],
        'timelineReminders' => ['en' => 'features/timeline-and-reminders', 'fr' => 'fonctionnalites/chronologie-et-rappels', 'de' => 'funktionen/zeitleiste-und-erinnerungen', 'es' => 'funcionalidades/cronologia-y-recordatorios', 'pt' => 'recursos/linha-do-tempo-e-lembretes', 'nl' => 'functies/tijdlijn-en-herinneringen', 'zh' => 'features/timeline-and-reminders', 'ru' => 'vozmozhnosti/khronologiya-i-napominaniya'],
        'possessions' => ['en' => 'features/possessions', 'fr' => 'fonctionnalites/possessions', 'de' => 'funktionen/besitz', 'es' => 'funcionalidades/pertenencias', 'pt' => 'recursos/pertences', 'nl' => 'functies/bezittingen', 'zh' => 'features/possessions', 'ru' => 'vozmozhnosti/veshchi'],
        'inventory' => ['en' => 'features/inventory', 'fr' => 'fonctionnalites/inventaire', 'de' => 'funktionen/inventar', 'es' => 'funcionalidades/inventario', 'pt' => 'recursos/inventario', 'nl' => 'functies/inventaris', 'zh' => 'features/inventory', 'ru' => 'vozmozhnosti/inventar'],
        'locations' => ['en' => 'features/locations', 'fr' => 'fonctionnalites/emplacements', 'de' => 'funktionen/standorte', 'es' => 'funcionalidades/ubicaciones', 'pt' => 'recursos/locais', 'nl' => 'functies/locaties', 'zh' => 'features/locations', 'ru' => 'vozmozhnosti/mesta'],
        'maintenanceLoans' => ['en' => 'features/maintenance-and-loans', 'fr' => 'fonctionnalites/entretien-et-prets', 'de' => 'funktionen/wartung-und-verleih', 'es' => 'funcionalidades/mantenimiento-y-prestamos', 'pt' => 'recursos/manutencao-e-emprestimos', 'nl' => 'functies/onderhoud-en-uitlenen', 'zh' => 'features/maintenance-and-loans', 'ru' => 'vozmozhnosti/obsluzhivanie-i-zaymy'],
        'knowledge' => ['en' => 'features/knowledge', 'fr' => 'fonctionnalites/connaissances', 'de' => 'funktionen/wissen', 'es' => 'funcionalidades/conocimiento', 'pt' => 'recursos/conhecimento', 'nl' => 'functies/kennis', 'zh' => 'features/knowledge', 'ru' => 'vozmozhnosti/znaniya'],
        'notes' => ['en' => 'features/notes', 'fr' => 'fonctionnalites/notes', 'de' => 'funktionen/notizen', 'es' => 'funcionalidades/notas', 'pt' => 'recursos/notas', 'nl' => 'functies/notities', 'zh' => 'features/notes', 'ru' => 'vozmozhnosti/zametki'],
        'lists' => ['en' => 'features/lists', 'fr' => 'fonctionnalites/listes', 'de' => 'funktionen/listen', 'es' => 'funcionalidades/listas', 'pt' => 'recursos/listas', 'nl' => 'functies/lijsten', 'zh' => 'features/lists', 'ru' => 'vozmozhnosti/spiski'],
        'folders' => ['en' => 'features/folders', 'fr' => 'fonctionnalites/dossiers', 'de' => 'funktionen/ordner', 'es' => 'funcionalidades/carpetas', 'pt' => 'recursos/pastas', 'nl' => 'functies/mappen', 'zh' => 'features/folders', 'ru' => 'vozmozhnosti/papki'],
        'templates' => ['en' => 'features/templates', 'fr' => 'fonctionnalites/modeles', 'de' => 'funktionen/vorlagen', 'es' => 'funcionalidades/plantillas', 'pt' => 'recursos/modelos', 'nl' => 'functies/sjablonen', 'zh' => 'features/templates', 'ru' => 'vozmozhnosti/shablony'],
        'vaults' => ['en' => 'features/vaults', 'fr' => 'fonctionnalites/coffres', 'de' => 'funktionen/tresore', 'es' => 'funcionalidades/bovedas', 'pt' => 'recursos/cofres', 'nl' => 'functies/kluizen', 'zh' => 'features/vaults', 'ru' => 'vozmozhnosti/khranilishcha'],
        'sharing' => ['en' => 'features/sharing', 'fr' => 'fonctionnalites/partage', 'de' => 'funktionen/teilen', 'es' => 'funcionalidades/compartir', 'pt' => 'recursos/compartilhamento', 'nl' => 'functies/delen', 'zh' => 'features/sharing', 'ru' => 'vozmozhnosti/dostup'],
        'languages' => ['en' => 'features/languages', 'fr' => 'fonctionnalites/langues', 'de' => 'funktionen/sprachen', 'es' => 'funcionalidades/idiomas', 'pt' => 'recursos/idiomas', 'nl' => 'functies/talen', 'zh' => 'features/languages', 'ru' => 'vozmozhnosti/yazyki'],
        'privacyFeatures' => ['en' => 'features/privacy', 'fr' => 'fonctionnalites/confidentialite', 'de' => 'funktionen/privatsphaere', 'es' => 'funcionalidades/privacidad', 'pt' => 'recursos/privacidade', 'nl' => 'functies/privacy', 'zh' => 'features/privacy', 'ru' => 'vozmozhnosti/privatnost'],
        'mcpServer' => ['en' => 'mcp', 'fr' => 'mcp', 'de' => 'mcp', 'es' => 'mcp', 'pt' => 'mcp', 'nl' => 'mcp', 'zh' => 'mcp', 'ru' => 'mcp'],
        'handbook' => ['en' => 'handbook', 'fr' => 'manuel', 'de' => 'handbuch', 'es' => 'manual', 'pt' => 'manual', 'nl' => 'handboek', 'zh' => 'handbook', 'ru' => 'rukovodstvo'],
        'contribute' => ['en' => 'contribute', 'fr' => 'contribuer', 'de' => 'mitmachen', 'es' => 'colaborar', 'pt' => 'contribuir', 'nl' => 'bijdragen', 'zh' => 'contribute', 'ru' => 'uchastie'],
        'support' => ['en' => 'support', 'fr' => 'support', 'de' => 'support', 'es' => 'soporte', 'pt' => 'suporte', 'nl' => 'support', 'zh' => 'support', 'ru' => 'podderzhka'],
    ];

    public static function locale(): string
    {
        return self::LOCALES[app()->getLocale()] ?? 'en';
    }

    public static function url(string $page, ?string $locale = null): string
    {
        $locale ??= self::locale();

        if (! isset(self::PAGES[$page][$locale])) {
            throw new InvalidArgumentException("The marketing site has no page [{$page}] in [{$locale}].");
        }

        $slug = self::PAGES[$page][$locale];
        $base = rtrim((string) config('monica.marketing_url'), '/');

        return $slug === '' ? "{$base}/{$locale}/" : "{$base}/{$locale}/{$slug}/";
    }

    public static function handbookArticle(string $slug, ?string $locale = null): string
    {
        $handbook = self::url('handbook', $locale);

        return "{$handbook}{$slug}/";
    }
}
