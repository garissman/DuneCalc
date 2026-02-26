import { resolve } from 'path';
import vue from '@vitejs/plugin-vue';
import { defineConfig } from 'vitest/config';

export default defineConfig({
    plugins: [vue()],
    test: {
        globals: true,
        environment: 'jsdom',
        exclude: ['vendor/**', 'node_modules/**', '.worktrees/**'],
        coverage: {
            provider: 'v8',
            include: ['resources/js/pages/**/*.vue', 'resources/js/lib/**/*.ts'],
            exclude: [
                'resources/js/wayfinder/**',
                'resources/js/types/**',
                'resources/js/actions/**',
                'resources/js/routes/**',
                'resources/js/pages/Welcome.vue',
            ],
            thresholds: {
                lines: 100,
                branches: 100,
                functions: 100,
                statements: 100,
            },
        },
    },
    resolve: {
        alias: {
            '@': resolve(__dirname, './resources/js'),
        },
    },
});
