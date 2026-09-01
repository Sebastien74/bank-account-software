# Écarts avec les conventions du projet

Le point qui compte avant d'intégrer.

## Décisions prises le 26 août 2026

Arbitrées par le client, avant toute intégration :

1. **La charte suit la démo**, sans moyenner. Rayons arrondis compris.
2. **Le back passe en sombre par défaut.**
3. **Public Sans**, la police de la démo, auto-hébergée.
4. **Les libellés flottants restent**, seule exception retenue. Le back affiche
   ses libellés en position haute en permanence, ce que la démo ne fait pas, et
   c'est ce qui rend le gabarit de saisie lisible au repos.
5. **La licence n'est pas un obstacle** : outil interne. Les valeurs se
   reprennent par les variables Bootstrap, ce qui reste de toute façon la bonne
   façon de le faire - pas une recopie de la feuille de style du gabarit.

**L'intégration est faite, le 26 août 2026.** Ce dossier reste la charte, et
la section « Ce qui a été intégré » en bas dit ce qui a été posé, ce qui a été
trouvé en le posant, et ce qui reste.

## Ce qui reste à trancher, ou à surveiller

## 1. Le rayon

Le projet passe `$border-radius: 0` à Bootstrap, dans
`assets/back/_variables.scss`, et l'assume : angles droits partout. Vuexy est à
`0.375rem`, avec une échelle jusqu'à `0.625rem`.

**Ce n'est pas un réglage, c'est le parti pris.** Décidé : on suit la démo.

Ce que cela entraîne : l'espace `security` partage le jeton, donc il s'arrondit
aussi. Un back arrondi et un écran de connexion à angles droits ne seraient pas
le même produit. Et le commentaire de `_variables.scss` qui justifie les angles
droits devra partir en même temps, sans quoi il mentira.

## 2. Le mode sombre

Le projet est en clair : `$paper: #f1f2f4`, `$surface: #ffffff`, une chrome
sombre et une surface de travail claire, décrit comme tel dans le commentaire de
`_variables.scss`. Vuexy sombre inverse tout. Décidé : on bascule.

Trois conséquences peu évidentes, à traiter pendant l'intégration :

- **les contrastes sont à recalculer entièrement.** Le projet a mesuré les siens,
  avec des commentaires qui le disent : `#9c4520` a été assombri depuis `#b7592c`
  pour tenir 4,4:1 sur la surface de travail, et `$alarm: #a4161a` a été choisi
  contre le rouge Bootstrap qui mesurait 3,52 sous un plancher de 4,5. Tout ce
  travail est à refaire sur fond sombre.
- **les images de la médiathèque changent de voisinage.** Une photographie claire
  sur `#2f3349` ne rend pas comme sur du blanc, et les vignettes carrées de
  l'index gagneraient un filet qu'elles n'ont pas aujourd'hui.
- **le rendu public reste clair.** Un éditeur passerait d'un back sombre à un
  aperçu clair. Ce n'est pas disqualifiant, c'est à savoir.

## 3. La typographie

Le projet auto-héberge **Fira Sans** par `@fontsource`, et le commentaire de
`back.js` dit pourquoi : aucune requête tierce. Vuexy charge **Public Sans**
depuis Google Fonts.

Décidé : **Public Sans**, la police de la démo.

**Mais pas depuis Google Fonts.** Le projet a un plancher Lighthouse de 95 sur
quatre catégories, et une requête tierce bloquante y coûte cher. Le paquet
`@fontsource/public-sans` existe et s'importe comme `@fontsource/fira-sans`
l'est déjà dans `assets/back/back.js` : quatre graisses, 300 à 600.

Reste à décider si l'espace `security` suit. Il partage la police aujourd'hui ;
laisser Fira Sans d'un côté et Public Sans de l'autre serait un écart visible au
passage de la connexion au back.

## 4. La règle du CSS custom

`PRECO.md` interdit d'écrire une règle là où Bootstrap suffit, et impose de
passer par la clause `with` du module. C'est **favorable** ici : la quasi-totalité
de ce que fait Vuexy passe par des variables Bootstrap.

Se reprennent par `with`, sans une ligne de règle :

