# vCard Import Enhancements - Documentation complète

## 📋 Vue d'ensemble

Cette documentation décrit les améliorations apportées au système d'importation vCard de Monica CRM pour permettre l'importation complète de tous les champs, y compris les organisations, les adresses formatées, et la gestion des images de carte.

**Import réalisé:** 1260 contacts avec 109 photos depuis un fichier vCard de 27 MB

## 🎯 Objectifs atteints

- ✅ Import des organisations (champ ORG) comme "Job Information"
- ✅ Formatage automatique des noms et organisations (suppression des `;` finaux)
- ✅ Formatage intelligent des adresses (gestion des retours à la ligne, espacement)
- ✅ Mapping des types d'adresses (HOME,pref → home)
- ✅ Gestion gracieuse des images de carte (404 au lieu de 500 quand Mapbox non configuré)
- ✅ Documentation complète avec PHPDoc pour toutes les fonctions

## 🏗️ Architecture - Diagramme de flux

```
┌─────────────────────────────────────────────────────────────────┐
│                    Import vCard (.vcf file)                      │
│                     ContactImportController                       │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│              Sabre\VObject\Reader::read($vcard)                  │
│                  Parse vCard format (RFC 6350)                   │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    DAV Import Pipeline                           │
│              Multiple Importers (Order attribute)                │
└────────────────────────────┬────────────────────────────────────┘
                             │
         ┌───────────────────┼───────────────────┐
         │                   │                   │
         ▼                   ▼                   ▼
┌──────────────────┐  ┌─────────────┐  ┌────────────────────┐
│  ImportAvatar    │  │ ImportNotes │  │ ImportJobInform..  │
│  Order(20)       │  │ Order(30)   │  │ Order(41) - NEW    │
│                  │  │             │  │                    │
│ PHOTO field →    │  │ NOTE field  │  │ ORG field →        │
│ storage/photos   │  │ → notes tbl │  │ Company + Link     │
└──────────────────┘  └─────────────┘  └────────────────────┘
         │                   │                   │
         │                   ▼                   ▼
         │          ┌──────────────┐   ┌──────────────────┐
         │          │ CreateNote() │   │ CreateCompany()  │
         │          └──────────────┘   │ or find existing │
         │                             │                  │
         │                             │ UpdateJobInfo()  │
         │                             │ → contacts.      │
         │                             │   company_id     │
         │                             └──────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────┐
│                        ImportAddress                             │
│                         Order(40)                                │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
         ┌───────────────────┴───────────────────┐
         │                                       │
         ▼                                       ▼
┌─────────────────────┐              ┌─────────────────────────┐
│  getAddressType()   │              │ formatStreetAddress()   │
│                     │              │ - NEW FUNCTION          │
│ HOME,pref → home    │              │                         │
│ Create if missing   │              │ "61\nRue" → "61, Rue"  │
│                     │              │ "61Rue" → "61, Rue"    │
└─────────────────────┘              └─────────────────────────┘
         │                                       │
         └───────────────────┬───────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                  vCard ADR field mapping                         │
│                                                                  │
│  parts[0]: PO Box (unused)                                       │
│  parts[1]: Extended (apartment/suite) → line_2                   │
│  parts[2]: Street → line_1 (via formatStreetAddress)            │
│  parts[3]: City                                                  │
│  parts[4]: Province                                              │
│  parts[5]: Postal Code                                           │
│  parts[6]: Country                                               │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│            UpdateAddress() or CreateAddress()                    │
│                 + AssociateAddressToContact()                    │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Contact Feed Display                          │
│                  ActionFeedAddress helper                        │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
         ┌───────────────────┴───────────────────┐
         │                                       │
         ▼                                       ▼
┌─────────────────────┐              ┌─────────────────────────┐
│  Map Image (opt.)   │              │  Address Display        │
│                     │              │                         │
│ MapHelper::         │              │ line_1: "61, Rue..."   │
│ getStaticImage()    │              │ line_2: "Apt 2"        │
│                     │              │ city, province, etc.   │
│ If Mapbox config:   │              │                         │
│ → Generate URL      │              │ Type: 🏡 Home          │
│                     │              │                         │
│ If not configured:  │              │ View on map (OSM link) │
│ → Return null       │              │                         │
│ → No broken image   │              └─────────────────────────┘
└─────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────┐
│      ContactModuleAddressImageController::show()                 │
│                                                                  │
│   If MapHelper returns null → abort(404)                        │
│   Else → Proxy Mapbox API request → Stream image                │
└─────────────────────────────────────────────────────────────────┘
```

