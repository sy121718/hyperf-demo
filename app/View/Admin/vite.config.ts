import path from "node:path";
import tailwindcss from "@tailwindcss/vite";
import react from "@vitejs/plugin-react";
import { defineConfig } from "vite";

export default defineConfig({
  plugins: [react(), tailwindcss()],
  // 后台部署在 /admin/ 下，资源路径与其他站点隔离
  base: "/admin/",
  resolve: {
    alias: {
      "@": path.resolve(import.meta.dirname, "./src"),
    },
  },
  build: {
    // 输出到 Hyperf 的 public/admin，与前台共用 public 根目录
    outDir: "../../../public/admin",
    emptyOutDir: true,
  },
  server: {
    port: 5173,
    proxy: {
      "/api": {
        target: "http://127.0.0.1:9501",
        changeOrigin: true,
      },
    },
  },
});
