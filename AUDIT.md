# Audit du projet giga-memo

Date : 2026-09-18
Stack : Symfony 7.4.18 (PHP 8.3), Doctrine ORM 3.3, AssetMapper, Stimulus, Turbo (installé, non utilisé), MySQL.

Méthode : lecture exhaustive de `src/`, `templates/`, `config/`, `assets/`, exécution de `composer audit`,
`vendor/bin/phpstan analyse` (niveau 5) et de la suite `phpunit` complète (189 tests) sur la base de test locale.

---

## 1. Architecture & Structure

### Points forts
- **Séparation par domaine** : les contrôleurs sont rangés par ressource (`Controller/Categories`, `Controller/Couples`,
  `Controller/Faqs`, `Controller/Rules`, `Controller/Images`, `Controller/Main`) plutôt que dans un seul dossier plat.
  Lisible et cohérent avec les bonnes pratiques Symfony.
- **Voter générique** `ResourceOwnerVoter` (`RESOURCE_NEW/EDIT/VIEW/DELETE`) réutilisé sur toutes les entités
  possédant un `getUser()`. Un seul point de vérité pour l'autorisation, appliqué de façon quasi systématique via
  `#[IsGranted]`. C'est le meilleur choix d'architecture du projet.
- **Trait `HasUserTrait`** factorise la relation `ManyToOne` vers `Users` sur 4 entités (Categories, Faqs, Couples,
  Rules, Images) : bon usage de la composition pour éviter la duplication de mapping Doctrine.
- **Repository "riches"** : `CouplesRepository` porte la logique métier de requêtage (restart, reset, compteurs) au
  lieu de la laisser dans les contrôleurs ou de faire des boucles PHP sur des collections chargées entièrement.
- **DTO `CouplesCounters`** (`final readonly class`) : typage propre du résultat d'une requête d'agrégation, plutôt
  qu'un tableau associatif brut. Bonne pratique.
- **Form Types avec `query_builder` scopé à l'utilisateur** (`FaqFormType`, `SelectFaqFormType`, `CoupleFormType`) :
  empêche par construction qu'un utilisateur choisisse une catégorie/FAQ/règle appartenant à quelqu'un d'autre dans
  un `<select>`, en plus du contrôle du Voter. Défense en profondeur bien pensée.

### Points à améliorer
- **`MainController::index`** cumule trois responsabilités (redirections d'onboarding, affichage du formulaire de
  sélection, traitement de la soumission avec redirection run/edit). Un extrait vers un service ou au moins une
  méthode privée clarifierait le flux.
- **Couplage vue/contrôleur fragile** : le paramètre de route `{from<run|review|list>}` de `app_couples_update` et le
  champ caché non mappé `from` du `CoupleFormType` portent la même information par deux canaux différents. Une seule
  source (le paramètre de route, déjà présent) suffirait.
- **Mélange de style d'autorisation** : la plupart des contrôleurs utilisent l'attribut déclaratif `#[IsGranted]`,
  mais `FaqsController::new()` et `MainStartController::createFaq()` font un `denyAccessUnlessGranted()` impératif
  au milieu de la méthode. Fonctionnellement correct (le contrôle a lieu avant le `persist`), mais l'incohérence de
  style augmente le risque d'oubli lors d'une future modification.
- **Nommage** : mélange anglais (`findNextPendingForRun`, `restartPendingForRun`) et français (`num`, `reponse`,
  `question`) selon les couches. Pas bloquant, mais à trancher pour la cohérence à long terme.
- Pas de couche `Service` dédiée à la logique métier "run/review" des FAQ : elle est répartie entre
  `CouplesRepository` (bulk update DQL, bien) et les contrôleurs `FaqsController`/`CouplesController` (orchestration
  + JSON, un peu chargé). Acceptable à la taille actuelle du projet, à surveiller si de nouvelles règles métier
  s'ajoutent.

---

## 2. Performance & Optimisations