`$primary`, `$secondary`, `$success`, `$info`, `$warning`, `$danger`, `$body-bg`,
`$body-color`, `$border-radius` et son échelle, `$font-family-base`,
`$font-size-base`, `$line-height-base`, `$card-spacer-y`, `$card-border-width`,
`$table-cell-padding-y`, `$input-padding-y`, `$badge-border-radius`,
`$box-shadow` et ses variantes.

Demandent une règle, parce que Bootstrap n'a pas le comportement : le champ
transparent, l'ombre colorée de l'onglet actif, l'entrée de menu active en
rectangle plein, et le couple titre plus sous-titre de carte.

## 5. Le mode sombre de Bootstrap 5.3

Vuexy utilise `[data-bs-theme=dark]`, le mécanisme natif de Bootstrap 5.3, et
**le projet s'en sert déjà** : sur le rail du back
(`templates/back/includes/_rail.html.twig`), sur les zones inversées du front
(`templates/front/page.html.twig`), et sur tout l'espace `security`
(`templates/security/base.html.twig`). Le mécanisme n'est donc pas à
introduire, seulement à étendre au document du back.

Ce que cela veut dire concrètement, vérifié dans `node_modules/bootstrap` :

- Bootstrap 5.3 expose **55 variables `*-dark`** dans `_variables-dark.scss`,
  dont `$body-bg-dark`, `$body-color-dark`, `$border-color-dark`,
  `$headings-color-dark`. Elles se passent par la clause `with`.
- Les fonds de composant - `$card-bg`, `$dropdown-bg`, `$modal-content-bg`,
  `$popover-bg`, `$list-group-bg`, `$input-bg`, `$accordion-bg`, `$pagination-bg`
  - valent tous `var(--bs-body-bg)` par défaut. Ils suivent donc le mode sans
  rien écrire. **Mais** la démo veut la carte *plus claire* que la page, donc
  ceux-là demandent une valeur explicite.
- `data-bs-theme="dark"` pose aussi `color-scheme: dark`, ce qui donne les
  ascenseurs et les contrôles natifs sombres sans une ligne de CSS.

Le piège subsiste sur un point : les jetons du projet sont posés en Sass à la
compilation, alors que `[data-bs-theme]` travaille en propriétés personnalisées
à l'exécution. Comme le back n'aura qu'un seul mode, poser les valeurs claires
**et** sombres à la même teinte évite tout écart : un composant lu hors d'une
portée `data-bs-theme` reste sur le même fond.

## 6. Ce qui est déjà bon et n'a pas à changer

À ne pas défaire par mimétisme :

- **le pied de liste** compte à gauche, pagination à droite : identique à Vuexy ;
- **la carte comme conteneur unique** : `.back-panel` fait déjà ce travail ;
- **les deux panneaux de l'espace `security`** : la référence confirme la
  structure ;
- **le glisser-déposer par Sortable** : jsTree tirerait jQuery, ce serait une
  régression ;
- **les icônes Lucide par `ux-icons`**, avec leur table de correspondance dans
  `App\Service\Back\Icons`. Vuexy utilise Iconify et Tabler ; changer de jeu
  d'icônes n'apporterait rien qu'un téléversement de plus.

## 7. Licence

Vuexy est un gabarit commercial. Relever des valeurs et décrire des dispositions
ne pose pas de problème ; recopier son CSS, son balisage ou ses illustrations
demande la licence. **À trancher avant l'intégration**, pas pendant.

## Ordre proposé, si l'intégration est décidée

1. trancher le rayon et le mode sombre, et l'écrire dans `PRECO.md` ;
2. recalculer les contrastes de la palette retenue, et les commenter comme le
   projet le fait déjà ;
3. passer ce qui se passe par la clause `with` de `assets/back/back.scss` ;
4. reprendre les composants un par un, dans l'ordre des écrans les plus vus :
   index, formulaire de contenu, médiathèque ;
5. mesurer Lighthouse à chaque étape, plancher 95, et repasser `/opquast` :
   changer de fond change tous les contrastes, donc tous les critères RGAA de
   couleur.

## Chemin d'intégration, déjà repéré

