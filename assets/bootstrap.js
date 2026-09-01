/**
 * Module : Stimulus bootstrap
 * Copyright : 2026
 * Author : Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * Licensed under MIT (https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
 *
 * Démarre l'application Stimulus et enregistre les contrôleurs déclarés dans
 * assets/controllers.json : Turbo et Chart.js sont chargés par ce biais.
 */

import {startStimulusApp} from '@symfony/stimulus-bridge';

export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));
