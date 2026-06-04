/// <reference types="vite/client" />
/// <reference types="vite-plugin-pwa/client" />

interface ImportMetaEnv {
    readonly VITE_API_MODE?: 'local' | 'online'
    readonly VITE_API_URL?: string
    readonly VITE_API_URL_LOCAL?: string
    readonly VITE_API_URL_ONLINE?: string
}

interface ImportMeta {
    readonly env: ImportMetaEnv
}

declare module '*.vue' {
    import type {DefineComponent} from 'vue'
    const component: DefineComponent<{}, {}, any>
    export default component
}
