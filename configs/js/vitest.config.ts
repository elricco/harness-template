// Vorlage: in den Repo-Root kopieren.
// TEMPLATE: Vue-Variante. Fuer Vanilla-Web-Projekte: vue-Plugin entfernen,
// environment auf 'node' stellen (schneller) und DOM-Tests per Datei-Pragma
// /* @vitest-environment jsdom */ markieren.
import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  test: {
    environment: 'jsdom',
    globals: true,
    include: ['src/**/*.{test,spec}.ts'],
    coverage: {
      provider: 'v8',
      include: ['src/**/*.ts'],
      exclude: ['src/**/*.{test,spec}.ts'],
    },
  },
})
