import {startStimulusApp} from '@symfony/stimulus-bridge';
import 'bootstrap';

import './styles/app.scss';

export const app = startStimulusApp(require.context('@symfony/stimulus-bridge/lazy-controller-loader!./controller', true, /\.js/));

