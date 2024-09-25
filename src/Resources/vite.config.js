import { defineConfig, splitVendorChunkPlugin, normalizePath } from 'vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { viteExternalsPlugin } from 'vite-plugin-externals';
import AutoImport from 'unplugin-auto-import/vite';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import path, { resolve } from 'path';

import config from './config';

//Check ASSET path
// process.env.ASSET_URL = '/vendor/crudadmin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['js/app.js', 'scss/eshop.scss'],
            publicDirectory: 'dist',
            buildDirectory: './',
            refresh: false,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: '',
                    includeAbsolute: true,
                },
            },
        }),
        AutoImport({
            imports: ['vue', 'vue-router', 'pinia'],
            resolvers: [],
            dirs: ['./js/**'],
            vueTemplate: true,
            cache: true,
            injectAtEnd: false,
        }),
        viteExternalsPlugin(
            {
                vue: 'Vue',
                lodash: '_',
                moment: 'moment',
                pinia: 'pinia',
                vuedraggable: 'vuedraggable',
            },
            // call after auto-imports
            { enforce: 'post' }
        ),
        splitVendorChunkPlugin(),
        viteStaticCopy({
            targets: config.paths.map((path) => {
                return {
                    src: normalizePath(resolve(__dirname, './dist/assets/*')),
                    dest: path + '/js',
                };
            }),
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                entryFileNames: `assets/[name].js`,
                chunkFileNames: `assets/[name].js`,
                assetFileNames: `assets/[name].[ext]`,
            },
            external: ['vue', 'pinia', 'lodash', 'moment', 'draggable'],
        },
    },
});
