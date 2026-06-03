import { isWails, bridgeKickDrawer, bridgePrintReceipt, checkBridgeStatus } from './bridge'
import { KickDrawer, PrintReceiptThermal } from '../../wailsjs/go/api/API'
import { formatCurrency } from './format'

export type PrintMethod = 'wails' | 'rawbt' | 'browser'

export const getPrintMethod = (): PrintMethod => {
  if (isWails()) {
    return 'wails'
  }
  return (localStorage.getItem('print_method') as PrintMethod) || 'browser'
}

export const setPrintMethod = (method: PrintMethod) => {
  localStorage.setItem('print_method', method)
}

export interface PrintOptions {
  kickDrawer?: boolean
}

/**
 * Main Printing Entry Point
 */
export const printReceipt = async (order: any, options: PrintOptions = {}) => {
  const method = getPrintMethod()
  
  if (method === 'wails') {
    return handleWailsPrint(order, options)
  } else if (method === 'rawbt') {
    return handleRawBTPrint(order, options)
  } else {
    // Default to browser print
    window.print()
    return true
  }
}

const handleWailsPrint = async (order: any, options: PrintOptions) => {
  try {
    if (isWails()) {
      await PrintReceiptThermal(order)
      if (options.kickDrawer) {
        await KickDrawer()
      }
    } else {
      const bridgeOk = await checkBridgeStatus()
      if (!bridgeOk) {
        const port = localStorage.getItem('bridge_port') || '12348'
        throw new Error(
          `Aplikasi desktop tidak terdeteksi dan bridge offline (port ${port}). ` +
          'Buka NessaPOS Desktop di PC kasir, atau di browser ubah metode cetak ke "Browser Print".'
        )
      }
      await bridgePrintReceipt(order)
      if (options.kickDrawer) {
        await bridgeKickDrawer()
      }
    }
    return true
  } catch (e: any) {
    console.error("Wails/Bridge print failed", e)
    const message = e?.message || (typeof e === 'string' ? e : 'Unknown error')
    alert(`Gagal mencetak langsung ke printer desktop: ${message}`)
    throw e
  }
}

/**
 * Construct RawBT Intent for Android
 */
const handleRawBTPrint = (order: any, options: PrintOptions) => {
  const tenant = JSON.parse(localStorage.getItem('tenant') || '{}')
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  
  // ESC/POS Control Codes
  const ESC = '\x1b'
  const GS = '\x1d'
  
  // Format the text for thermal printer (ESC/POS compatible)
  let text = ""
  text += `${ESC}R\x03` // Select International Character Set (Indonesia/ASCII)
  text += `${ESC}a\x01` // Center
  text += `${GS}!\x11`  // Double height & width (Hex 11 = 17)
  text += `${tenant.name || 'NessaPOS'}\n`
  text += `${GS}!\x00`  // Normal
  text += `${tenant.address || ''}\n`
  text += `${tenant.phone || ''}\n`
  text += `--------------------------------\n`
  text += `${ESC}a\x00` // Left
  text += `No: #${order.id}\n`
  text += `Kasir: ${user.username || ''}\n`
  text += `Waktu: ${new Date().toLocaleString('id-ID')}\n`
  text += `--------------------------------\n`
  
  order.items.forEach((it: any) => {
    text += `${it.product_name}\n`
    const price = formatCurrency(it.price).replace('Rp', '').trim()
    const total = formatCurrency(it.total).replace('Rp', '').trim()
    text += `${it.quantity} x ${price}`.padEnd(20) + total.padStart(12) + `\n`
  })
  
  text += `--------------------------------\n`
  const subtotal = formatCurrency(order.total_amount).replace('Rp', '').trim()
  text += `SUBTOTAL`.padEnd(20) + subtotal.padStart(12) + `\n`
  
  if (order.discount > 0) {
    const disc = formatCurrency(order.discount).replace('Rp', '').trim()
    text += `DISKON`.padEnd(20) + `-${disc}`.padStart(12) + `\n`
  }
  
  if (order.tax_amount > 0) {
    const tax = formatCurrency(order.tax_amount).replace('Rp', '').trim()
    text += `PAJAK`.padEnd(20) + tax.padStart(12) + `\n`
  }
  
  text += `--------------------------------\n`
  text += `${GS}!\x01` // Tall
  const totalValue = formatCurrency(order.final_amount).replace('Rp', '').trim()
  text += `TOTAL`.padEnd(10) + totalValue.padStart(12) + `\n`
  text += `${GS}!\x00` // Normal
  
  const bayar = formatCurrency(order.amount_paid).replace('Rp', '').trim()
  const kembali = formatCurrency(order.change_amount).replace('Rp', '').trim()
  text += `Bayar:`.padEnd(20) + bayar.padStart(12) + `\n`
  text += `Kembali:`.padEnd(20) + kembali.padStart(12) + `\n`
  
  text += `--------------------------------\n`
  text += `${ESC}a\x01` // Center
  text += `Terima Kasih Atas\nKunjungan Anda\n`
  text += `\n\n\n` // Padding for tear off
  if (options.kickDrawer) {
    text += `${ESC}p\x00\x19\xfa` // Kick cash drawer pin 2
  }
  text += `${GS}V\x42\x00` // Partial cut

  // Construct Intent for RawBT
  const encodedText = btoa(text) // Base64 encode the binary string
  const intentUrl = `intent:#Intent;content=${encodedText};action=woyou.intent.action.PRINT;package=woyou.aidlservice.jiuiv5;end`
  
  // Or simpler rawbt protocol (handles base64 or raw)
  const rawbtUrl = `rawbt:${text}`
  
  // Attempt to open RawBT
  window.location.href = rawbtUrl
  return true
}
