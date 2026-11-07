import { readdirSync } from 'node:fs';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// Illustrations d'ambiance appelées depuis Blade (Vite::asset) : versionnées comme le reste.
const ambiance = readdirSync('resources/images/ambiance')
    .filter((file) => file.endsWith('.webp'))
    .map((file) => `resources/images/ambiance/${file}`);

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', ...ambiance],
            refresh: true,
        }),
    ],
    build: {
        // Les polices sont copiées telles quelles : pas d'intégration en base64 dans le CSS.
        assetsInlineLimit: 0,
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
