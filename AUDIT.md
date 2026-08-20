# Audit du projet giga-memo

Audit réalisé sur l'intégralité de `/src` (32 fichiers), `/templates` (44 fichiers), `/config`, `/assets`, `/tests` et les migrations.

## 1. Architecture & Structure

**Points positifs**
- Organisation par domaine métier (`Controller/Categories`, `Controller/Couples`, `Controller/Faqs`...) plutôt qu'un dossier `Controller` plat — bonne lisibilité.
- Utilisation cohérente de `#[MapEntity]` pour éviter le pattern `find()` + `if (!$entity) throw 404` dans chaque contrôleur.
- Le `ResourceOwnerVoter` (src/Security/Voter/ResourceOwnerVoter.php) est une bonne abstraction : un seul voter générique pour toutes les entités possédant `getUser()`, au lieu de dupliquer la logique d'autorisation.
- Le trait `HasUserTrait` (src/Entity/Trait/HasUserTrait.php) évite la duplication du champ `user` sur 4 entités.
- Séparation service/entité correcte pour l'upload d'images (`PictureService`) découplée du contrôleur.

**Points à corriger**
- **Incohérence forte entre AssetMapper et JS "à l'ancienne"** : le projet utilise Stimulus/AssetMapper pour presque tout (modales de suppression, aperçu image, select dynamique), mais `public/assets/js/scripts.js` (206 lignes, sélecteurs `getElementById`, IIFE) gère à lui seul les boutons Restart / Reset Review / A Revoir / Ne plus revoir, chargé en `<script>` brut dans `templates/base.html.twig` (ligne 23) au lieu de passer par l'importmap. C'est un corps étranger dans une architecture par ailleurs moderne — devrait être un contrôleur Stimulus comme les autres.
- **Fichier `public/assets/images/` utilisé comme dossier de stockage utilisateur** (config/services.yaml, ligne 8) alors que `public/assets/` est le répertoire de sortie compilé d'AssetMapper. Risque de collision/purge lors d'un `asset-map:compile` ou d'un déploiement qui régénère ce dossier — les images utilisateur devraient être hors de l'arborescence gérée par AssetMapper (ex. `var/uploads/` servi via un contrôleur, ou au minimum un sous-dossier `public/uploads/` clairement séparé).
- `PictureService` a une double responsabilité (upload + suppression physique) mais reste correct en taille ; en revanche `$params` n'est pas typé (`private $params;` au lieu de `private ParameterBagInterface $params;`).
- `AppFixtures` crée des `Faqs`/`Couples` **sans utilisateur** alors que `HasUserTrait` impose `nullable: false` — ces fixtures cassent en base (contrainte NOT NULL) et sont décorrélées du modèle actuel : dette à nettoyer.
- Fichier de test `tests/Controller/DeleteCascadeCategoryTest.php` contient une classe nommée `DeleteCategoryTest` (incohérence nom fichier/classe) et se trouve dans `tests/Controller` alors qu'il ne teste aucun contrôleur HTTP (juste l'EntityManager) — plutôt un test d'intégration Doctrine, à ranger dans `tests/Entity` ou `tests/Integration`.
- Structure `tests/entity/tests/Entity/...` (double niveau `tests/`) : artefact probable d'un copier-coller, à corriger.

## 2. Performance & Optimisations

