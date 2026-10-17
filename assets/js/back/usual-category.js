/**
 * Module : Catégorie habituelle d'un tiers
 * Copyright : 2025
 * Author : Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * Licensed under MIT (https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
 *
 * Choisir un tiers présélectionne la catégorie que son historique montre.
 * C'est une proposition : le champ reste ouvert, et une catégorie déjà posée
 * à la main n'est jamais écrasée par la suite.
 */
export default function (selectors) {

    selectors.forEach(function (source) {

        let map;
        try {
            map = JSON.parse(source.dataset.usualSubCategories || '{}');
        } catch (error) {
            return;
        }

        const target = document.getElementById(source.dataset.usualTarget || '');
        if (!target) {
            return;
        }

        /* Choices remplace le select a l'ecran : poser la valeur sur l'element
           natif changerait ce qui est envoye sans changer ce qui est lu. */
        const apply = (value) => {
            if (target._choices) {
                target._choices.setChoiceByValue(value);
                return;
            }
            target.value = value;
            target.dispatchEvent(new Event('change', {bubbles: true}));
        };

        source.addEventListener('change', () => {
            const usual = map[source.value];
            if (!usual) {
                return;
            }

            const option = target.querySelector('option[value="' + usual + '"]');
            if (!option || target.value === String(usual)) {
                return;
            }

            apply(String(usual));
        });
    });
}
