import path from "node:path";
import tailwindcss from "@tailwindcss/vite";
import react from "@vitejs/plugin-react";
import { defineConfig } from "vite";

export default defineConfig({
  plugins: [react(), tailwindcss()],
  base: "/",
  resolve: {
    alias: {
      "@": path.resolve(import.meta.dirname, "./src"),
    },
  },
  build: {
    // 前台占用 public 根，必须与后台共用同一目录
    outDir: "../../../public",
    // 关键：不能清空，否则会把 public/admin 一起删掉
    emptyOutDir: false,
  },
  server: {
    port: 5174,
    proxy: {
      "/api": {
        target: "http://127.0.0.1:9501",
        changeOrigin: true,
      },
    },
  },
});
