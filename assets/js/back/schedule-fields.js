/**
 * Module : Champs d'échéancier
 * Copyright : 2026
 * Author : Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * Licensed under MIT (https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
 *
 * Le nombre d'échéances et le mois de départ ne concernent qu'un paiement en
 * plusieurs fois. Affichés sur toutes les lignes, ils noyaient le tableau sous
 * des champs sans objet ; cachés en dur, le choix « En plusieurs fois » n'aurait
 * eu nulle part où se régler. Ils suivent donc la périodicité, sans recharger.
 */
export default function (selectors) {

    selectors.forEach(function (source) {

        const target = document.getElementById(source.dataset.scheduleTarget || '');
        if (!target) {
            return;
        }

        /* `d-none` est porté par une enveloppe sans autre utilitaire d'affichage :
           posé sur le `d-flex` lui-même, les deux règles sont `!important` et
           c'est `d-flex`, défini après, qui l'emporterait. */
        const apply = () => target.classList.toggle('d-none', 'instalments' !== source.value);

        /* Choices remplace le select à l'écran mais relaie `change` sur
           l'élément natif : c'est lui qu'on écoute, dans les deux cas. */
        source.addEventListener('change', apply);
        apply();
    });
}
