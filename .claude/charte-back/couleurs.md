# Palette sombre

Toutes les valeurs de ce fichier sont **mesurées** : relevées dans le bloc
`[data-bs-theme=dark]` de `assets/vendor/css/core.css`. Le gabarit est un
Bootstrap 5 piloté par propriétés personnalisées, ce qui rend la palette
lisible sans interprétation.

## Fonds et texte

| Rôle | Valeur | Où |
|------|--------|-----|
| Fond de page | `#25293c` | `--bs-body-bg` |
| Fond de surface | `#2f3349` | `--bs-paper-bg`, et `--bs-card-bg` y renvoie |
| Texte courant | `#acabc1` | `--bs-body-color` |
| Titres | `#cfcde4` | `--bs-heading-color` |
| Texte accentué | `#ffffff` | `--bs-emphasis-color` |
| Texte secondaire | `#76778e` | `--bs-secondary-color` |
| Filets | `#44485e` | `--bs-border-color` |
| Filets translucides | `rgba(255,255,255,0.15)` | `--bs-border-color-translucent` |
| Lien | `#aba4f6` | `--bs-link-color` |

Deux fonds seulement, et onze points d'écart de luminosité entre eux : la carte
se détache du fond sans filet ni ombre. C'est ce qui donne au gabarit son air
calme, et c'est **l'inverse** du parti actuel du projet, qui pose une surface
blanche sur un papier clair et sépare par un filet.

## Couleurs de sens

| Rôle | Base | Fond atténué | Filet atténué | Texte emphasé |
|------|------|--------------|---------------|----------------|
| Primaire | `#7367f0` | `#3a3b64` | `#4a478a` | `#aba4f6` |
| Secondaire | `#808390` | `#3c4054` | `#4f5265` | `#b3b5bc` |
| Succès | `#28c76f` | `#2e4b4f` | `#2c6d58` | `#7edda9` |
| Info | `#00bad1` | `#27495f` | `#1d687e` | `#66d6e3` |
| Alerte | `#ff9f43` | `#504448` | `#805d47` | `#ffc58e` |
| Danger | `#ff4c51` | `#50374a` | `#803d4c` | `#ff9497` |
| Clair | `#494a5d` | `#33374c` | `#393c51` | `#acabc1` |
| Sombre | `#6b6c9d` | `#393c56` | `#46496a` | `#e1def5` |

Chaque couleur vient donc par **quatre** : la teinte pleine pour un bouton, un
fond atténué et un filet atténué pour une pastille ou une alerte, et une teinte
claire pour du texte sur fond sombre. C'est la mécanique qui évite les
contrastes hasardeux, et elle est reprenable telle quelle : Bootstrap 5.3 la
fournit nativement par `$theme-colors` et ses dérivés `-bg-subtle`,
`-border-subtle` et `-text-emphasis`.

Le contraste sur fond de carte, à vérifier avant reprise : `#acabc1` sur
`#2f3349` mesure environ 6,4:1, largement au-dessus du plancher de 4,5. Le
secondaire `#76778e` sur la même carte tombe autour de 3,1:1 : **il ne tient
pas pour du texte courant**, seulement pour du décor. Ces deux chiffres sont
une **hypothèse** de calcul, à refaire avec un outil avant de s'en servir.

## Ombres

| Rôle | Valeur |
|------|--------|
| Petite | `0 0.125rem 0.5rem 0 rgba(47,43,61,0.12)` |
| Normale | `0 0.1875rem 0.75rem 0 rgba(47,43,61,0.14)` |
| Grande | `0 0.25rem 1.125rem 0 rgba(47,43,61,0.16)` |

L'ombre est teintée `#2f2b3d`, la couleur `--bs-black` du gabarit, pas du noir
pur. Sur un fond sombre elle se voit à peine ; elle sert surtout au mode clair
du même gabarit. Un onglet actif porte en plus une ombre colorée,
`0 .125rem .375rem 0 rgba(var(--bs-primary-rgb), 0.3)`, qui est la seule lueur
du système.

## Comparaison avec la palette du projet

Le projet mesure aujourd'hui, dans `assets/back/_variables.scss` :

| Rôle | Projet | Vuexy |
|------|--------|-------|
| Primaire | `#9c4520` | `#7367f0` |
| Secondaire | `#575a5f` | `#808390` |
| Fond de travail | `#f1f2f4` | `#25293c` |
| Surface | `#ffffff` | `#2f3349` |
| Danger | `#a4161a` | `#ff4c51` |
| Rayon | `0` | `0.375rem` |

Les deux systèmes ne se mélangent pas : l'un est clair, terreux, à angles
droits, l'autre sombre, violet, arrondi. **Il faut choisir**, pas moyenner.
C'est ce que dit `ecarts-projet.md`.
