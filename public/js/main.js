// public/js/main.js

import App from './App.js';
import Home from './views/Home.js';
import Morpion from './views/Morpion.js';
import Puissance4 from './views/Puissance4.js';

const routes = [
    { path: '/', component: Home },
    { path: '/games/morpion', component: Morpion },
    { path: '/games/puissance4', component: Puissance4 },
];

const router = VueRouter.createRouter({
    history: VueRouter.createWebHistory(),
    routes,
});

const app = Vue.createApp(App);
app.use(router);
app.mount('#app');
