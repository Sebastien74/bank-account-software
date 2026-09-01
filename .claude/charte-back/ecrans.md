# Archétypes d'écran

La démo compte **149 pages**. Treize ont été capturées, six regardées. Ce fichier
dit lesquelles, et à quel écran du CMS chacune répond.

## Regardées

| Page de la démo | Écran du CMS | Ce qu'on en retient |
|-----------------|--------------|---------------------|
| `index` | tableau de bord | tuiles de chiffres, cartes de graphiques, une carte mise en avant en violet plein |
| `app-user-list` | tout index | tuiles, filtres, barre d'outils, tableau, pied compte plus pagination |
| `pages-account-settings-account` | formulaire à onglets | onglets en pilules hors carte, grille à deux colonnes, bloc dangereux à part |
| `app-ecommerce-product-add` | édition de page ou d'actualité | en-tête à actions graduées, colonne principale plus rail latéral |
| `auth-login-cover` | espace `security` | deux panneaux, illustration à gauche, formulaire à droite |
| `extended-ui-treeview` | arbre des pages | six variantes jsTree, dont glisser-déposer et cases à cocher |

## Capturées, pas encore analysées

Elles sont dans `captures/`, disponibles pour la phase d'intégration.

| Page | Intérêt attendu pour le CMS |
|------|------------------------------|
| `tables-datatables-basic` | tableau nu, sans les tuiles ni les filtres |
| `app-ecommerce-product-list` | liste avec vignette de produit, comme l'index actualités |
| `form-layouts-vertical` | disposition de formulaire de référence |
| `form-layouts-sticky` | barre d'actions collante, pour un formulaire long |
| `forms-file-upload` | variantes de zone de dépôt |
| `ui-modals` | modale, dont la confirmation de suppression |
| `ui-pagination-breadcrumbs` | pagination et fil d'Ariane |
| `pages-misc-error` | page d'erreur, pour les 403 et 404 des trois espaces |

## Pages de la démo sans équivalent dans le CMS

À ne pas reprendre, pour ne pas importer une complexité sans emploi : les
tableaux de bord métier (`dashboards-crm`, `app-academy-dashboard`,
`app-logistics-dashboard`), la messagerie (`app-email`, `app-chat`), le
calendrier, le kanban, la facturation, l'académie, le commerce au-delà de la
liste et du formulaire, les pages de façade (`../front-pages/*`), les cartes
Leaflet, et les vitrines de composants (`ui-*`, `icons-*`) qui documentent
Bootstrap plus qu'elles ne dessinent un écran.

## Écrans du CMS sans équivalent dans la démo

Ce que l'intégration devra dessiner sans modèle :

- **la médiathèque en grille**, avec sa tuile, son badge d'usage et son sélecteur
  en modale. La démo n'a pas de gestionnaire de fichiers ;
- **la composition Zone / Col / Block**, qui n'a aucun équivalent : ni la démo ni
  Bootstrap ne proposent de constructeur de page ;
- **le sélecteur de langue par onglet de traduction**, avec ses drapeaux ;
- **le recadrage par écran** de la médiathèque.

Ces quatre-là resteront des inventions du projet. La charte leur donnera ses
couleurs et ses formes, pas leur disposition.