L'intégration a été montée puis défaite le 26 août 2026, pour vérifier qu'elle
tient. Elle tient : le back a compilé en sombre, `--bs-body-bg: #25293c` dans
`private/build/back/app.css`, avec Public Sans. Ce qui suit est la liste exacte
des points de retouche, pour ne pas refaire la recherche.

| Fichier | Ce qu'il y a à faire |
|---------|----------------------|
| `assets/back/_variables.scss` | remplacer la valeur des jetons, pas leur nom : `$paper`, `$surface`, `$ink`, `$slate`, `$graphite`, `$rule`, `$highlight`, `$ember`, `$alarm`, `$moss`, `$muted`. Les règles existantes suivent alors sans être touchées. |
| `assets/back/back.scss` | clause `with` : les `*-dark` de Bootstrap, les fonds de composant plus clairs que la page, `$input-bg: transparent`, `$card-border-width: 0`, la densité, les ombres, `$badge-border-radius: 0.125rem`. |
| `assets/back/back.js` | les quatre imports de graisse, Fira Sans vers Public Sans. |
| `templates/back/base.html.twig` et `error/40*.html.twig` | `data-bs-theme="dark"` sur `<html>`. |

Deux pièges rencontrés, tous deux dans `back.scss` :

- **`$highlight` porte deux rôles à la fois** : couleur de texte sur le rail
  sombre, et fond de survol sur la surface de travail claire. Sur un fond
  unique sombre, les deux doivent se séparer - le texte passe à `$slate`, le
  fond reste `$highlight`. Trois lignes concernées.
- **`$ink` et `$surface` convergent.** Le rail de Vuexy est la même surface
  qu'une carte, seule la page derrière est plus sombre. Les deux jetons
  prennent donc `#2f3349`, ce qui n'est pas une erreur mais mérite un
  commentaire, sinon quelqu'un les fusionnera.

Un dernier point, valable pour la vérification : **le CSS du back est protégé
par `AssetController`**. Une capture navigateur du back demande donc une session
ouverte, et l'authentification passe par le second facteur via Mailpit. Le
lanceur `bin/lighthouse.mjs` porte déjà cette mécanique, dans ses fonctions
`signIn` et `mailpitCode` : c'est de là qu'il faut partir, pas d'un
`playwright-cli open` qui repart d'une session vide.

## Ce qui a été intégré, le 26 août 2026

Les cinq décisions du haut sont posées. Ce qui suit est le journal de la pose,
pour que personne ne refasse la recherche.

### Fichiers touchés

| Fichier | Ce qui a été fait |
|---------|-------------------|
| `assets/back/_variables.scss` | palette sombre, ratios recalculés et commentés valeur par valeur, plus trois jetons neufs : `$primary-light`, `$alarm-light`, `$muted` en valeur mesurée |
| `assets/back/back.scss` | clause `with` : les `*-dark`, les fonds de composant, `$input-bg: transparent`, `$card-border-width: 0`, la densité, les ombres mesurées, `$badge-border-radius` |
| `assets/back/back.js` | Fira Sans vers Public Sans, quatre graisses auto-hébergées |
| `assets/controllers/rich_text_controller.js` | skin `oxide-dark`, et le fond du cadre d'édition relu sur la feuille |
| `assets/security/_variables.scss`, `security.scss`, `security.js` | rayon et police alignés, palette inchangée |
| `templates/back/base.html.twig`, `error/40*.html.twig` | `data-bs-theme="dark"` |
| `templates/core/form/bootstrap_5.html.twig`, `back/media/form.html.twig` | onglets à filet vers pilules, cinq jeux |
| `bin/contrast.mjs`, `package.json` | le contrôle de contraste, rendu exécutable |

### Les deux pièges annoncés, et ce qu'ils ont coûté

Les deux étaient exacts. `$highlight` a bien fallu séparer, et `$ink` et
`$surface` valent bien la même teinte - avec le commentaire qui empêche de les
fusionner. Un détail à savoir en plus : le remplacement de `$highlight` par
recherche de texte attrape la version à quatre espaces d'indentation **à
l'intérieur** de celle à huit. Les deux rôles se relisent à la main.