**Problèmes N+1 identifiés**
- `templates/couples/list-by-faq.html.twig` (lignes 30-52) itère `faq.couples`, puis pour chaque couple boucle sur `couple.images` et `couple.rules` — sans `JOIN FETCH` en amont, Doctrine émet 1 requête pour charger les couples + N requêtes pour les images + N requêtes pour les règles. Sur une FAQ à 50 couples, c'est ~100 requêtes SQL pour une seule page.
- Aucun repository ne définit de méthode `findByFaqWithImagesAndRules()` utilisant `leftJoin()->addSelect()` — toutes les collections `OneToMany`/`ManyToMany` sont chargées en lazy loading pur.
- ~~`CouplesRepository` fait 4 requêtes séparées (`countTodoRun`, `countTodoReview`, `countSelectRun`, `countSelectReview`) à chaque affichage de `run`/`review`/`restart`/`reset-review`~~ **Corrigé** : fusionnées en une seule méthode `CouplesRepository::countAll()` avec des agrégats conditionnels (`SUM(CASE WHEN ...)`), qui renvoie un DTO `App\Dto\CouplesCounters`, réutilisée dans les 8 endroits qui en avaient besoin (`FaqsController`, `MainController`, `CouplesController`).
- `countAll()->selectRun` (ex-`countSelectRun()`, CouplesRepository.php) ne filtre toujours pas sur un flag `selectRun` malgré son nom — il compte tous les couples de la FAQ, ce qui reste trompeur (bug de nommage conservé volontairement pour l'instant, cf. section qualité, point 10).

**Cache**
- `cache.yaml` est laissé en configuration par défaut (filesystem, pas de pool dédié) — acceptable pour la taille actuelle, mais aucun cache de requêtes/résultats n'est mis en place explicitement en dehors du `when@prod` de `doctrine.yaml` (pools `cache.app`/`cache.system` corrects mais uniquement pour proxy/metadata, pas de result cache appliqué aux requêtes répétitives comme `countAll`).
- Pas d'utilisation de HTTP cache (`Cache-Control`, ESI) — non critique pour une appli authentifiée par utilisateur, donc acceptable ici.

**AssetMapper / Turbo / Stimulus**
- `symfony/ux-turbo` est présent dans `composer.json` mais **désactivé** dans `assets/controllers.json` (`turbo-core.enabled: false`) et non utilisé nulle part dans les templates — dépendance installée mais inexploitée : soit l'activer pour bénéficier des navigations accélérées (pertinent vu le nombre de redirections après chaque action), soit la retirer.
- Bootstrap CSS/JS et Bootstrap Icons sont chargés depuis un **CDN externe** (templates/base.html.twig, lignes 12 et 35-36) alors que le projet a AssetMapper en place — incohérent (perte de contrôle sur la disponibilité/versioning, appel réseau externe à chaque page, pas de Subresource Integrity homogène avec le reste). Ces libs devraient passer par l'importmap comme `@hotwired/stimulus`/`turbo`.
- Le fichier legacy `scripts.js` n'étant pas dans `assets/`, il **échappe au fingerprinting/versioning** d'AssetMapper (pas de cache-busting en prod).

## 3. Sécurité & Robustesse

**Points positifs**
- Firewall correctement configuré, tout le site derrière `ROLE_USER` sauf `/login` (config/packages/security.yaml, lignes 36-41).
- Le voter d'ownership est appliqué systématiquement sur les routes sensibles (edit/delete/view) via `#[IsGranted]`.
- CSRF activé sur le login et vérifié manuellement sur toutes les routes AJAX (delete, restart, reset-review, set/cancel-review).
- Upload d'images : validation MIME côté serveur via `getimagesize()` (pas de confiance dans l'extension fournie par le client), nom de fichier régénéré aléatoirement (`md5(uniqid())`) — protège contre l'exécution de scripts déguisés et le path traversal.
- `Users::__serialize()` hash le mot de passe en session (CRC32C) plutôt que de le stocker en clair — bonne pratique récente Symfony 7.3 correctement reprise.

