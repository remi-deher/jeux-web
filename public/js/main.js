// public/js/main.js

import App from './App.js';
import Home from './views/Home.js';
import Morpion from './views/Morpion.js';

const routes = [
    { path: '/', component: Home },
    { path: '/games/morpion', component: Morpion },
];

const router = VueRouter.createRouter({
    history: VueRouter.createWebHistory(),
    routes,
});

const app = Vue.createApp(App);
app.use(router);
app.mount('#app');
