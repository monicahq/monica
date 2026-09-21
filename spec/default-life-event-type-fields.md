# Champs proposés pour les types d’activité fournis

**Statut : proposition de contenu, sans création automatique dans les comptes.** Les 40 types et leurs catégories suivent le [catalogue spécifié](models/035-life-event-type-in-progress.md#supplied-types). Ce document décrit les champs que je fournirais avec chacun d’eux.

## Règles de composition

- Le [modèle Life event prévu](models/036-life-event-not-yet.md) porte la date ou période, l’heure, la durée, le titre, le récit, le lieu et les participants. Ces informations ne sont pas recréées en champs personnalisés.
- Les champs listés directement sous un type sont **sans groupe**. Un groupe nommé n’est proposé que si deux ensembles distincts rendent le formulaire plus lisible. Un type marqué **aucun champ** fonctionne avec les informations communes à toute activité.
- Un champ **Note** (`textarea`) facultatif accompagne tous les types, sauf Réflexion, Souvenir et Autre activité : pour ces trois types, le récit général joue déjà ce rôle.
- Tous les champs ci-dessous sont **facultatifs** au départ. Les listes d’un champ `select` sont propres à ce champ, conformément à l’éditeur actuel. Les libellés et choix français sont des propositions éditoriales à traduire avant de fournir le catalogue dans plusieurs langues.
- Les types de champs employés existent dans `FieldTypeEnum` et sont proposés dans le formulaire actuel : `text`, `textarea`, `number`, `boolean`, `select`, `date`, `file`, `image`. Pour une note sur 5, `number` reçoit `min: 1`, `max: 5`, `decimals: 0`. Le type `rating` du catalogue visuel n’est pas disponible dans le modèle.
- Les champs de documents ou de photos désignent un fichier chacun. Plusieurs fichiers ne sont pas présumés pris en charge par un seul champ.

## Relations et famille

### Appel (Call)

- Canal — `select` : téléphone, vidéo, appel vocal par application.
- Sens — `select` : entrant, sortant.
- Note — `textarea` : point à retenir de l’échange.

### Message (Message)

- Canal — `select` : SMS, courriel, messagerie, réseau social, autre.
- Sens — `select` : envoyé, reçu.
- Note — `textarea` : contexte ou suite à donner après l’échange.

### Repas (Meal)

- Repas — `select` : petit-déjeuner, déjeuner, dîner, collation.
- Occasion — `text`.
- Note — `textarea` : détail du repas ou de la conversation à retenir.

### Visite (Visit)

- Motif — `text`.
- Note — `textarea` : observation à conserver après la visite.

### Moment ensemble (Time together)

- Note — `textarea` : moment ou échange à retenir.

### Dispute (Argument)

- Sujet du désaccord — `text`.
- Situation apaisée — `boolean`.
- Note — `textarea` : point à reprendre ou résolution convenue.

### Mariage (Marriage)

- Forme de cérémonie — `select` : civile, religieuse, autre.
- Document — `file`.
- Note — `textarea` : détail marquant de l’événement.

### Naissance (Birth)

- Note — `textarea` : circonstance ou souvenir de l’annonce.

### Séparation (Separation)

- Note — `textarea` : détail à conserver sur la séparation.

## Travail et études

### Réunion (Meeting)

**Groupe « Préparation »**

- Ordre du jour — `textarea`.

**Groupe « Résultats »**

- Décisions — `textarea`.
- Actions convenues — `textarea`.
- Note — `textarea` : observation qui ne relève ni des décisions ni des actions.

### Entretien (Interview)

**Groupe « Poste »**

- Intitulé — `text`.
- Organisation — `text`.

**Groupe « Processus »**

- Étape — `select` : premier échange, entretien, exercice, entretien final, autre.
- Retour reçu — `textarea`.
- Note — `textarea` : impression personnelle sur l’entretien.

### Nouvel emploi (New job)

- Intitulé du poste — `text`.
- Employeur — `text`.
- Mode d’exercice — `select` : salarié, indépendant, autre.
- Note — `textarea` : information utile sur le début de ce travail.

### Cours (Class)

- Cours ou matière — `text`.
- Enseignant — `text`.
- Support — `file`.
- Note — `textarea` : notion apprise ou point à revoir.

### Formation (Training)

- Programme — `text`.
- Organisme — `text`.
- Attestation — `file`.
- Note — `textarea` : acquis ou piste à appliquer.

### Diplôme (Graduation)

- Diplôme ou qualification — `text`.
- Établissement — `text`.
- Justificatif — `file`.
- Note — `textarea` : détail à retenir de cette étape.

## Santé et bien-être

### Rendez-vous médical (Medical appointment)

**Groupe « Consultation »**

- Motif — `text`.
- Professionnel — `text`.
- Note — `textarea` : observation personnelle sur la consultation.

**Groupe « Suite »**

- Consignes — `textarea`.
- Ordonnance ou compte rendu — `file`.
- Prochain contrôle — `date`.

### Thérapie (Therapy)

**Groupe « Séance »**

- Thérapeute — `text`.
- Thèmes abordés — `textarea`.
- Note — `textarea` : réflexion personnelle après la séance.

**Groupe « Suite »**

- Exercice ou piste à explorer — `textarea`.

### Maladie (Illness)

**Groupe « État de santé »**

- Diagnostic — `text`.
- Symptômes — `textarea`.
- Note — `textarea` : évolution observée.

**Groupe « Prise en charge »**

- Traitement — `textarea`.
- Document médical — `file`.

### Hospitalisation (Hospital stay)

**Groupe « Séjour »**

- Établissement — `text`.
- Service — `text`.
- Motif — `text`.
- Note — `textarea` : information utile sur le séjour.

**Groupe « Documents »**

- Compte rendu — `file`.

### Exercice physique (Exercise)

- Discipline — `text`.
- Distance en kilomètres — `number` (`unit: km`, `min: 0`).
- Intensité — `select` : douce, modérée, intense.
- Note — `textarea` : ressenti ou condition particulière.

## Maison et quotidien

### Déménagement (Move)

- Note — `textarea` : détail pratique à conserver.

**Groupe « Adresses »**

- Ancienne adresse — `text`.
- Nouvelle adresse — `text`.

**Groupe « Documents »**

- Justificatif ou contrat — `file`.

### Travaux (Home improvement)

**Groupe « Projet »**

- Pièce ou espace — `text`.
- Travaux effectués — `textarea`.
- Prestataire — `text`.
- Note — `textarea` : point à vérifier après les travaux.

**Groupe « Documents »**

- Facture — `file`.

### Achat important (Major purchase)

**Groupe « Achat »**

- Objet — `text`.
- Vendeur — `text`.
- Prix avec devise — `text`.
- Note — `textarea` : état, usage ou détail de l’achat.

**Groupe « Garantie »**

- Fin de garantie — `date`.
- Reçu ou garantie — `file`.

### Tâche domestique (Household task)

- Note — `textarea` : résultat ou problème constaté après la tâche.

## Loisirs et culture

### Restaurant (Restaurant)

- Plat ou menu — `text`.
- Appréciation sur 5 — `number` (`min: 1`, `max: 5`, `decimals: 0`).
- Photo — `image`.
- Note — `textarea` : impression sur le repas ou le service.

### Concert (Concert)

- Artiste — `text`.
- Morceau marquant — `text`.
- Billet — `file`.
- Note — `textarea` : impression à retenir du concert.

### Cinéma (Cinema)

- Film — `text`.
- Appréciation sur 5 — `number` (`min: 1`, `max: 5`, `decimals: 0`).
- Note — `textarea` : avis ou scène marquante.

### Lecture (Reading)

- Livre — `text`.
- Auteur — `text`.
- Pages lues — `number` (`min: 0`, `decimals: 0`).
- Terminé — `boolean`.
- Note — `textarea` : impression ou idée à retenir.

### Jeu (Game)

- Jeu — `text`.
- Forme — `select` : jeu de société, jeu vidéo, jeu de cartes, jeu de rôle, autre.
- Résultat — `text`.
- Note — `textarea` : impression ou moment marquant de la partie.

### Loisir (Hobby)

- Activité pratiquée — `text`.
- Projet — `text`.
- Réalisation — `image`.
- Note — `textarea` : progrès ou idée pour la prochaine fois.

## Voyages et expériences

### Voyage (Trip)

- Mode — `select` : voiture, train, avion, bateau, autre.
- Référence de réservation — `text`.
- Billet — `file`.
- Note — `textarea` : détail pratique ou expérience à retenir.

### Vacances (Holiday)

- Hébergement — `text`.
- Référence de réservation — `text`.
- Confirmation — `file`.
- Note — `textarea` : adresse utile ou conseil pour une prochaine visite.

### Randonnée (Hike)

- Distance en kilomètres — `number` (`unit: km`, `min: 0`).
- Dénivelé en mètres — `number` (`unit: m`, `min: 0`).
- Difficulté — `select` : facile, modérée, difficile.
- Trace — `file`.
- Note — `textarea` : état du sentier ou conditions rencontrées.

### Excursion (Excursion)

- Organisateur — `text`.
- Référence de réservation — `text`.
- Billet — `file`.
- Note — `textarea` : expérience ou information pratique à retenir.

## Personnel

### Réflexion (Reflection)

- Aucun champ. Le récit est précisément le contenu à conserver.

### Décision (Decision)

**Groupe « Choix »**

- Sujet — `text`.
- Options envisagées — `textarea`.
- Raison du choix — `textarea`.

**Groupe « Suivi »**

- Date de réévaluation — `date`.
- Note — `textarea` : constat lors de la réévaluation.

### Réussite (Achievement)

- Domaine — `text`.
- Résultat — `text`.
- Justificatif — `file`.
- Note — `textarea` : ce qui a permis cette réussite.

### Souvenir (Memory)

- Provenance du souvenir — `text`.
- Photo — `image`.

### Événement spirituel (Spiritual event)

- Tradition ou cadre — `text`.
- Cérémonie ou pratique — `text`.
- Note — `textarea` : ressenti ou signification personnelle.

## Autre

### Autre activité (Other life event)

- Aucun champ : ce type reste utilisable comme solution de repli.

## Points à trancher avant une création automatique

- **Portée des champs sensibles** : le formulaire des types d’activité ne permet actuellement pas de configurer `is_sensitive`. Les types de santé méritent une décision explicite sur ce point avant de fournir leurs champs par défaut.
- **Montants** : `money` apparaît dans le catalogue visuel, mais pas dans `FieldTypeEnum`. Le prix d’un achat reste donc un texte avec devise dans cette proposition.
- **Libellés et listes** : les 40 types, leurs groupes, champs et choix exigent des traductions si le compte doit être utilisable dans toutes les langues de l’application.