### Sept défauts trouvés en posant, tous corrigés

Aucun n'était prévu par la charte, et six sur sept sont des contrastes.

1. **Le violet de la démo ne peut pas porter de libellé blanc.** `#7367f0`
   mesure 4,26:1 sous le blanc, et Bootstrap répond alors par un libellé
   **noir**, que `color-contrast()` choisit dès qu'il tient 4,5. Il a fallu
   descendre au survol de la démo, `#685dd8`, où le blanc tient 5,07 et où le
   noir ne passe plus.
2. **`btn-outline-secondary`, la classe la plus utilisée du back-office**, 57
   emplois, sortait son libellé à 3,29:1. Idem `btn-outline-danger` à 2,55.
   Corrigé par les propriétés personnalisées du composant, sans réécrire la
   classe.
3. **Le libellé flottant du projet est à `opacity: .65` en permanence**, parce
   que Bootstrap le prévoit pour un champ rempli et que le thème le lève
   toujours. Tous les libellés de formulaire mesuraient 3,26:1.
4. **Une pagination désactivée prend `$gray-800`**, un gris de Bootstrap qui
   n'appartient à aucune palette du projet, et les points de suspension y
   tombaient à 4,25:1.
5. **La rangée de tuiles du tableau de bord était invisible**, et le panneau de
   connexion tout entier avec elle. `animation-fill-mode: backwards` tient
   l'image de départ pendant le délai, or l'horloge d'animation ne démarre
   qu'à la première image peinte : une capture sans interface - ce que fait
   Lighthouse - les attrape à `opacity: 0`. Le commentaire du code affirmait
   que cela ne pouvait pas arriver.
6. **L'éditeur riche était un aplat blanc.** Il embarque son propre habillage,
   qui n'écoute pas `data-bs-theme`.
7. **Le pied de la page de connexion mesurait 2,81:1**, défaut antérieur, hors
   périmètre de la refonte, trouvé par le même contrôle.

### Ce que la pilule a cassé en silence

Passer `nav-tabs` en `nav-pills` a emporté deux choses qui nommaient la classe
au lieu du rôle, et **la capture navigateur ne l'a pas vu** : le serveur ouvre
lui-même l'onglet refusé, donc l'écran avait l'air juste.

- `assets/controllers/tabs_controller.js` cherchait `:scope > .nav-tabs`. Sans
  son `tablist`, le contrôleur n'écrivait plus rien et ne restaurait plus rien :
  la règle « un formulaire à onglets rend l'onglet où l'on travaillait » était
  morte, sans erreur de console.
- `tests/Functional/Back/PageCompositionTest.php` filtrait sur `.nav-tabs`, en
  trois endroits. Ce sont les deux seules erreurs de la suite, et c'est ce qui a
  révélé la première.

Les deux visent désormais `[role="tablist"]`, qui est ce qu'ils voulaient dire.
**Une variante Bootstrap ne se nomme pas dans un test ni dans un contrôleur** :
le rôle ARIA survit à la charte, la classe non.

### Ce qui a été décidé, et volontairement pas fait

- **`.back-status` reste une puce plus un mot**, pas la pastille à fond atténué
  que ce fichier trouvait meilleure. Elle tient désormais ses 4,5:1 sur les deux
  états, elle est déjà factorisée dans un seul gabarit, et la pastille
  demandait six jetons de plus pour un gain d'aspect. À reprendre si le besoin
  vient d'ailleurs que du mimétisme.
- **Les onglets restent dans la carte.** La démo les pose au-dessus ; les
  déplacer touche la structure de chaque écran de formulaire et le contrôleur
  `tabs`, pour un gain qui n'est pas arbitré.
- **La rangée de tuiles de chiffres ne gagne ni icône ni variation.** La démo en
  porte, le tableau de bord du CMS n'a pas la donnée.


## Ce qui a été repris, le 27 août 2026

Signalé en séance : « des blocs qui contiennent les tables, ça ne correspond pas
à la charte ». C'était exact, et la cause est mécanique.

### Le défaut : toute table cachait la carte qui la portait