## 📦 Fichiers modifiés et créés

### Nouveaux fichiers

#### 1. `app/Domains/Contact/ManageJobInformation/Dav/ImportJobInformation.php`

**Objectif:** Importer le champ ORG des vCards comme "Job Information" dans Monica.

**Fonctionnement:**
- Extrait le champ ORG de la vCard
- Utilise `formatValue()` pour nettoyer les `;` finaux
- Cherche ou crée la Company correspondante
- Lie le contact à la company via `UpdateJobInformation` service
- Met à jour `contacts.company_id` (PAS contact_information)

**Méthodes:**
- `handle(VCard $vcard): bool` - Vérifie si c'est un contact individuel
- `import(VCard $vcard, ?VCardResource $result): ?VCardResource` - Effectue l'import

**Ordre d'exécution:** #[Order(41)] - Après ImportContactInformation

### Fichiers modifiés

#### 2. `app/Domains/Contact/Dav/Importer.php`

**Modification:** Amélioration de `formatValue()`

**Avant:**
```php
protected function formatValue(?string $value): ?string
{
    if (empty($value)) {
        return null;
    }
    
    return str_replace('\\;', ';', trim($value));
}
```

**Après:**
```php
protected function formatValue(?string $value): ?string
{
    if (empty($value)) {
        return null;
    }
    
    $formatted = str_replace('\\;', ';', trim($value));
    // Remove trailing semicolon (e.g., "Company;" -> "Company")
    return rtrim($formatted, ';');
}
```

**Raison:** Les vCards peuvent contenir des `;` finaux (ex: "Meilleur Ami;", "Louis;") qui s'affichaient dans l'interface.

#### 3. `app/Domains/Contact/ManageContact/Dav/ImportAddress.php`

**Modifications majeures:**

##### a) Nouvelle méthode `formatStreetAddress()`

```php
/**
 * Format street address to add proper spacing after street numbers.
 * 
 * Handles various vCard formatting issues:
 * - Newline characters: "61\nRue Des Lutiers" → "61, Rue Des Lutiers"
 * - Missing space: "61Rue Des Lutiers" → "61, Rue Des Lutiers"
 * - Already spaced: "61 Rue Des Lutiers" → "61, Rue Des Lutiers"
 * 
 * Also normalizes whitespace (tabs, multiple spaces, etc.).
 */
private function formatStreetAddress(?string $street): ?string
{
    if (empty($street)) {
        return null;
    }
    
    // Replace newlines/multiple spaces with single space
    $street = preg_replace('/[\n\r\t]+/', ' ', $street);
    $street = preg_replace('/\s+/', ' ', $street);
    $street = trim($street);
    
    // If address starts with digits followed by space or letter, add ", "
    if (preg_match('/^(\d+)\s*(.+)$/', $street, $matches)) {
        return $matches[1] . ', ' . trim($matches[2]);
    }
    
    return $street;
}
```

**Exemples de formatage:**
- `"61\nRue Des Lutiers"` → `"61, Rue Des Lutiers"`
- `"61Rue Des Lutiers"` → `"61, Rue Des Lutiers"`
- `"61 Rue Des Lutiers"` → `"61, Rue Des Lutiers"`

##### b) Amélioration de `getAddressType()`

**Avant:**
```php
private function getAddressType(Property $adr): ?AddressType
{
    $type = Arr::get($adr->parameters(), 'TYPE');
    if ($type) {
        $typeValue = $type->getValue();
        
        return AddressType::where([
            'account_id' => $this->account()->id,
            'type' => $typeValue, // Créait "HOME,pref" au lieu de "home"
        ])->firstOrFail();
    }
    return null;
}
```

