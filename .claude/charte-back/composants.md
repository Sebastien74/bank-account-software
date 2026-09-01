# Composants

Ce que chaque brique fait, et ce qu'elle vaut pour le CMS. Les valeurs sont
mesurées, les dispositions sont observées sur les captures citées.

## Carte

Le conteneur unique du gabarit. Fond `#2f3349`, aucun filet, rayon `0.375rem`,
marge intérieure `1.5rem`. Titre en `--bs-heading-color`, sous-titre en gris
juste dessous, et un menu à trois points en haut à droite quand la carte porte
des actions.

Tout est une carte : une tuile de chiffre, un bloc de formulaire, un tableau,
une zone de dépôt. Le CMS a déjà ce parti, sous le nom `.back-panel`, ce qui
rend la reprise directe.

## Tableau (`app-user-list`, regardé)

L'archétype le plus proche de ce que le CMS fait déjà. De haut en bas :

1. **une rangée de tuiles de chiffres**, quatre cartes égales, chacune avec son
   libellé, sa valeur, sa variation en vert ou rouge, et une icône dans un carré
   coloré atténué ;
2. **une carte de filtres**, trois listes déroulantes pleine largeur ;
3. **une barre d'outils** : nombre par page à gauche, puis à droite le champ de
   recherche, un bouton d'export secondaire, et l'action primaire en violet
   plein avec un plus ;
4. **le tableau** : case à cocher de sélection, colonne principale avec avatar
   plus deux lignes empilées (nom en clair, courriel en gris), colonnes
   secondaires, pastille de statut colorée, colonne d'actions à droite avec
   corbeille, oeil et menu à trois points ;
5. **le pied** : `Showing 1 to 10 of 50 entries` à gauche, pagination à droite.

Les en-têtes de colonne sont en capitales, petites, en gris. C'est exactement la
disposition que le CMS vient d'adopter pour son pied de liste, ce qui confirme
le choix plutôt qu'il ne le remet en cause.

## Formulaire à onglets (`pages-account-settings-account`, regardé)

Les onglets sont des **pilules**, pas des onglets à filet : l'onglet actif est un
rectangle violet plein à coins arrondis, porté par une ombre colorée, et chaque
onglet a son icône à gauche du libellé. Ils sont **au-dessus de la carte**, pas
dedans.

Dans la carte : grille à deux colonnes, libellé au-dessus du champ, et en pied
`Save changes` en primaire plein plus `Cancel` en secondaire effacé. Le bloc
dangereux est une **carte séparée** en bas, avec son alerte orangée et sa case de
confirmation avant le bouton rouge.

Le CMS pose ses onglets à filet dans la carte. La pilule est plus lisible et se
prête mieux aux sept sections d'un formulaire de contenu.

## Formulaire de contenu (`app-ecommerce-product-add`, regardé)

L'archétype qui correspond à l'édition d'une page ou d'une actualité :

- **en-tête d'écran** hors carte : titre, sous-titre, et à droite trois actions
  graduées, `Discard` effacé, `Save draft` en primaire atténué, `Publish product`
  en primaire plein ;
- **deux colonnes** : une principale large qui empile les cartes de contenu, une
  latérale étroite qui empile les cartes de réglage ;
- **une carte par sujet** : informations, image, variantes d'un côté ; prix,
  classement de l'autre.

C'est plus lisible que la tabulation de tout, et directement transposable : le
corps du contenu à gauche, le référencement et la publication à droite.

## Zone de dépôt

Rectangle à **filet en pointillés**, icône de téléversement centrée, un titre
`Drag and drop your image here`, un `or`, puis un bouton `Browse image` en
primaire atténué. Fond identique à la carte, aucun aplat.

Le CMS a déjà une zone de dépôt, par `ux-dropzone`. La différence est le filet en
pointillés et le bouton de secours au centre, qui dit à l'éditeur que le clic
marche aussi.

## Boutons

| État | Valeur |
|------|--------|
| Fond | `#7367f0` |
| Survol | `#685dd8` |
| Actif | `#685dd8`, filet `#564db4` |
| Texte | `#ffffff` |

Trois niveaux se lisent sur les captures : plein pour l'action principale, teinte
atténuée pour l'action secondaire utile, et effacé pour l'action neutre. Le
gabarit ne se sert pas du contour clair pour l'action secondaire, il se sert de
la couleur atténuée.

## Pastille de statut

Rayon `0.125rem`, graisse `500`, corps `0.8667em`, marges `0.4235em` sur
`0.77em`. Sur le tableau des utilisateurs : `Active` en vert atténué, `Pending`
en orange atténué, `Inactive` en gris. Le fond est la couleur atténuée, le texte
la teinte claire de la même couleur - jamais du blanc sur teinte pleine.

Le CMS a `.back-status` avec `is-live` et `is-draft`. La règle du couple fond
atténué plus texte emphasé est meilleure : elle tient le contraste par
construction.

## Menu latéral

Largeur `16.25rem`, fond `--bs-paper-bg`, donc **la même surface que les
cartes** : le menu est une carte pleine hauteur. L'entrée active est un rectangle
violet plein à coins arrondis, avec un point en puce. Les entrées sont groupées
sous des intertitres en capitales grises, `APPS & PAGES`, `COMPONENTS`,
`FORMS & TABLES`.

En dessous de la rupture, **observation sur la capture mobile** : le menu
disparaît derrière un bouton à trois barres, et les cartes passent en une
colonne. Rien d'autre ne change.

## Barre du haut

Champ de recherche à gauche avec une loupe et le raccourci `[CTRL + K]`, et à
droite une série d'icônes : langue, thème clair ou sombre, raccourcis,
notifications avec un point, puis l'avatar. Fond `--bs-body-bg`, donc **plus
sombre que le menu** : la barre s'enfonce, le menu avance.

## Pagination

**Capturée, pas encore regardée en détail** (`ui-pagination-breadcrumbs`). Ce qui
se voit sur `app-user-list` : boutons carrés à coins arrondis, page active en
violet plein, flèches simples et doubles aux extrémités.

## Arborescence (`extended-ui-treeview`, regardé)

Basée sur jsTree, déclinée en six variantes dans la même page : simple, icônes
personnalisées, menu contextuel, glisser-déposer, cases à cocher, chargement
ajax. Les noeuds portent une icône de dossier ou de fichier, et un chevron de
pliage à gauche.

À noter pour l'arbre des pages du CMS : le glisser-déposer y est déjà fait par
Sortable, pas par jsTree. Reprendre l'aspect n'oblige pas à changer de
bibliothèque, et **ne le devrait pas** : jsTree tirerait jQuery.

## Connexion (`auth-login-cover`, regardé)

Deux panneaux. À gauche, environ deux tiers, une illustration tridimensionnelle
sur le fond de page. À droite, un tiers, le formulaire : titre accueillant,
sous-titre, deux champs, une case `Remember Me` à gauche et un lien
`Forgot Password?` à droite sur la même ligne, puis le bouton pleine largeur.

Le projet a déjà un espace `security` en deux panneaux avec photographie pleine
cadre, arbitré et documenté. La référence confirme la structure ; elle n'apporte
rien qui justifie de la refaire.
