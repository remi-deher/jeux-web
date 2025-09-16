// public/js/main.js

import App from './App.js';
import Home from './views/Home.js';
import Morpion from './views/Morpion.js';
import Puissance4 from './views/Puissance4.js';
import BatailleNavale from './views/BatailleNavale.js';
import Pong from './views/Pong.js'; // Ajout de l'import pour Pong

const routes = [
    { path: '/', component: Home },
    { path: '/games/morpion', component: Morpion },
    { path: '/games/puissance4', component: Puissance4 },
    { path: '/games/bataille-navale', component: BatailleNavale },
    { path: '/games/pong', component: Pong }, // Ajout de la route pour Pong
];

const router = VueRouter.createRouter({
    history: VueRouter.createWebHistory(),
    routes,
});

const app = Vue.createApp(App);
app.use(router);
app.mount('#app');
