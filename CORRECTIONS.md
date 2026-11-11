# Corrections – lot 1 (routes manquantes + format des données)

## À faire après avoir récupéré cette version
```bash
cd api
composer install
php artisan migrate          # 3 nouvelles migrations (actif, semestre/credits, actualites, programmes)
php artisan storage:link     # nécessaire pour afficher les programmes envoyés
php artisan serve            # http://127.0.0.1:8000

# dans un autre terminal, à la racine du projet
npm install
npx ng serve
```

## Ajouté côté API (Laravel)
| Route | Rôle |
|---|---|
| POST /api/register | Inscription étudiant (rôle forcé à « etudiant »), 5 essais/min |
| PUT /api/password | Changement du mot de passe (vérifie l'ancien) |
| GET/POST/DELETE /api/actualites, /api/admin/actualites | Actualités du tableau de bord |
| GET /api/programmes, POST|PUT /api/programmes | Images de programmes (5 Mo max, chef limité à sa filière) |
| GET/DELETE /api/moi/telechargements | Historique personnel (vider = masquer, l'audit admin reste) |
| DELETE /api/admin/modules/{id} | Refusé (409) si le module contient des archives |
| PATCH/DELETE /api/admin/users/{id} | Rôle, activer/désactiver, suppression ; impossible sur son propre compte |

## Modifié
- Téléchargement d'archive : maintenant réservé aux connectés.
- Connexion : limitée à 5 essais/min, refuse les comptes désactivés.
- Modules : stockent semestre et crédits ; la création accepte les noms de filière/niveau envoyés par le front.
- Création de compte : le serveur génère un mot de passe temporaire, affiché une seule fois à l'admin.
- GET /admin/users renvoie le format du front (prenom, nom, identifiant, role, actif).
- Types d'archive renvoyés en « Devoir / Test / Épreuve de session » ; dates en jj/mm/aaaa.

## Modifié côté front
- Un intercepteur ajoute le jeton partout et renvoie vers la connexion sur 401.
- `donnees.ts` ne contient plus de comptes factices ni de repli local silencieux.
- Inscription, changement de mot de passe, programmes, mes documents, utilisateurs : branchés sur l'API.
- Aperçu et téléchargement passent par le jeton.
- Adresse de l'API centralisée dans `src/app/config.ts`.

## Pas encore testé
Le code PHP n'a pas été exécuté (PHP indisponible là où il a été écrit). Le front compile (`ng build`).

---

# Lot 2 – sécurité du compte admin, archives, accès

## Compte administrateur
- Plus de mot de passe admin écrit dans le code : `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` se règlent dans `api/.env`.
- Hors environnement local, sans `ADMIN_PASSWORD`, un mot de passe aléatoire est généré et affiché une seule fois au `db:seed`.
- Relancer `db:seed` ne réinitialise plus aucun mot de passe existant.
- Les comptes de démonstration (chef.info, etudiant) ne sont créés qu'en local.
- Il peut y avoir plusieurs admins : l'admin en crée d'autres depuis la page Utilisateurs.

## Archives
- En local, `php artisan db:seed` crée 8 archives de démonstration avec de vrais PDF (stockés dans `api/storage/app/private/archives`).
- Les vraies archives se déposent via « Ajouter une archive » (chef de filière ou admin).

## Accès
- Archives, modules, actualités et programmes exigent maintenant une connexion.
- CORS limité à `FRONTEND_URL` (par défaut http://localhost:4200).
- Le jeton expire après `SANCTUM_EXPIRATION` minutes (480 par défaut).
- Le front ne charge rien sans session et vide ses données à la déconnexion.

## Commandes
```bash
cd api
cp .env.example .env          # si .env n'existe pas encore, puis php artisan key:generate
php artisan migrate:fresh --seed   # repart d'une base propre avec les archives de démo
```

---

# Lot 3 – inscription

- Après l'inscription, l'étudiant est connecté automatiquement et arrive sur le tableau de bord (plus de passage par la page de connexion).
- `POST /api/register` renvoie maintenant un jeton, au même format que `/login`.
