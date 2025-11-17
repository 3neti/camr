import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath } from 'node:url'

export default defineConfig({
  plugins: [vue()],
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./tests/frontend/setup.ts'],
    include: ['tests/frontend/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html'],
      exclude: [
        'node_modules/',
        'tests/',
        '**/*.config.{js,ts}',
        '**/resources/js/components/ui/**', // Generated UI components
      ],
    },
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
      '@/components': fileURLToPath(new URL('./resources/js/components', import.meta.url)),
      '@/composables': fileURLToPath(new URL('./resources/js/composables', import.meta.url)),
      '@/layouts': fileURLToPath(new URL('./resources/js/layouts', import.meta.url)),
      '@/lib': fileURLToPath(new URL('./resources/js/lib', import.meta.url)),
      '@/types': fileURLToPath(new URL('./resources/js/types', import.meta.url)),
      '@/ui': fileURLToPath(new URL('./resources/js/components/ui', import.meta.url)),
    },
  },
})