`$table-bg` vaut `var(--bs-body-bg)` par défaut, et une cellule Bootstrap peint
ce fond elle-même, sur `> :not(caption) > * > *`. Le projet ayant posé
`$body-bg: #25293c`, chaque cellule repeignait **la couleur de la page** par
dessus la carte : mesuré au navigateur, `.back-panel` à `rgb(47, 51, 73)` et
`.back-table td` à `rgb(37, 41, 60)`. La carte existait, la table l'effaçait.

Un index entier lisait donc comme une table posée à nu sur la page, là où la
référence l'enferme dans une carte. Corrigé en une ligne de la clause `with` :
`$table-bg: transparent`. La cellule prend alors le fond de la carte, ce que
`formes-typo.md` dit déjà du champ de saisie.

**À retenir** : un composant Bootstrap qui dérive de `$body-bg` doit être relu
dès que la page et la surface ne sont plus la même couleur. La carte, le
dropdown, la modale et l'input avaient été traités ; la table avait été oubliée.

### Trois écarts de charte corrigés avec lui

- **Le titre de carte était une étiquette de colonne.** `.back-panel-title`
  portait `micro-label`, soit 11 px en capitales espacées, le même registre que
  les en-têtes du tableau juste dessous. La référence pose un titre de carte en
  `--bs-heading-color`, graisse moyenne, taille courante : il passe à
  `1.0625rem` et `$headings-font-weight`.
- **La rangée de tuiles était un bloc soudé à filet**, héritée du parti clair à
  angles droits. Elle devient **une carte par tuile**, comme `app-user-list` et
  comme `index`. Ce n'est pas qu'une question d'aspect : l'animation d'arrivée
  décale chaque tuile de `0.5rem`, et un fond partagé se voyait par la fente
  ouverte. Le piège de `.claude/PRECO.md` sur cette même rangée, sous une autre
  forme.
- **La barre d'outils et le pied étaient hors de la carte.** La référence
  enferme recherche, tableau et pied compte plus pagination dans **une seule
  carte** (`tables-datatables-basic`, `app-ecommerce-product-list`). Les deux
  entrent dans `_index.html.twig` et `_grid.html.twig`, donc les onze index en
  héritent sans une ligne chacun. `_pagination.html.twig` reçoit la classe de son
  enveloppe par `pagination_class`, ce qui laisse le sélecteur de médias garder
  la sienne.

### Le rail groupe ses entrées

Demandé en séance. Deux groupes repliés, un seul ouvert à la fois : « Gestion du
site » - pages, médias, menus, utilisateurs, réglages - et « Modules » -
carrousels, actualités, historique. Le tableau de bord reste hors des groupes :
c'est l'accueil du rail, et l'enfermer dans un groupe replié le cacherait.

Deux points à savoir :

- **Le groupe qui porte l'écran courant s'ouvre**, et « Gestion du site » n'est
  le défaut que lorsque aucun ne le porte. Ouvrir toujours le premier cacherait
  le lien de la page où l'on est.
- **L'événement de pliage remonte aussi des sous-menus** imbriqués dans un
  groupe. `nav-accordion` ne ferme donc que sur un événement dont la cible est
  un de ses `group`, sinon ouvrir « Utilisateurs » fermait son propre parent.
  Verrouillé par `DashboardTest`, et vérifié au navigateur.

## Le thème clair, le 27 août 2026

Demandé en séance. La palette claire est **relevée dans le même core.css que la
sombre**, bloc `[data-bs-theme=light]` : les valeurs sombres de ce dossier s'y
retrouvent à l'identique, ce qui valide le relevé.

| Rôle | Démo, clair | Projet, clair | Pourquoi l'écart |
|------|-------------|---------------|------------------|
| Page | `#f8f7fa` | `#e9ebf0` | fatigue oculaire, arbitré en séance |
| Carte | `#ffffff` | `#fbfbfd` | aucun blanc pur, même raison |
| Titres | `#444050` | `#3a3746` | plus contrasté, même raison |
| Texte courant | `#6d6b77` | `#5b5966` | 4,30:1 sur le lavis violet sinon |
| Texte secondaire | `#acaab1` | `#6e6c79` | 2,20:1 sur une carte sinon |
| Filet de champ | mix 22% | `#8f8d99` | 1,51:1 sinon, sous les 3:1 dus |
| Violet marque | `#7367f0` | `#5a4fca` | 4,26:1 sinon |
| Rail | clair | `#2f3349` | reste sombre, arbitré en séance |

