# Typographie, formes, densité

Valeurs **mesurées** dans `core.css`, sauf mention contraire.

## Typographie

| Élément | Valeur |
|---------|--------|
| Famille | `"Public Sans"`, puis `-apple-system`, `Segoe UI`, `Oxygen`, `Ubuntu`, `Cantarell`, `Fira Sans`, `Droid Sans` |
| Source | Google Fonts, `Public+Sans:ital,wght@0,300..700;1,300..700` |
| Corps | `0.9375rem`, soit 15 px |
| Graisse courante | `400` |
| Interligne | `1.375` |
| Monospace | `SFMono-Regular`, `menlo`, `monaco`, `consolas` |

Deux choses à noter. Le corps est à **15 px**, pas 16 : le gabarit resserre tout
d'un cran, ce qui explique sa densité. Et `Fira Sans` figure dans la pile de
secours de Vuexy, or c'est la police que le projet auto-héberge déjà : une
reprise pourrait garder Fira Sans et ne rien télécharger, au prix d'un dessin de
lettre un peu moins neutre.

**Observation** sur les captures : les titres de carte sont en graisse moyenne,
autour de `500`, en `--bs-heading-color`, et les sous-titres juste dessous en
gris secondaire, plus petits. Le couple titre plus sous-titre est systématique,
sur chaque carte du tableau de bord comme sur chaque section de formulaire.

## Rayons

| Jeton | Valeur |
|-------|--------|
| `--bs-border-radius` | `0.375rem` |
| `--bs-border-radius-sm` | `0.25rem` |
| `--bs-border-radius-lg` | `0.5rem` |
| `--bs-border-radius-xl` | `0.625rem` |
| `--bs-border-radius-pill` | `50rem` |
| Carte | `0.375rem` |
| Pastille | `0.125rem` |

La pastille est nettement moins arrondie que la carte, `0.125rem` contre
`0.375rem`. Ce n'est pas une inattention : une pastille de statut lue en tableau
reste rectangulaire pour ne pas se confondre avec un bouton.

## Filets

`--bs-border-width: 1px`. La carte, elle, porte `--bs-card-border-width: 0` :
**aucun filet**, la séparation vient du seul écart de fond. Le tableau garde un
filet bas de 1 px entre ses lignes.

## Densité

| Élément | Valeur |
|---------|--------|
| Marge intérieure de carte | `1.5rem` en X et en Y |
| Écart titre / sous-titre de carte | `0.5rem` |
| Cellule de tableau | `0.782rem` vertical, `1.25rem` horizontal |
| Champ de saisie | `0.426rem` vertical, `0.9375rem` horizontal |
| Interligne de champ | `1.625` |
| Largeur du menu | `16.25rem`, soit 260 px |

Le champ est **serré en hauteur** et large en marge latérale, ce qui donne des
lignes de formulaire compactes sans que la saisie soit à l'étroit. La cellule de
tableau suit la même logique.

## Fond des champs

`.form-control` a `background-color: rgba(0,0,0,0)` : **le champ est
transparent**, il prend le fond de la carte, et n'est dessiné que par son filet.
Le filet lui-même est calculé,
`color-mix(in sRGB, var(--bs-base-color) 22%, var(--bs-paper-bg))`. C'est ce qui
fait que les formulaires ne ressemblent pas à une grille de boîtes.

**Observation** sur la capture des réglages : le champ actif est cerclé d'un
filet primaire net, sans halo diffus. Le champ vide affiche son gabarit de
saisie en gris secondaire.