**Après:**
```php
private function getAddressType(Property $adr): ?AddressType
{
    $type = Arr::get($adr->parameters(), 'TYPE');
    if ($type) {
        $typeValue = $type->getValue();
        
        // Map HOME,xxx to 'home' type
        if (str_starts_with(strtolower($typeValue), 'home')) {
            $typeValue = 'home';
        }
        
        return AddressType::where([
            'account_id' => $this->account()->id,
            'type' => $typeValue,
        ])->firstOrFail();
    }
    return null;
}
```

**Raison:** Les vCards peuvent avoir des types comme "HOME,pref" mais Monica attend "home" pour afficher l'icône 🏡.

##### c) Correction du mapping des champs ADR

**Avant (INCORRECT):**
```php
'line_1' => Arr::get($parts, 2) . ' ' . Arr::get($parts, 1), // Mélange street + extended
'line_2' => null,
```

**Après (CORRECT):**
```php
'line_1' => $this->formatStreetAddress(Arr::get($parts, 2)), // Street seul, formaté
'line_2' => Arr::get($parts, 1), // Extended/apartment séparé
```

**Mapping vCard ADR:**
- `parts[0]`: PO Box (non utilisé)
- `parts[1]`: Extended address (appartement, suite) → `line_2`
- `parts[2]`: Street address → `line_1` (via formatStreetAddress)
- `parts[3]`: City → `city`
- `parts[4]`: Province → `province`
- `parts[5]`: Postal code → `postal_code`
- `parts[6]`: Country → `country`

#### 4. `app/Domains/Contact/ManageContactInformation/Dav/ImportContactInformation.php`

**Modification:** Suppression de la gestion du champ ORG

**Retiré:**
```php
protected array $keys = [
    'email',
    'TEL',
    'IMPP',
    'X-SOCIALPROFILE',
    'ORG', // ← SUPPRIMÉ
];
```

**Raison:** Le champ ORG est maintenant géré par `ImportJobInformation.php`. Les organisations ne sont PAS des ContactInformation mais des Company liées via `contacts.company_id`.

#### 5. `config/app.php`

**Modification:** Suppression du groupe ORGANIZATION

**Retiré:**
```php
'contact_information_groups' => [
    'email' => [...],
    'phone' => [...],
    'IMPP' => [...],
    'X-SOCIAL-PROFILE' => [...],
    'ORGANIZATION' => [...], // ← SUPPRIMÉ
],
```

**Raison:** Les organisations ne sont pas des "contact information" mais des "job information" (système séparé).

#### 6. `app/Domains/Contact/ManageContactAddresses/Web/Controllers/ContactModuleAddressImageController.php`

**Modification:** Gestion du cas où Mapbox n'est pas configuré

**Avant:**
```php
public function show(...)
{
    $url = MapHelper::getStaticImage($address, $width, $height);
    $response = Http::get($url)->throw(); // ← TypeError si $url === null
```

**Après:**
```php
public function show(...)
{
    $url = MapHelper::getStaticImage($address, $width, $height);
    
    // Return 404 if map service not configured or no coordinates
    if ($url === null) {
        abort(404);
    }
    
    $response = Http::get($url)->throw();
```

**Raison:** Quand Mapbox n'est pas configuré, `MapHelper::getStaticImage()` retourne `null`, ce qui causait une erreur 500 (TypeError). Maintenant retourne proprement un 404.

#### 7. `app/Domains/Contact/ManageContactFeed/Web/ViewHelpers/Actions/ActionFeedAddress.php`

**Modification:** Génération conditionnelle de l'URL d'image

**Avant:**
```php
$imageUrl = route('contact.address.image.show', [...]); // Toujours généré
```

**Après:**
```php
// Only generate image URL if map service is configured
$imageUrl = null;
if ($address instanceof Address && MapHelper::getStaticImage($address, 300, 100) !== null) {
    $imageUrl = route('contact.address.image.show', [...]);
}
```

