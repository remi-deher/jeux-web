// public/js/App.js
import Chat from './components/Chat.js';

export default {
    components: {
        Chat
    },
    template: `
        <nav class="navbar">
            <div class="nav-container">
                <router-link to="/" class="nav-brand">Mon Portail de Jeux</router-link>
                <div class="nav-admin">
                    <a href="/admin">Admin</a>
                </div>
            </div>
        </nav>
        <main>
            <div class="main-content">
                <router-view class="main-view"></router-view>
                
                <Chat />
            </div>
        </main>
        <footer>
            <p>&copy; {{ new Date().getFullYear() }} Mon Portail de Jeux. Tous droits réservés.</p>
        </footer>
    `
}
