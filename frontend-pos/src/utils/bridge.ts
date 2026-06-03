import axios from 'axios'

export interface BridgeConfig {
    port: number
    token: string
}

const getBridgeConfig = (): BridgeConfig => {
    // These will be saved in localStorage after we fetch settings once
    return {
        port: parseInt(localStorage.getItem('bridge_port') || '12348'),
        token: localStorage.getItem('bridge_token') || ''
    }
}

const bridgeApi = () => {
    const config = getBridgeConfig()
    return axios.create({
        baseURL: `http://127.0.0.1:${config.port}`,
        headers: {
            'X-Bridge-Token': config.token,
            'Content-Type': 'application/json'
        }
    })
}

/**
 * Detects if we are running inside Wails environment
 */
export const isWails = (): boolean => {
    return (window as any).runtime !== undefined
}

const formatBridgeError = (error: any) => {
    if (error?.response) {
        return `Bridge error ${error.response.status}: ${JSON.stringify(error.response.data)}`
    }
    if (error?.request) {
        return `Bridge did not respond. Port atau bridge tidak berjalan.`
    }
    return error?.message || 'Unknown bridge error'
}

export const bridgePrintReceipt = async (order: any) => {
    try {
        await bridgeApi().post('/print/receipt', order)
        return true
    } catch (e: any) {
        const msg = formatBridgeError(e)
        console.error("Bridge print failed:", msg)
        throw new Error(msg)
    }
}

export const bridgeKickDrawer = async () => {
    try {
        await bridgeApi().post('/drawer/kick', {})
        return true
    } catch (e: any) {
        const msg = formatBridgeError(e)
        console.error("Bridge kick drawer failed:", msg)
        throw new Error(msg)
    }
}

export const bridgePrintSessionOpen = async (req: any) => {
    try {
        await bridgeApi().post('/print/session-open', req)
        return true
    } catch (e: any) {
        const msg = formatBridgeError(e)
        console.error("Bridge session open print failed:", msg)
        throw new Error(msg)
    }
}

export const bridgePrintSessionClose = async (req: any) => {
    try {
        await bridgeApi().post('/print/session-close', req)
        return true
    } catch (e: any) {
        const msg = formatBridgeError(e)
        console.error("Bridge session close print failed:", msg)
        throw new Error(msg)
    }
}

export const checkBridgeStatus = async () => {
    try {
        const res = await bridgeApi().get('/status')
        return res.data?.status === 'ok'
    } catch (e) {
        return false
    }
}
