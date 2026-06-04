import axios from 'axios'

export interface BridgeConfig {
    port: number
    token: string
}

export interface BridgeConnectionStatus {
    ok: boolean
    baseURL: string
    message?: string
}

const getBridgeConfig = (): BridgeConfig => {
    // These will be saved in localStorage after we fetch settings once
    return {
        port: parseInt(localStorage.getItem('bridge_port') || '12348'),
        token: localStorage.getItem('bridge_token') || ''
    }
}

const getBridgeBaseUrls = () => {
    const { port } = getBridgeConfig()
    return [
        `http://127.0.0.1:${port}`,
        `http://localhost:${port}`,
        `http://[::1]:${port}`,
    ]
}

const bridgeApi = (baseURL?: string) => {
    const config = getBridgeConfig()
    const headers: Record<string, string> = {
        'Content-Type': 'application/json'
    }

    if (config.token) {
        headers['X-Bridge-Token'] = config.token
    }

    return axios.create({
        baseURL: baseURL || getBridgeBaseUrls()[0],
        timeout: 10000, // Tambahkan timeout jika koneksi lambat
        headers
    })
}

const bridgeStatusApi = (baseURL: string) => {
    return axios.create({
        baseURL,
        timeout: 5000
    })
}

const findBridgeBaseUrl = async () => {
    const errors: string[] = []

    for (const baseURL of getBridgeBaseUrls()) {
        try {
            const res = await bridgeStatusApi(baseURL).get('/status')
            if (res.data?.status === 'ok') {
                return { ok: true, baseURL } as BridgeConnectionStatus
            }
            errors.push(`${baseURL}: status tidak valid`)
        } catch (error: any) {
            const status = error?.response?.status
            errors.push(`${baseURL}: ${status ? `HTTP ${status}` : error?.message || 'gagal terhubung'}`)
        }
    }

    const { port } = getBridgeConfig()
    const httpsHint = window.location.protocol === 'https:'
        ? (
            ' Jika POS dibuka dari domain HTTPS, browser hanya bisa mencetak via aplikasi desktop jika akses ke localhost diizinkan. ' +
            'Pastikan NessaPOS Desktop/Bridge berjalan, firewall mengizinkan port ini, lalu coba buka URL status bridge dari browser yang sama.'
        )
        : ''

    return {
        ok: false,
        baseURL: getBridgeBaseUrls()[0],
        message: (
            `Bridge tidak merespons di port ${port}. ` +
            'Pastikan aplikasi NessaPOS Desktop sedang berjalan di PC kasir, port bridge sama dengan Pengaturan, lalu restart aplikasi desktop.' +
            httpsHint +
            ` URL cek: http://127.0.0.1:${port}/status. Detail: ${errors.join('; ')}`
        )
    } as BridgeConnectionStatus
}

/**
 * Detects if we are running inside Wails desktop (bindings injected at runtime).
 */
export const isWails = (): boolean => {
    const w = window as { runtime?: object; go?: object }
    return Boolean(w.go || w.runtime)
}

const formatBridgeError = (error: any) => {
    if (error?.response) {
        return `Bridge error ${error.response.status}: ${JSON.stringify(error.response.data)}`
    }
    if (error?.request) {
        const { port } = getBridgeConfig()
        return (
            `Bridge tidak merespons di http://127.0.0.1:${port}. ` +
            'Pastikan aplikasi NessaPOS Desktop (Wails) sedang berjalan di PC ini, ' +
            'port bridge sama dengan Pengaturan, lalu restart aplikasi desktop. ' +
            `Jika web dibuka dari domain online, coba akses http://127.0.0.1:${port}/status dari browser yang sama untuk memastikan browser tidak memblokir localhost.`
        )
    }
    return error?.message || 'Unknown bridge error'
}

export const bridgePrintReceipt = async (order: any) => {
    try {
        const bridge = await findBridgeBaseUrl()
        if (!bridge.ok) {
            throw new Error(bridge.message)
        }
        await bridgeApi(bridge.baseURL).post('/print/receipt', order)
        return true
    } catch (e: any) {
        const msg = e?.message || formatBridgeError(e)
        console.error("Bridge print failed:", msg)
        throw new Error(msg)
    }
}

export const bridgeKickDrawer = async () => {
    try {
        const bridge = await findBridgeBaseUrl()
        if (!bridge.ok) {
            throw new Error(bridge.message)
        }
        await bridgeApi(bridge.baseURL).post('/drawer/kick', {})
        return true
    } catch (e: any) {
        const msg = e?.message || formatBridgeError(e)
        console.error("Bridge kick drawer failed:", msg)
        throw new Error(msg)
    }
}

export const bridgePrintSessionOpen = async (req: any) => {
    try {
        const bridge = await findBridgeBaseUrl()
        if (!bridge.ok) {
            throw new Error(bridge.message)
        }
        await bridgeApi(bridge.baseURL).post('/print/session-open', req)
        return true
    } catch (e: any) {
        const msg = e?.message || formatBridgeError(e)
        console.error("Bridge session open print failed:", msg)
        throw new Error(msg)
    }
}

export const bridgePrintSessionClose = async (req: any) => {
    try {
        const bridge = await findBridgeBaseUrl()
        if (!bridge.ok) {
            throw new Error(bridge.message)
        }
        await bridgeApi(bridge.baseURL).post('/print/session-close', req)
        return true
    } catch (e: any) {
        const msg = e?.message || formatBridgeError(e)
        console.error("Bridge session close print failed:", msg)
        throw new Error(msg)
    }
}

export async function checkBridgeStatus(): Promise<boolean> {
    const bridge = await findBridgeBaseUrl()
    return bridge.ok
}

export async function checkBridgeConnection(): Promise<BridgeConnectionStatus> {
    return findBridgeBaseUrl()
}
