import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

const apiProxyTarget = process.env.VITE_API_PROXY_TARGET || 'http://localhost:8080'

export default defineConfig(({ mode }) => {
  // 门户版构建：npm run build:admin（读取 .env.admin 的 VITE_ADMIN_BASE，避免 Windows shell 路径转换）
  const env = loadEnv(mode, process.cwd(), '')
  return {
    base: env.VITE_ADMIN_BASE || '/',
    plugins: [vue()],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
    server: {
      port: 5173,
      open: true,
      proxy: {
        '/api': {
          target: apiProxyTarget,
          changeOrigin: true,
        },
      },
    },
  }
})