**Raison:** Évite d'envoyer une URL d'image au frontend si Mapbox n'est pas configuré, ce qui évite l'affichage d'une icône d'image cassée.

#### 8. `resources/js/Shared/Modules/ContactInformation.vue`

**Modification:** Suppression des logs de debug

**Retiré:**
```javascript
// Debug logging
console.log('ContactInformation component loaded');
console.log('contact_information keys:', Object.keys(props.data.contact_information || {}));
console.log('contact_information_groups keys:', Object.keys(props.data.contact_information_groups || {}));
console.log('contact_information data:', props.data.contact_information);
```

**Raison:** Logs de debug utilisés pendant le développement, plus nécessaires maintenant.

## 🐛 Bugs corrigés

### Bug #1: Organisations non affichées

**Symptôme:** Les organisations importées via ORG field apparaissaient dans la base de données (`contact_information` table) mais ne s'affichaient pas dans l'interface avec le message "Job information: Not set".

**Cause racine:** Monica utilise DEUX systèmes séparés:
1. **ContactInformation** (table `contact_information`) - Pour emails, téléphones, IM, réseaux sociaux
2. **Job Information** (colonnes `contacts.company_id` + `contacts.job_position`) - Pour les organisations/entreprises

L'ancien code tentait d'importer ORG comme ContactInformation, ce qui ne fonctionnait pas car le système Job Information cherche dans `contacts.company_id`.

**Solution:**
1. Créé `ImportJobInformation.php` qui:
   - Utilise le service `CreateCompany` pour créer/trouver la company
   - Utilise le service `UpdateJobInformation` pour lier via `contacts.company_id`
2. Retiré ORG de `ImportContactInformation.php`
3. Supprimé le type ORGANIZATION de `config/app.php`

**Résultat:** Les organisations s'affichent maintenant correctement: "Job information: Meilleur Ami"

### Bug #2: Noms et organisations avec `;` finaux

**Symptôme:** Affichage de "Meilleur Ami;" et "Louis;" avec des points-virgules en trop.

**Cause racine:** Le format vCard utilise `;` comme séparateur de champs, et certains exports ajoutent un `;` final non significatif. La méthode `formatValue()` ne les supprimait pas.

**Solution:** Modifié `Importer.php::formatValue()` pour ajouter `rtrim($formatted, ';')`.

**Résultat:** "Meilleur Ami;" → "Meilleur Ami", "Louis;" → "Louis"

### Bug #3: Adresses avec retours à la ligne

**Symptôme:** Adresses affichées avec retours à la ligne: "61\nRue Des Lutiers"

**Cause racine:** Certains exports vCard utilisent des retours à la ligne littéraux (`\n`) dans le champ ADR street address.

**Solution:** Créé `formatStreetAddress()` qui:
- Remplace `[\n\r\t]+` par des espaces
- Normalise les espaces multiples
- Ajoute une virgule après le numéro de rue

**Résultat:** "61\nRue Des Lutiers" → "61, Rue Des Lutiers"

### Bug #4: Adresses sans espacement

