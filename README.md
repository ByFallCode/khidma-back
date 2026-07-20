# Moukhadimatoul Khidma API — Laravel

Réécriture progressive de l’API Spring Boot pour un déploiement sur un hébergement Web/Cloud Hostinger.

## Prérequis

- PHP 8.2 ou supérieur
- Composer 2
- Extensions PHP `zip`, `gd`, `mbstring`, `dom` et `xml`
- MariaDB/MySQL en production

Avec XAMPP sous Windows, vérifier notamment que `extension=zip` n'est pas commentée dans `C:\xampp\php\php.ini`, puis redémarrer le terminal et Apache si nécessaire.

## Installation locale

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Renseigner ensuite la connexion locale dans `.env` :

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mkdatabase
DB_USERNAME=root
DB_PASSWORD=
JWT_SECRET=une-cle-secrete-longue-et-aleatoire
```

Créer le schéma et le compte administrateur initial :

```powershell
php artisan migrate --seed
php artisan serve --port=8081
```

L’API répond sur `http://127.0.0.1:8081/api/v1`.

Compte de développement initial :

```text
Utilisateur : 777197482
Mot de passe : admin
```

Le mot de passe doit être changé après la première connexion sur un environnement partagé.

## Tests

La suite utilise SQLite en mémoire et n’écrase pas la base de développement :

```powershell
php artisan test
vendor\bin\pint --test
composer audit --locked
```

## Architecture Laravel

- `app/Http/Requests/Api` contient les `FormRequest` : règles de validation et codes d’erreur métier.
- `app/Http/Resources` contient les `JsonResource` : contrat JSON compatible avec Angular.
- `app/Models` contient uniquement les modèles Eloquent, leurs relations, casts et attributs persistables.
- `app/Http/Controllers/Api` orchestre les cas d’usage HTTP avec injection typée des requests.
- `app/Support` contient les conventions transversales, notamment la pagination compatible Spring pendant la migration.

Les nouveaux modules ne doivent pas ajouter de méthode de type `toDto`, `fromDto` ou `toApiArray` aux modèles. La validation ne doit pas être déclarée directement dans les contrôleurs lorsqu’un payload métier est reçu.

## Déploiement Hostinger

1. Créer une base MariaDB/MySQL dans hPanel.
2. Vérifier dans la configuration PHP Hostinger que les extensions `zip`, `gd`, `mbstring`, `dom` et `xml` sont actives.
3. Copier `.env.example` vers `.env` et renseigner les valeurs de production.
4. Utiliser une valeur unique et secrète pour `APP_KEY` et `JWT_SECRET`.
5. Définir `APP_ENV=production` et `APP_DEBUG=false`.
6. Faire pointer le document root du domaine vers le dossier `public`.
7. Exécuter `composer install --no-dev --optimize-autoloader`.
8. Exécuter `php artisan migrate --force` puis `php artisan db:seed --force` uniquement lors de l’installation initiale.
9. Exécuter `php artisan optimize`.
10. Vérifier les droits d’écriture sur `storage` et `bootstrap/cache`.

### Mise en recette

La recette doit utiliser une base et des secrets distincts de la production. Sur le serveur :

```bash
cp .env.recette.example .env
php artisan key:generate
```

Renseigner ensuite `APP_URL`, `CORS_ALLOWED_ORIGINS`, les paramètres `DB_*` et une valeur
`JWT_SECRET` longue et aléatoire. Ne jamais envoyer le fichier `.env` local sur le serveur.

Pour chaque livraison en recette :

```bash
php artisan down
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
php artisan up
```

Le document root du sous-domaine d’API doit pointer vers `public`. Vérifier ensuite
`GET /up`, puis `POST /api/v1/auth/login` avec un compte de recette.

Le seeder crée un compte de développement (`777197482` / `admin`). Ne pas exécuter
`php artisan db:seed` sur un serveur accessible publiquement sans changer immédiatement
ce mot de passe.

Consulter [MIGRATION.md](MIGRATION.md) pour l’état fonctionnel de la migration.