Ce que la bascule a demandé, et qui ne se devine pas :

- **Un jeton Sass ne bascule pas.** Les quinze jetons de couleur valent
  désormais `var(--back-*)`, et deux blocs de `back.scss` les redéfinissent. Les
  1 500 lignes de règles n'ont pas bougé : elles nomment déjà des jetons.
- **Ce sur quoi Bootstrap calcule refuse un `var()`.** `$code-color` a fait
  tomber la compilation, parce que `_variables-dark.scss` en tire une teinte par
  `tint-color()`. Ceux-là reçoivent `map.get(t.$light, ...)` et leur jumeau.
- **Trois jetons mentaient sur un fond clair.** `$primary-light` et
  `$alarm-light` nommaient « la teinte lisible », qui s'éclaircit en sombre et
  s'assombrit en clair. Renommés `-text`, et `$moss-text` et `$ember-text` les
  rejoignent : la pastille d'état lisait `#28c76f` sur blanc, 2,2:1.
- **Le dégradé du `.back-shell` cachait le rail au contrôle.** Il peignait la
  colonne du rail, que le composant offcanvas vide au-dessus de son point de
  rupture. `bin/contrast.mjs` ne lit que `background-color` : il a rendu
  106 faux écarts, tous sur le rail. Un fond opaque par colonne remplace le
  dégradé, et le contrôle voit ce que l'oeil voit.
- **Deux feuilles tierces gagnent l'égalité de spécificité.** Choices.js et la
  peau de TinyMCE arrivent en morceau différé, donc après celle du projet. Leur
  fond se reprend avec un sélecteur plus lourd, jamais un `!important`.

`bin/contrast.mjs` audite maintenant **les deux thèmes** et prend `--theme`.
Vingt-et-un audits, zéro écart.

## L'espace `security` prend la palette du back, le 27 août 2026

Demandé en séance, en remplacement des valeurs mesurées sur `background.jpg`.
La photographie et la disposition en deux panneaux ne changent pas ; les
couleurs, si.

**Les deux espaces lisent désormais la même source**, `assets/styles/_palettes.scss` :
une teinte corrigée d'un côté ne peut plus dériver de l'autre. C'était le point
qui décidait entre partager et recopier.

### Trois pièges de nommage, levés en passant

Les deux espaces employaient les mêmes mots pour des rôles différents, ce qui
rendait tout passage de l'un à l'autre risqué :

| Nom, avant | Rôle dans `security` | Rôle dans le back | Nom, après |
|------------|----------------------|-------------------|------------|
| `$highlight` | texte accentué, crème | fond de survol, lavis violet | `$slate` |
| `$graphite` | le gris **faible** | le texte courant | `$muted` / `$rule-strong` |
| `$graphite-light` | le gris **fort** | le gris faible | `$graphite` |

`$graphite` portait en plus **trois rôles à la fois** dans `security` : couleur
de texte, filet d'un contrôle, et fond d'un bouton désactivé. Le back les tient
séparés depuis sa refonte, et c'est ce découpage qui a été appliqué ici. Le
filet d'un champ passe donc à `$rule-strong`, seul jeton qui tient les 3:1 dus.

### Deux couples que le changement de teinte cassait

- **Le libellé du bouton d'envoi était `$ink`**, l'encre du panneau, sur le
  primaire. Lisible sur l'orange chaud d'avant, il tombe à 3,4:1 sur le violet.
  Il passe au blanc, qui est la couleur pour laquelle ce violet a été assombri :
  **5,07:1 mesuré**.
- **Le survol prenait `$highlight` en fond**, ce qui n'a plus de sens quand ce
  jeton désigne un texte. Il prend `$primary-shade`, le filet actif de la
  référence.

`::selection` devient celle du back, fond ambre et texte sombre : un produit,
une sélection.

