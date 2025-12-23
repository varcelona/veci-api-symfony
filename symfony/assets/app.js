import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

import L from 'leaflet'
import icon from 'leaflet/dist/images/marker-icon.png'
import iconShadow from 'leaflet/dist/images/marker-shadow.png'

delete L.Icon.Default.prototype._getIconUrl

L.Icon.Default.mergeOptions({
  iconUrl: icon,
  shadowUrl: iconShadow
})


// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';
import 'leaflet/dist/leaflet.css'
