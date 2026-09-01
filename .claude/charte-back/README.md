# Charte du back-office - analyse du modèle Vuexy

Analyse de la référence retenue pour la reprise visuelle du back-office. **Rien
n'est intégré à ce stade** : ce dossier décrit.

Les arbitrages sont pris, eux, et consignés en tête de `ecarts-projet.md` :
la charte suit la démo, le back passe en sombre, Public Sans auto-hébergée, et
les libellés flottants restent la seule exception. Le même fichier porte la
liste des points de retouche, relevés en montant l'intégration puis en la
défaisant.

Référence : Vuexy HTML Admin Template, gabarit `vertical-menu-template-dark`.
<https://demos.pixinvent.com/vuexy-html-admin-template/html/vertical-menu-template-dark/index.html>

## Ce que contient ce dossier

| Fichier | Contenu |
|---------|---------|
| `couleurs.md` | la palette sombre exacte, relevée dans la feuille de style |
| `formes-typo.md` | typographie, rayons, ombres, densité |
| `composants.md` | carte, tableau, champ, bouton, pastille, onglets, menu, pagination |
| `ecrans.md` | les archétypes d'écran, mis en regard de ceux du CMS |
| `ecarts-projet.md` | les frictions avec les conventions actuelles du projet |

## Mesure et honnêteté

`PRECO.md` impose qu'une référence visuelle soit ouverte dans un vrai
navigateur, capturée, et sa feuille de style lue. Ce dossier distingue donc :

- **mesure** : valeur relevée dans `assets/vendor/css/core.css` de la démo, ou
  lue sur une capture. Elle est donnée telle quelle.
- **observation** : ce qui se voit sur une capture regardée.
- **hypothèse** : ce qui n'a été ni mesuré ni regardé. Signalée comme telle.

La démo compte **149 pages**. Treize ont été capturées, **six ont été
regardées** : tableau de bord, liste d'utilisateurs, réglages à onglets,
création de produit, connexion, arborescence. Les sept autres sont capturées
mais pas encore analysées, et `ecrans.md` le dit ligne par ligne.

## Reproduire les captures

`captures/` n'est pas versionné : trois mégaoctets de PNG régénérables ne
gagnent pas leur place dans le dépôt.

```bash
B="https://demos.pixinvent.com/vuexy-html-admin-template/html/vertical-menu-template-dark"
C="/c/Program Files/Google/Chrome/Application/chrome.exe"

for p in index app-user-list pages-account-settings-account app-ecommerce-product-add          auth-login-cover extended-ui-treeview tables-datatables-basic          app-ecommerce-product-list form-layouts-vertical form-layouts-sticky          forms-file-upload ui-modals ui-pagination-breadcrumbs pages-misc-error; do
  "$C" --headless=new --disable-gpu --hide-scrollbars --window-size=1600,1400        --virtual-time-budget=9000 --screenshot="captures/$p.png" "$B/$p.html"
done
```

Le mobile se prend avec `--window-size=414,900`. La feuille de style se
récupère par :

```bash
curl -s -A "Mozilla/5.0"   "https://demos.pixinvent.com/vuexy-html-admin-template/assets/vendor/css/core.css"   -o core.css
```

## Licence

Vuexy est un gabarit commercial de Pixinvent. Ce dossier ne recopie ni son code
ni ses images : il relève des valeurs et décrit des dispositions. Une reprise
qui irait plus loin que l'inspiration demande la licence. À trancher avant
l'intégration.
