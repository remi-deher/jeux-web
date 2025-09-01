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
            <div class="footer-content">
                <p>Développé par DEHER Rémi &copy; {{ new Date().getFullYear() }} Mon Portail de Jeux. Tous droits réservés.</p>
                <p>
                    <span>Projet sous licence MIT.</span> | 
                    <a href="https://github.com/remi-deher/jeux-web" target="_blank" rel="noopener noreferrer">
                        Voir le dépôt GitHub
                    </a>
                </p>
            </div>
        </footer>
        `
}