### Points forts (déjà en place)
- **N+1 déjà corrigé et documenté** : `CouplesRepository::findByFaqWithImagesAndRules()` charge un couple, ses
  images et ses règles en une seule requête (`leftJoin` + `addSelect`), avec un commentaire de code qui référence
  explicitement ce fichier d'audit. Un test dédié (`Find by faq with images and rules loads collections in one
  query`) verrouille la non-régression. Exemplaire.
- **Compteurs en une requête** : `CouplesRepository::countAll()` renvoie les 4 compteurs (run/review restants et
  totaux) via une seule requête d'agrégation SQL (`SUM(CASE WHEN ...)`), au lieu de 4 requêtes séparées. Bon réflexe
  de performance.
- **Cache Doctrine** (`query_cache_driver`, `result_cache_driver`) correctement activé uniquement en prod
  (`when@prod` dans `doctrine.yaml`), avec des pools `cache.system`/`cache.app` dédiés.
- **AssetMapper** utilisé sans fioriture : imports ESM propres dans `app.js`, pas de build Webpack superflu,
  `assets/vendor/` bien exclu du dépôt (généré par `importmap:install`).

### Points à améliorer
- **Turbo installé mais non exploité.** `symfony/ux-turbo` et `@hotwired/turbo` sont présents, `turbo-core` est actif
  par défaut (`controllers.json`), mais aucun template n'utilise `turbo-frame`, `turbo-stream` ou `data-turbo`.
  Concrètement : Turbo Drive intercepte silencieusement la navigation (liens et soumissions de formulaires) de toute
  l'application sans que ce comportement ait été un choix explicite ni testé. C'est à la fois du poids mort côté
  bundle JS et un risque de comportement de navigation inattendu.
- Les mises à jour de compteurs (`counter_action_controller.js`) sont faites en `fetch` + JSON manuel, alors que
  Turbo Streams serait l'outil naturel pour ça puisque la dépendance est déjà présente. Cohérence à retrouver dans
  un sens ou dans l'autre (l'utiliser vraiment, ou le retirer).
- **`console.log` de debug oubliés** en production dans plusieurs contrôleurs Stimulus
  (`counter_action_controller.js`, `confirm_modal_controller.js`, `image_preview_controller.js`,
  `answer_controller.js`) et dans `assets/app.js` ("welcome to AssetMapper 🎉"). Pas un problème de performance en
  soi, mais du bruit à nettoyer avant mise en production.
- Aucun cache HTTP (`Cache-Control`, attribut `#[Cache]`) ni cache applicatif (`framework.cache` pools) exploité
  pour des données peu volatiles comme la liste des catégories d'un utilisateur. Non critique vu le volume de
  données probable de l'application, mais à garder en tête si le nombre d'utilisateurs augmente.
