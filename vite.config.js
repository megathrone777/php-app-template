import { defineConfig } from "vite";
import tailwindcss from "@tailwindcss/vite";

const config = defineConfig({
  // Ассеты отдаются по /build/... (docroot — корень проекта).
  base: "/build/",
  build: {
    outDir: "build",
    manifest: true,
    emptyOutDir: true,
    rollupOptions: {
      input: [
        "src/web/assets/css/init.css",
        "src/web/assets/js/init.js",
        // "src/admin/assets/js/init.js",
      ],
    },
  },
  plugins: [tailwindcss()],
  server: {
    cors: true,
    strictPort: true,
    port: 5173,
    origin: "http://localhost:5173",
  },
});

export default config;
