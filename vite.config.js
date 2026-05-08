import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    // server: {
    //     host: '192.168.101.7', // <-- Add or ensure this line exists
    //     // port: 5173 // Optional: you can specify the port if needed
    //     cors: {
    //         origin: '*', // This allows requests from any origin (for development)
    //         methods: ['GET', 'HEAD', 'PUT', 'POST', 'DELETE', 'PATCH'],
    //         allowedHeaders: ['Content-Type', 'Authorization', 'X-Requested-With'],
    //     },
    // }
});
