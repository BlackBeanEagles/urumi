import {defineConfig} from 'vite';
export default defineConfig({root:'web',build:{outDir:'dist',emptyOutDir:true,assetsDir:'.'},server:{proxy:{'/api':'http://localhost:3000','/mcp':'http://localhost:3000'}}});