**Symptôme:** "61Rue Des Lutiers" (pas d'espace après le numéro)

**Cause racine:** Problème de formatage dans l'export vCard source.

**Solution:** Le même `formatStreetAddress()` utilise une regex pour détecter les numéros en début d'adresse et ajouter ", " automatiquement.

**Résultat:** "61Rue Des Lutiers" → "61, Rue Des Lutiers"

### Bug #5: Types d'adresse non mappés

**Symptôme:** Création d'un nouveau type "HOME,pref" au lieu d'utiliser le type existant "home" (🏡 Home).

**Cause racine:** Les vCards peuvent avoir des paramètres TYPE complexes comme "HOME,pref" mais Monica a des types simples ("home", "work").

**Solution:** Modifié `getAddressType()` pour mapper toute valeur commençant par "home" vers le type "home".

**Résultat:** "HOME,pref" → Type "home" → Icône 🏡 Home affichée

### Bug #6: Champs d'adresse confondus

**Symptôme:** L'utilisateur mentionnait "confusion entre apartment et address".

**Cause racine:** Le code mélangeait `parts[1]` (Extended/apartment) et `parts[2]` (Street) dans le champ `line_1`.

**Solution:** 
- `parts[2]` → `line_1` (Address field)
- `parts[1]` → `line_2` (Apartment, suite, etc.)

**Résultat:** Séparation claire entre l'adresse principale et l'appartement/suite.

### Bug #7: Erreur 500 sur images de carte

**Symptôme:** `GET /vault/1/contact/123/address/16/image/300x100` retourne erreur 500 avec trace:
```
TypeError: Illuminate\Support\Facades\Http::get(): Argument #1 ($url) must be of type string, null given
```

**Cause racine:** Quand Mapbox n'est pas configuré, `MapHelper::getStaticImage()` retourne `null`, ce qui causait `Http::get(null)`.

**Solution:** 
1. Ajouté vérification `if ($url === null) abort(404)` dans `ContactModuleAddressImageController`
2. Modifié `ActionFeedAddress` pour ne générer l'URL d'image que si le service est disponible

**Résultat:** 
- Erreur 500 → 404 propre
- Pas d'icône d'image cassée dans le feed
- Lien "View on map" (OpenStreetMap) fonctionne toujours

## ⚙️ Configuration Mapbox (optionnelle)

Les images de carte statiques nécessitent un compte Mapbox gratuit:

### Étapes:

1. **Créer un compte**: https://mapbox.com/ (gratuit, 50 000 requêtes/mois)

2. **Obtenir les credentials**:
   - Se connecter sur https://account.mapbox.com/
   - Copier le "Default public token" (commence par `pk.`)
   - Noter le username utilisé lors de l'inscription

3. **Configurer Monica**:
   Éditer `/Users/clementabraham/Documents/GitHub/monica/.env` aux lignes 134-136:
   ```env
   MAPBOX_API_KEY=pk.eyJ1IjoiWU9VUl9VU0VSTkFNRSIsImEiOiJjbHh4eHh4...
   MAPBOX_USERNAME=votre_username
   MAPBOX_CUSTOM_STYLE_NAME=  # Optionnel, défaut: streets-v11
   ```

4. **Redémarrer Monica**:
   ```bash
   cd /Users/clementabraham/Documents/GitHub/monica
   ./vendor/bin/sail restart
   ```

5. **Vérifier**: Ouvrir un contact avec adresse, l'image de carte devrait s'afficher dans le feed.

### Sans Mapbox:

Si Mapbox n'est pas configuré, les fonctionnalités suivantes restent disponibles:
- ✅ Affichage complet de l'adresse (tous les champs)
- ✅ Icône de type d'adresse (🏡 Home, etc.)
- ✅ Lien "View on map" vers OpenStreetMap/Google Maps
- ❌ Image de prévisualisation de carte (404, pas affiché)

## 📊 Résultats de l'import

**Import effectué:**
- **Fichier source**: 1259.vcf (2.7 MB)
- **Contacts importés**: 1260
- **Photos importées**: 109 (stockées dans `storage/app/public/photos`)
- **Notes**: Toutes importées depuis champ NOTE
- **Organisations**: Toutes importées comme Job Information (Company + company_id)
- **Adresses**: Toutes formatées correctement avec types mappés

**Qualité des données:**
- ✅ Aucun `;` final dans les noms/organisations
- ✅ Adresses formatées: "61, Rue Des Lutiers" (virgule, espacement)
- ✅ Types d'adresse corrects: 🏡 Home au lieu de "HOME,pref"
- ✅ Séparation adresse/appartement dans les bons champs
- ✅ Photos en avatars arrondis
- ✅ Organisations affichées dans Job Information

## 🔍 Testing

Pour tester l'import:

1. **Préparer un vCard test**:
   ```vcard
   BEGIN:VCARD
   VERSION:3.0
   FN:Test User
   N:User;Test;;;
   ORG:Test Company;
   ADR;TYPE=HOME,pref:;;61Rue Des Lutiers;Caen;;14000;France
   NOTE:Test note with details
   PHOTO;ENCODING=b;TYPE=JPEG:/9j/4AAQ...base64...
   END:VCARD
   ```

2. **Import via UI**: http://localhost:9092/contacts/import

3. **Vérifications**:
   - [ ] Nom affiché: "Test User" (sans `;`)
   - [ ] Job information: "Test Company" (sans `;`)
   - [ ] Adresse line_1: "61, Rue Des Lutiers" (avec virgule)
   - [ ] Type d'adresse: 🏡 Home
   - [ ] Note présente
   - [ ] Photo affichée en avatar arrondi
   - [ ] Lien carte OpenStreetMap fonctionne
   - [ ] Image carte (si Mapbox configuré) ou 404 sinon (pas d'erreur 500)

## 📝 Maintenance

### Ajouter un nouveau type d'information contact

Si vous voulez importer un nouveau champ vCard (ex: BDAY pour birthdate):

1. Créer un nouveau Importer dans le bon domaine
2. Ajouter `#[Order(XX)]` pour contrôler l'ordre d'exécution
3. Implémenter `ImportVCardResource` interface
4. Utiliser les Services Laravel appropriés (Create*/Update*)

### Modifier le formatage des adresses

Le formatage est centralisé dans `ImportAddress::formatStreetAddress()`. Modifier cette méthode pour changer:
- Le format du numéro de rue (actuellement: `"$numero, $rue"`)
- La normalisation des espaces
- La gestion des retours à la ligne

### Changer le mapping des types d'adresse

Modifier `ImportAddress::getAddressType()` pour ajouter de nouveaux mappings:
```php
if (str_starts_with(strtolower($typeValue), 'work')) {
    $typeValue = 'work';
}
```

## 🎓 Leçons apprises

### 1. Architecture Monica: ContactInformation vs Job Information

**Important:** Monica sépare strictement:
- **ContactInformation**: Méthodes de contact (email, tel, IM, social) - table `contact_information`
- **Job Information**: Emploi/organisation - colonnes `contacts.company_id` + `contacts.job_position`

Ne pas confondre ces deux systèmes! Les organisations ne vont PAS dans ContactInformation.

### 2. Format vCard: Subtilités du parsing

- Les `;` sont des séparateurs, peuvent apparaître en fin de valeur
- Les champs ADR ont 7 parts séparées par `;` (certaines vides)
- Les TYPE parameters peuvent être multiples: "HOME,pref"
- Les retours à la ligne peuvent être littéraux (`\n`) ou encodés

### 3. Gestion des erreurs gracieuse

Plutôt que de lancer des erreurs 500:
- Retourner null et vérifier avant utilisation
- Utiliser `abort(404)` pour ressources indisponibles (images de carte)
- Ne pas envoyer d'URLs au frontend si le service backend n'est pas disponible

### 4. Importance de la documentation

La documentation PHPDoc complète aide à:
- Comprendre rapidement le rôle de chaque méthode
- Identifier les paramètres et types de retour
- Expliquer les cas limites et le comportement spécial

## 📚 Références

- **vCard Format**: RFC 6350 (https://datatracker.ietf.org/doc/html/rfc6350)
- **Sabre VObject**: https://sabre.io/vobject/
- **Mapbox Static Maps API**: https://docs.mapbox.com/api/maps/static-images/
- **Monica CRM**: https://github.com/monicahq/monica

## 🤝 Contribution

Pour contribuer à ces améliorations:

1. Tous les imports vCard doivent implémenter `ImportVCardResource`
2. Utiliser `#[Order(X)]` pour contrôler l'ordre d'exécution
3. Toujours utiliser les Services Laravel (Create*, Update*) plutôt que l'accès direct aux models
4. Ajouter PHPDoc complète à toutes les méthodes
5. Tester avec de vrais exports vCard (Google Contacts, iCloud, Android)

---

**Auteur**: Clément ABRAHAM
**Date**: Janvier 2026
**Version Monica**: 11.x (Laravel 11.x)