- Le N+1 restant sur `rules/list-by-faq.html.twig` (boucle `for rule in faq.rules`) n'est en réalité qu'une requête
  supplémentaire (1+1, pas un vrai N+1 puisqu'il n'y a qu'une seule FAQ) : mentionné pour être exhaustif, mais ce
  n'est pas un problème à corriger.

---

## 3. Sécurité & Robustesse

### Points forts
- **Isolation multi-utilisateur robuste** : chaque ressource sensible (Catégorie, FAQ, Couple, Règle, Image) est
  rattachée à un `Users` via `HasUserTrait` et vérifiée par `ResourceOwnerVoter` avant toute lecture/écriture. C'est
  le point le plus solide du projet côté sécurité.
- **CSRF traité de bout en bout** : formulaires Symfony classiques + jetons CSRF manuels sur les endpoints Ajax
  (`JsonCsrfTokenTrait`), avec une gestion propre des erreurs (400 explicite si le corps JSON est absent ou mal
  formé, plutôt qu'un `TypeError` fatal).
- **`access_control` en liste blanche puis verrou global** : les routes publiques sont explicitement listées, puis
  `{ path: ^/, roles: ROLE_USER }` verrouille tout le reste par défaut. C'est la bonne façon de faire (deny by
  default).
- **Inscription / vérification email / reset password** via les bundles SymfonyCasts, avec anti-énumération
  explicite ("ne pas révéler si un compte existe") dans `ResetPasswordController::processSendingPasswordResetEmail`,
  et `NotCompromisedPassword` (vérification Have I Been Pwned) sur le changement de mot de passe.
- **Upload d'images maîtrisé** : restriction PNG à la fois côté formulaire (`File` constraint) et côté service
  (vérification `getimagesize()` du contenu réel, pas seulement du `Content-Type` déclaré par le navigateur), noms
  de fichiers générés aléatoirement (évite l'écrasement ou l'injection de chemin), et **quota d'images par
  utilisateur** (`images_max_per_user`) qui protège contre l'épuisement disque.
- `composer audit` : aucune vulnérabilité connue dans les dépendances actuelles.

### Points à corriger
- **Typage faible confirmé par l'outillage** : `vendor/bin/phpstan analyse` (niveau 5, pourtant bas) relève
  **9 erreurs réelles**, toutes de la même nature : `$this->getUser()` renvoie `UserInterface|null`, mais est transmis
  tel quel à des méthodes qui attendent `?Users` — `Faqs::setUser()`, `Categories::setUser()`, `Rules::setUser()`,
  `CategoriesRepository::findNbCategory()`, `FaqsRepository::findNbFaq()` — dans `FaqsController`, `MainController`,
  `MainStartController` et `RulesController`. Ça fonctionne aujourd'hui uniquement parce qu'il n'existe qu'un seul
  provider (`Users`), mais c'est un vrai trou de typage strict. Le correctif existe déjà dans le code
  (`CouplesController` utilise `/** @var Users $user */ $user = $this->getUser();`) : il suffit de généraliser ce
  pattern aux 4 contrôleurs concernés.
- **Bug potentiel détecté par l'analyse statique** : `LoginVerificationReminderListener::__invoke()` appelle
  `$event->getRequest()->getSession()->getFlashBag()`, mais `getSession()` est typé `SessionInterface`, qui n'expose
  plus `getFlashBag()` dans les versions récentes de Symfony (la méthode vit sur `FlashBagAwareSessionInterface`).
  Aucun test ne couvre ce listener : à vérifier manuellement (connexion avec un compte non vérifié) pour confirmer
  si ça lève une erreur en conditions réelles, et corriger le typage sinon.
- **Pas de limitation de tentatives (rate limiting)** sur `/connexion`, `/inscription`, ni sur la demande de
  réinitialisation de mot de passe. Symfony fournit un `login_throttling` prêt à l'emploi non activé dans
  `security.yaml` : l'application est exposée au brute force de mots de passe et au spam d'envoi d'emails.
- **`declare(strict_types=1)` quasi absent** : présent dans 1 seul fichier sur 42 (`PictureService.php`). Sans lui,
  PHP effectue des conversions de type implicites (int → string, etc.) qui masquent des bugs à l'exécution plutôt
  que de les révéler immédiatement.
- **Cohérence disque/BDD sur les fichiers** : la suppression physique de l'image a lieu dans
  `ImageDeleteListener::preRemove()`, donc *avant* le commit SQL du `flush()`. Si le flush échoue ensuite pour une
  autre raison, le fichier est déjà supprimé du disque alors que la ligne BDD reste (rollback). Risque symétrique
  dans `PictureService::upload()` (fichier déplacé sur disque avant flush). Cas rares, mais à connaître.
- Aucune protection anti-bot (honeypot/captcha) sur l'inscription ou la demande de reset password, qui restent des
  cibles classiques de spam automatisé.

---

## 4. Qualité du code & Maintenabilité

### Points forts
- **Suite de tests réellement complète** : 189 tests / 517 assertions couvrant Entités, Repository, Form Types,
  Voter, Service, Contrôleurs et EventListener. Exécutée avec succès (188/189). C'est nettement au-dessus de la
  moyenne pour un projet de cette taille, et un vrai filet de sécurité pour les évolutions futures.
- **Commentaires abondants et pédagogiques en français**, cohérents avec l'objectif d'apprentissage du projet :
  chaque contrôleur Stimulus explique son rôle, chaque méthode de repository un peu subtile est justifiée.
- **PSR-12 globalement respecté** : imports groupés, visibilités explicites, indentation cohérente.
- Aucune vulnérabilité de dépendance, dépendances à jour à quelques patchs mineurs près (Symfony 7.4.17→7.4.19,
  Doctrine ORM 3.6→3.7, etc.), rien de critique.

### Points à améliorer
- **1 test en échec** : `RegistrationControllerTest::testRegisterPageIsAccessible` cherche le texte "Formulaire
  d'inscription" dans la page, texte qui n'existe plus dans `templates/registration/register.html.twig` (dérive
  entre le template et le test après une évolution de l'UI). À corriger pour ne pas laisser une suite rouge
  s'installer.
- **Duplication du bloc JSON des compteurs** : la même structure
  `['nbRemainingToRun' => ..., 'nbRemainingToReview' => ..., 'nbTotalToRun' => ..., 'nbTotalToReview' => ...]` est
  recopiée à l'identique dans 4 méthodes (`FaqsController::restart`, `FaqsController::reset_review`,
  `CouplesController::set_one_review`, `CouplesController::cancel_one_review`). Une méthode commune
  `countersToJson(CouplesCounters $counters): JsonResponse` (par ex. dans un trait partagé) supprimerait la
  duplication.
- **Casse des méthodes de contrôleur incohérente** : `list_by_faq`, `set_one_review`, `cancel_one_review`,
  `reset_review` sont en snake_case alors que PSR-12/Symfony attendent du camelCase (`listByFaq`, `setOneReview`...).
- **Bloc mort dans `base.html.twig`** : `{% block stylesheets %}{% endblock %}` n'est jamais utilisé, le CSS étant
  chargé via l'import ESM dans `app.js`. À retirer pour éviter toute confusion future.
- **Niveau PHPStan bas (5/10)** au regard de la discipline de code déjà en place : le passer à 8 ferait ressortir
  immédiatement les 9 erreurs déjà identifiées, et probablement d'autres améliorations de typage.
- Quelques comparaisons Yoda (`'review' == $from`) : style valide mais peu idiomatique en PHP moderne, à uniformiser
  si un style guide/CS Fixer strict est mis en place.

---

## 5. Recommandations prioritaires

### Priorité Élevée
1. **Corriger les 9 erreurs PHPStan** de typage `$this->getUser()` → `Users` dans `FaqsController`,
   `MainController`, `MainStartController`, `RulesController`, en généralisant le pattern
   `/** @var Users $user */ $user = $this->getUser();` déjà utilisé dans `CouplesController`.
2. **Vérifier et corriger `LoginVerificationReminderListener`** (`getSession()->getFlashBag()`) : tester une
   connexion avec un compte non vérifié pour confirmer si l'erreur se produit réellement, puis corriger le typage
   ou la façon d'accéder au flash bag.
3. **Activer le rate limiting sur le login** (`login_throttling` dans `security.yaml`, firewall `main`) pour se
   prémunir du brute force sur `/connexion`.
4. **Corriger le test cassé** `RegistrationControllerTest` (aligner le texte attendu avec le template actuel).

### Priorité Moyenne
5. **Trancher le sort de Turbo** : soit l'exploiter réellement (Turbo Streams pour les compteurs run/review, ce qui
   simplifierait `counter_action_controller.js`), soit le retirer proprement
   (`composer remove symfony/ux-turbo`, retrait de `@hotwired/turbo` de l'importmap) pour ne pas garder une
   dépendance qui modifie silencieusement le comportement de navigation sans que ce soit voulu.
6. **Généraliser `declare(strict_types=1)`** à l'ensemble de `src/` (peut être automatisé via php-cs-fixer).
7. **Factoriser le bloc dupliqué** de conversion `CouplesCounters` → `JsonResponse`.
8. **Nettoyer les `console.log` de debug** dans les contrôleurs Stimulus et `app.js`.
9. **Monter `phpstan.dist.neon` au niveau 8** par paliers, pour capitaliser sur la bonne discipline déjà en place.
10. **Ajouter une protection anti-bot légère** (honeypot) sur l'inscription et la demande de reset password.

### Priorité Faible
11. Harmoniser la casse des méthodes de contrôleur en camelCase strict.
12. Retirer le bloc `stylesheets` mort de `base.html.twig`.
13. Mettre à jour les dépendances mineures (`composer update` sur les patchs Symfony 7.4.x et Doctrine ORM).
14. Revoir l'ordre suppression-fichier / commit-BDD pour éviter les incohérences disque/BDD en cas d'échec de
    flush, ou prévoir une tâche de nettoyage des fichiers orphelins.
15. Trancher la convention de nommage anglais/français dans le code (méthodes de repository vs propriétés d'entité)
    pour plus de cohérence à long terme.