### Mesuré, pas supposé

`npm run contrast` sur `security-login` : zéro écart. Sur l'écran de second
facteur, qui n'est pas une cible du lanceur, les couples ont été relevés à la
main dans le navigateur, code refusé compris : alerte 7,15:1, lien 5,53:1,
bouton 5,07:1, étiquette flottante 5,53:1, accroche 5,53:1.

## Le champ de code en six cases, le 27 août 2026

Sur le modèle de `auth-two-steps-basic.html`, relevé dans sa feuille : boîte de
**50 px**, corps `1.125rem`, texte centré, marge intérieure nulle, filet violet
sur la case active.

**Un écart assumé : les cases sont à angles droits.** La référence les arrondit,
mais les champs de cet espace ont été carrés le même jour, à la demande. La
disposition suit le modèle, le coin suit l'espace.

Ce qui compte plus que l'aspect, et qui ne se voit pas sur une capture :

- **Le champ unique reste celui qui se soumet.** Il est dans la page, masqué
  quand le script tourne, et c'est lui qui porte le nom attendu par le bundle.
  Aucun code serveur n'a changé.
- **L'écran marche sans JavaScript**, vérifié en coupant le script dans le
  navigateur : les six cases restent `hidden`, le champ unique paraît, et la
  connexion aboutit.
- **Les cases précèdent le champ unique dans le DOM**, et le contrôleur retire
  `autocomplete="one-time-code"` du champ masqué. Sans cela, `bin/lighthouse.mjs`
  et `bin/contrast.mjs` visaient le champ caché pour se connecter, et toute la
  mécanique de mesure tombait.
- **Une case porte son rang**, `aria-label` « Chiffre 3 sur 6 », et le groupe a
  sa légende. Six champs sans étiquette seraient six champs anonymes pour un
  lecteur d'écran.

Vérifié de bout en bout dans le navigateur : six cases visibles de 50 px, focus
sur la première à l'arrivée, six frappes réparties, `598771` recomposé dans le
champ soumis, et le back-office atteint.

## Les messages flash en toasts, le 27 août 2026

Sur le modèle de `ui-toasts.html`, relevé dans la feuille de la démo :
`350px` de large, marges `0.75rem` sur `0.406rem`, écart `1rem`, ombre large, et
**la couleur de sens dans la seule icône** - le toast lui-même reste la surface.
C'est ce dernier point qui rend la reprise sûre : aucun couple de la palette
n'est à remesurer, puisque le fond ne change pas.

**Un écart assumé sur le fond.** La démo pose la surface à 85 % d'opacité. Un
toast translucide fait dépendre le contraste de son texte de ce qui défile
dessous, et `bin/contrast.mjs` remonte au premier fond opaque : il mesurerait
autre chose que ce que l'oeil reçoit. Le fond est donc opaque.

**Placement** : en haut à droite, sous le bandeau, demandé en séance. Le
bandeau est collant et sa hauteur vient de son contenu, 55 px mesurés : elle est
désormais épinglée par `$topbar-height`, que le bandeau et les toasts lisent
tous les deux. Sans ce jeton partagé, un toast finirait par recouvrir le fil
d'Ariane au premier changement de densité.

### Le délai de disparition n'est pas un nombre rond

Arbitré : le toast part seul, et le délai suit le **temps de lecture** plutôt
qu'une constante. Quatre secondes pour le remarquer, plus cinquante
millisecondes par signe - environ 200 mots par minute - borné entre 5 et 12
secondes, avec un plancher à 8 secondes pour une erreur, dont le coût de rater
est plus élevé.

Deux filets qui vont avec, et qui viennent de Bootstrap sans une ligne à écrire :
le minuteur **se suspend** tant que le pointeur ou le focus est sur le toast, et
chaque toast porte sa croix de fermeture.

### Ce qui a été décidé, et volontairement pas fait

- **L'espace `security` garde ses alertes dans le flux.** « Identifiants
  invalides » appartient au formulaire qu'on vient de soumettre, pas à un coin
  de l'écran. Ses gabarits et ses tests ne changent pas.
