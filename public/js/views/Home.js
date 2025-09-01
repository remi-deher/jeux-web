// public/js/views/Home.js

export default {
    data() {
        return {
            games: []
        };
    },
    mounted() {
        // On récupère les jeux depuis notre API PHP
        fetch('/api/games')
            .then(response => response.json())
            .then(data => {
                this.games = data;
            });
    },
    template: `
        <section id="game-list">
            <header class="page-title">
                <h1>Portail de jeux si tu t'ennuies ;)</h1>
            </header>
            <h2>Nos Jeux</h2>
            <div class="games-container">
                <div v-for="game in games" :key="game.id" class="game-card">
                    <img :src="game.image_url" :alt="game.name">
                    <h3>{{ game.name }}</h3>
                    <p>{{ game.description }}</p>
                    <div class="game-info">
                        <span class="player-count" :data-game-id="game.id">
                            <span class="count">0</span> joueur(s) en ligne
                        </span>
                        <router-link :to="'/games/' + game.slug" class="play-button">Jouer</router-link>
                    </div>
                </div>
            </div>
        </section>
    `
};
