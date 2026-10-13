import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import tailwindcss from "@tailwindcss/vite";
import { fileURLToPath } from "node:url";

export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: { alias: { "@": fileURLToPath(new URL("./src", import.meta.url)) } },
  base: "/admin-ui/",
  build: {
    outDir: "../backend/public/admin-ui",
    emptyOutDir: true,
    manifest: true,
    rollupOptions: { input: fileURLToPath(new URL("./src/admin-entry.tsx", import.meta.url)) },
  },
});