- **Aucun titre inventé.** Le mot de l'en-tête est le sens - Fait, Attention,
  Erreur - et le message reste le corps. Un intitulé de plus n'aurait rien
  ajouté à une phrase.

### Huit assertions à reprendre, et la leçon deux fois apprise

`.alert`, `.alert-success` et `.alert-danger` n'existent plus dans le
back-office, et huit assertions les nommaient. Elles visent désormais le
**rôle** : `[role="status"]` pour une confirmation, `[role="alert"]` pour un
refus. C'est exactement ce que le passage des onglets à filet aux pilules avait
déjà enseigné : une classe de composant ne se nomme pas dans un test, le rôle
ARIA lui survit.

### Mesuré

Un vrai enregistrement de site, dans les deux thèmes. Fond de la carte, corps à
5,53:1 en sombre et 6,63:1 en clair, titre à 7,99 et 11,2, icône à 5,61 et 9,21.
Les trois sens relevés sur le même fond, largeur 350 px, `role="status"` et
`aria-live="polite"` pour une confirmation, et **la disparition seule vérifiée
dans les deux thèmes**.

| Sens | Sombre | Clair |
|------|--------|-------|
| Succès | 5,61:1 | 9,21:1 |
| Attention | 6,09:1 | 8,78:1 |
| Erreur | 5,48:1 | 11,47:1 |

Les tons d'attention et d'erreur ont été lus sur un toast injecté dans la page :
le projet n'a aucun flash `danger` déclenchable sans risque, et le chemin réel
rend le même balisage avec la même classe.

## La pagination, le 27 août 2026

Relevée dans la feuille de la démo, sur `app-access-roles.html` :

| Valeur | Mesure |
|--------|--------|
| Taille d'un bouton | `2.2509rem` plus les deux pixels de filet, soit 38 px |
| Rayon | `0.375rem` |
| Marges | `0.4809rem` sur `0.5rem`, corps `0.9375rem` |
| Écart entre boutons | `0.375rem` |
| Filet | 1 px, un mix à 22 % |
| Page active | blanc sur le primaire |
| Survol | le primaire sur le lavis violet |

**Un piège dans la feuille de la démo** : elle déclare
`--bs-pagination-border-radius: 50%`, ce qui suggère des cercles, puis une règle
plus bas repose `border-radius: .375rem` sur `.page-link` et gagne. Ce sont des
carrés à coins doux, ce que la lecture de `app-user-list` avait déjà consigné.
Prendre la propriété personnalisée au mot aurait donné des ellipses.

### Ce qui vient de Bootstrap sans une ligne

Onze variables passent par la clause `with`. **Trois ont été retirées après
essai** : `$pagination-active-color`, `-active-bg` et `-active-border-color`
valent déjà `$component-active-color` et `$component-active-bg`, donc blanc sur
le primaire - exactement la référence. Les écrire n'aurait fait que répéter le
défaut, et la première tentative a d'ailleurs cassé la compilation en cherchant
`bootstrap.$white` avant que le module soit chargé.

Il ne reste **qu'une règle** : Bootstrap dimensionne un bouton par son contenu,
donc « ... » sortait plus large qu'un chiffre. Le carré de 38 px et le centrage
sont les deux choses que la référence fait et qu'aucune variable ne porte.

### Quatre flèches, et leur nom

La pagination du projet n'avait que des numéros. Elle gagne première, précédente,
suivante et dernière, comme la référence. Chaque flèche est un lien nommé -
`aria-label`, glyphe `aria-hidden` - et devient un `span aria-hidden` quand elle
ne mène nulle part : un lien inerte de plus n'apporte rien à un lecteur d'écran,
les numéros disent déjà où l'on est.

Le balisage des quatre est écrit **une fois**, dans une macro du même gabarit.

### Mesuré

Page 1 et page 30, dans les deux thèmes : bouton 38x38, rayon 6 px, filet 1 px
en `$rule-strong` - `#7a7d8d` en sombre, `#8f8d99` en clair -, libellé en
`$slate`, page active blanche sur `#685dd8`, écart 6 px. Onze éléments page 1,
quinze page 30 avec ses deux ellipses. Zéro écart de contraste sur les vingt-et-un
audits.