**Failles / fragilités potentielles**
- **Endpoints AJAX fragiles face à une requête malformée** : dans `CouplesController::set_one_review`, `FaqsController::restart`, etc., le code fait `json_decode($request->getContent(), true)` puis `$data['_token']` sans vérifier que `$data` est un tableau. Un POST avec un corps non-JSON (ou vide) produit `$data = null`, et `null['_token']` déclenche une erreur fatale (`TypeError`) plutôt qu'une réponse 400 propre — DoS mineur/robustesse, pas une faille d'exploitation mais un bug facilement déclenchable (fuzzing, client buggé).
- **Aucune limite de taille/nombre de fichiers sur l'upload** (CoupleFormType.php, lignes 47-51) : le champ `images` (FileType multiple) n'a aucune contrainte `Assert\File` (maxSize, mimeTypes). Un utilisateur authentifié peut uploader un nombre illimité de fichiers volumineux → épuisement disque. À corriger même si le risque est limité par l'authentification obligatoire.
- **Échec silencieux à l'upload** : si un fichier n'est pas un PNG valide, `PictureService::upload()` l'ignore silencieusement sans retour d'erreur à l'utilisateur (PictureService.php, lignes 32-46) — pas une faille de sécurité mais une mauvaise expérience utilisateur qui peut masquer un problème.
- Pas de `Assert\Email` explicite sur `Users::$email`, ni de contrainte de complexité sur le mot de passe visible dans le code fourni (le `SecurityController` ne montre pas de formulaire d'inscription — à vérifier si l'inscription existe ailleurs ou est faite en fixtures/admin uniquement).
- Le typage strict PHP (`declare(strict_types=1)`) n'est présent dans **aucun fichier** du projet — avec `phpstan` en level 5 configuré, c'est cohérent avec la config actuelle mais laisse passer des conversions de type implicites (ex. comparaisons `==` au lieu de `===` dans `MainController::index` : `findNbCategory(...) == 0`).
- `MainController::index()` compare `findNbCategory($this->getUser()) == 0` — `$this->getUser()` peut être `null` en théorie (bien qu'improbable vu le firewall) ; `findNbCategory()` attend un `Users` non-nullable, ce qui est un léger décalage de contrat (PHPStan level 5 devrait déjà le signaler).

## 4. Qualité du code & Maintenabilité

**Points positifs**
- Nommage des routes cohérent (`app_<domaine>_<action>`), verbes HTTP explicites sur les routes REST-like.
- Commentaires en français conformes à la convention du projet, souvent utiles pour expliquer le "pourquoi" (ex. commentaires sur le cascade persist dans `Couples`, sur le comportement du `next-review`).
- Tests unitaires présents pour le voter (3 cas bien choisis : owner, non-owner, sujet non supporté) et un test d'intégration solide sur la cascade de suppression.
- `phpstan` (level 5) et `php-cs-fixer` sont configurés — bonne base d'outillage statique.

**Points à améliorer**
- **Couverture de tests très faible** : seulement 3 fichiers de test pour 32 classes PHP (~9%). Aucun test sur les contrôleurs métier principaux (`FaqsController`, `CouplesController` avec leur logique run/review), ni sur `PictureService`, ni sur les repositories `CouplesRepository` (logique de comptage/reset qui mériterait des tests vu sa complexité).
- **Nommage trompeur** : `CouplesRepository::countSelectRun()` ne filtre pas réellement sur un flag `selectRun` (qui n'existe même pas sur l'entité) — nom hérité d'un renommage incomplet, source de confusion pour la maintenance.
- ~~Duplication notable du bloc "récupérer les 4 compteurs" répété 7 fois à l'identique dans `FaqsController` et `CouplesController`~~ **Corrigé** via `CouplesRepository::countAll()` (cf. section performance).
- Incohérence de nommage entre `getCreateAt()` (Faqs) et `getCreatedAt()` (Couples) pour le même concept — faute de frappe qui casse la cohérence de l'API.
- Emojis et commentaires "journal de bord" laissés dans le code de prod (`// 🔒 On récupère l'utilisateur...`, `// J'ai ajouté à la main le cascade persist`, `// Le onDelete: 'CASCADE' a été ajouté à la main`) — traces de développement utiles en cours de dev mais à nettoyer avant une base "propre" (ou au moins reformuler sans référence au geste de modification, cf. bonnes pratiques de commentaires).
- `console.log` de debug laissés dans `scripts.js` et `image_preview_controller.js` (visibles en prod dans la console navigateur).
- Fonction vide `delete_image()` dans `scripts.js` (dead code, aucun appelant).
- `.php-cs-fixer.cache` et `.phpunit.result.cache` semblent committés dans le dépôt — à vérifier qu'ils sont bien dans `.gitignore`, sinon source de bruit dans les diffs.

## 5. Plan d'action priorisé

### Priorité Élevée
1. **Sécuriser le stockage des images uploadées** : déplacer `images_directory` hors de `public/assets/` (répertoire piloté par AssetMapper) vers un dossier dédié non compilé, pour éviter toute perte de données lors d'un déploiement/`asset-map:compile`.
2. **Ajouter des contraintes de validation sur l'upload** (`Assert\File` : taille max, types MIME autorisés) sur le champ `images` de `CoupleFormType` pour éviter l'épuisement disque.
3. **Sécuriser le parsing JSON des endpoints AJAX** (`set_one_review`, `cancel_one_review`, `restart`, `reset_review`, `deleteImage`) : vérifier que `json_decode` renvoie bien un tableau avant d'accéder à `_token`, retourner un 400 propre sinon.
4. **Corriger les fixtures cassées** (`AppFixtures` sans `Users` associé) pour qu'elles soient exécutables avec le schéma actuel.

### Priorité Moyenne
5. ~~**Résoudre les N+1 Doctrine**~~ **Fait** : `findByFaqWithImagesAndRules()` (leftJoin+addSelect) pour couples+images+rules, et `countAll()` (agrégats conditionnels) pour fusionner les 4 requêtes de comptage en une seule méthode réutilisée dans les 8 endroits dupliqués.
6. **Unifier la couche JavaScript** : migrer `public/assets/js/scripts.js` vers un contrôleur Stimulus dans `assets/controllers/`, cohérent avec le reste du projet (bénéfice : testabilité, fingerprinting AssetMapper, suppression du script hors-importmap).
7. **Rapatrier Bootstrap dans l'importmap** au lieu du CDN, pour cohérence avec l'architecture AssetMapper choisie.
8. **Étoffer la couverture de tests** sur `FaqsController`/`CouplesController` (logique run/review, qui est le cœur métier de l'appli) et `PictureService`.
9. Décider du sort de `symfony/ux-turbo` (activer réellement ou retirer la dépendance).

### Priorité Faible
10. Renommer `countSelectRun()` → nom conforme à son comportement réel, harmoniser `getCreateAt()`/`getCreatedAt()`.
11. Nettoyer les `console.log` de debug et la fonction morte `delete_image()`.
12. Ajouter `declare(strict_types=1)` progressivement (au moins sur les nouveaux fichiers), reformuler les commentaires "journal de bord" en commentaires techniques neutres.
13. Réorganiser `tests/entity/tests/Entity/...` et renommer `DeleteCascadeCategoryTest.php`/`DeleteCategoryTest` pour cohérence fichier/classe, déplacer ce test hors de `tests/Controller`.
14. Vérifier que `.php-cs-fixer.cache` et `.phpunit.result.cache` sont bien ignorés par Git.
