# Migration Spring Boot vers Laravel

## Cible technique

- Laravel 12 (compatible avec la cible Hostinger documentée en PHP 8.2)
- MariaDB/MySQL à la place de PostgreSQL
- Préfixe HTTP conservé : `/api/v1`
- JWT HS256 compatible avec le frontend Angular : claims `sub`, `roles`, `iat`, `exp`
- Noms JSON conservés en camelCase (`accountType`, `dateEntree`, etc.)
- Pagination reproduite au format Spring Data (`content`, `totalElements`, `totalPages`, `number`, etc.)

## Conventions Laravel retenues

- Les anciennes classes Java `*Request` et validators sont représentées par des `FormRequest` Laravel.
- Les DTO Java de sortie sont représentés par des `JsonResource` Laravel.
- Eloquent remplace les repositories JPA ; aucun repository artificiel n’est ajouté sans besoin métier réel.
- Les modèles ne sérialisent pas eux-mêmes les réponses HTTP.
- Les transactions restent explicites pour les agrégats qui créent ou remplacent plusieurs enregistrements.
- Le format d’erreur historique est conservé globalement pour ne pas casser la traduction Angular.

## Domaines identifiés

1. Authentification, utilisateurs et rôles
2. Résidences et ressources
3. Pavillons et chambres
4. Accueillants et responsables
5. Assignments et rotations
6. Délégations et invités
7. Événements et réservations
8. Statistiques et exports Excel/PDF

## Contrat déjà implémenté

- `POST /api/v1/auth/login`
- `GET|POST /api/v1/utilisateurs`
- `GET /api/v1/utilisateurs/info`
- `GET /api/v1/utilisateurs/{id}`
- `PUT /api/v1/utilisateurs/statut/{id}`
- `PUT /api/v1/utilisateurs/{id}/password`
- `PUT /api/v1/utilisateurs/me/password`
- `GET /api/v1/roles`
- `GET|POST|PUT /api/v1/residences`
- `GET /api/v1/residences/{id}`
- `GET /api/v1/residences/responsable/{username}`
- `GET /api/v1/ressources/{id}`
- `GET|POST /api/v1/pavillons`
- `PUT /api/v1/pavillons/{id}`
- `GET /api/v1/pavillons/residence/{id}`
- `GET|POST|PUT /api/v1/chambres`
- `GET /api/v1/chambres/pavillon/{id}`
- `GET /api/v1/chambres/residence/{id}/disponible/{debut}/{fin}`
- `GET|POST /api/v1/assignments`
- `GET|PUT|DELETE /api/v1/assignments/{id}`
- `GET /api/v1/assignments/agent/{agentId}`
- `GET|POST|PUT /api/v1/accueillants`
- `GET|DELETE /api/v1/accueillants/{id}`
- `GET /api/v1/accueillants/user/{username}`
- `GET|POST /api/v1/responsables`
- `GET|DELETE /api/v1/responsables/{id}`
- `GET|POST|PUT /api/v1/delegations`
- `GET|DELETE /api/v1/delegations/{id}`
- `POST /api/v1/invites`
- `GET|POST /api/v1/evenements`
- `GET|DELETE /api/v1/evenements/{id}`
- `GET|POST|PUT /api/v1/reservations`
- `GET|DELETE /api/v1/reservations/{id}`
- `GET /api/v1/reservations/pavillon/{pavillon}/{debut}/{fin}`
- `GET /api/v1/reservations/exportation/residence/{residence}`
- `GET /api/v1/reservations/exportation/pdf/residence/{residence}`
- `GET /api/v1/stats/{residence}`
- `GET /api/v1/stats/{residence}/chambres`

## Différences de base à traiter

- Les identités PostgreSQL deviennent des `BIGINT UNSIGNED AUTO_INCREMENT`.
- Les `bytea` des ressources deviennent des fichiers privés sur disque avec un chemin en base. L’endpoint binaire historique reste inchangé.
- Les enums Java sont stockés comme chaînes contrôlées.
- Les tables de jointure et contraintes de suppression doivent être déclarées explicitement.
- Les dates doivent être normalisées en UTC et sérialisées comme l’API Spring actuelle.

## Ordre de migration restant

Les domaines fonctionnels utilisés par l'application Angular sont maintenant migrés verticalement : migrations SQL, modèles, validation, contrôleurs, routes et tests de compatibilité. La prochaine étape est la préparation opérationnelle du déploiement Hostinger et la recette Angular complète.

## Vérifications du lot hébergement

- Migrations et relations validées par la suite SQLite en mémoire.
- Création multipart et lecture binaire d’image validées avec un disque simulé.
- Références automatiques et pagination Spring validées.
- Une validation MariaDB réelle reste à exécuter lorsque Docker Desktop ou un serveur MariaDB local est disponible.
- Le calcul des places occupées de la route `disponible` est branché sur les réservations qui chevauchent la période demandée.

## Vérifications du lot équipes

- Création et remplacement transactionnels des responsabilités et créneaux de rotation.
- Recherche et pagination Spring des assignments, accueillants et responsables.
- Métadonnées `hasAssignment` et `assignedResidenceName` exposées pour le sélecteur Angular des agents.
- Création d’un compte `KHIDMA_AGENT` lors de la création d’un accueillant, avec mot de passe initial historique `test`.
- Suppression en cascade du compte associé lorsqu’un accueillant est supprimé.
- Liaison plusieurs-à-plusieurs entre responsables et chambres validée.

## Vérifications du lot délégations et événements

- Création transactionnelle d'une délégation, de son chef et de ses invités.
- Validation des payloads par des `FormRequest`, y compris l'unicité des téléphones.
- Sérialisation du chef, des invités et de leur délégation par des `JsonResource`.
- Pagination Spring, mise à jour de la délégation et suppression en cascade validées.
- Création et mise à jour des événements avec la méthode `POST` attendue par l'application Angular.
- Trois événements initiaux sont ajoutés par le seeder de manière idempotente.

## Vérifications du lot réservations

- Création groupée et transactionnelle d'une réservation par invité sélectionné.
- Contrôle atomique de la capacité des chambres pour empêcher le surbooking.
- Mise à jour des dates, de la chambre, de l'accueillant, du responsable et de la présence.
- Filtres par année, événement, résidence et présence avec pagination Spring.
- Recherche des réservations par période et pavillon.
- Calcul des places réservées et exclusion des chambres pleines dans la route de disponibilité.
- Sérialisation du graphe métier par `ReservationResource`, sans DTO ajouté aux modèles Eloquent.

## Vérifications du lot statistiques et exports

- Totaux par résidence pour les pavillons, chambres, délégations distinctes et réservations.
- Nombre de chambres disponibles par pavillon sur les trente prochains jours.
- Export XLSX natif avec en-têtes, styles, largeur automatique et sens RTL en arabe.
- Export PDF natif en paysage avec tableau bilingue français/arabe.
- Filtres d'export compatibles Angular : année, événement, présence et langue.
- Noms de fichiers et en-têtes HTTP de téléchargement validés.
