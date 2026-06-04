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
    return handleBrowserPrint(order)
  }
}

const escapeHtml = (value: unknown) => {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

const buildReceiptHtml = (order: any) => {
  const tenant = JSON.parse(localStorage.getItem('tenant') || '{}')
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  const settings = JSON.parse(localStorage.getItem('settings') || '{}')
  const items = Array.isArray(order.items) ? order.items : []
  const receiptText = settings.receipt_text || tenant.receipt_text || 'Terima kasih atas kunjungan Anda'

  const itemRows = items.map((it: any) => {
    const lineTotal = Number(it.total ?? Number(it.price || 0) * Number(it.quantity || 0))
    return `
      <div class="item">
        <div class="item-name">${escapeHtml(it.product_name)}</div>
        <div class="row">
          <span>${escapeHtml(it.quantity)} x ${escapeHtml(formatCurrency(Number(it.price || 0)))}</span>
          <span>${escapeHtml(formatCurrency(lineTotal))}</span>
        </div>
        ${Number(it.discount || 0) > 0 ? `<div class="discount">Diskon: -${escapeHtml(formatCurrency(Number(it.discount)))}</div>` : ''}
      </div>
    `
  }).join('')

  return `
    <!doctype html>
    <html>
      <head>
        <meta charset="utf-8">
        <title>Struk #${escapeHtml(order.id || '')}</title>
        <style>
          @page { size: 58mm auto; margin: 0; }
          * { box-sizing: border-box; }
          body {
            margin: 0;
            padding: 4mm;
            width: 58mm;
            color: #000;
            background: #fff;
            font-family: "Courier New", Courier, monospace;
            font-size: 11px;
            line-height: 1.3;
          }
          .center { text-align: center; }
          .store { font-size: 14px; font-weight: 700; }
          .divider { border-top: 1px dashed #000; margin: 6px 0; }
          .row { display: flex; justify-content: space-between; gap: 8px; }
          .item { margin-bottom: 5px; }
          .item-name { overflow-wrap: anywhere; }
          .discount { font-size: 10px; }
          .total { font-weight: 700; font-size: 13px; }
        </style>
      </head>
      <body>
        <div class="center">
          <div class="store">${escapeHtml(tenant.name || settings.store_name || 'NessaPOS')}</div>
          <div>${escapeHtml(tenant.address || settings.store_address || '')}</div>
          <div>${escapeHtml(tenant.phone || settings.store_phone || '')}</div>
        </div>
        <div class="divider"></div>
        <div>No: #${escapeHtml(order.id || '-')}</div>
        <div>Kasir: ${escapeHtml(user.username || order.user_name || '')}</div>
        <div>Waktu: ${escapeHtml(order.date || new Date().toLocaleString('id-ID'))}</div>
        <div class="divider"></div>
        ${itemRows}
        <div class="divider"></div>
        <div class="row"><span>Subtotal</span><span>${escapeHtml(formatCurrency(Number(order.total_amount || 0)))}</span></div>
        ${Number(order.discount || 0) > 0 ? `<div class="row"><span>Diskon</span><span>-${escapeHtml(formatCurrency(Number(order.discount)))}</span></div>` : ''}
        ${Number(order.tax_amount || 0) > 0 ? `<div class="row"><span>Pajak</span><span>${escapeHtml(formatCurrency(Number(order.tax_amount)))}</span></div>` : ''}
        <div class="row total"><span>TOTAL</span><span>${escapeHtml(formatCurrency(Number(order.final_amount || 0)))}</span></div>
        <div class="row"><span>Bayar</span><span>${escapeHtml(formatCurrency(Number(order.amount_paid || 0)))}</span></div>
        <div class="row"><span>Kembali</span><span>${escapeHtml(formatCurrency(Number(order.change_amount || 0)))}</span></div>
        <div class="divider"></div>
        <div class="center">${escapeHtml(receiptText)}</div>
      </body>
    </html>
  `
}

const handleBrowserPrint = (order: any) => {
  return new Promise<boolean>((resolve) => {
    const iframe = document.createElement('iframe')
    let resolved = false
    let cleanupTimer: number | undefined

    const finish = () => {
      if (resolved) return
      resolved = true
      window.clearTimeout(cleanupTimer)
      window.setTimeout(() => {
        iframe.remove()
      }, 250)
      resolve(true)
    }

    iframe.title = 'NessaPOS Receipt Print'
    iframe.style.position = 'fixed'
    iframe.style.right = '0'
    iframe.style.bottom = '0'
    iframe.style.width = '0'
    iframe.style.height = '0'
    iframe.style.border = '0'
    iframe.style.opacity = '0'
    iframe.style.pointerEvents = 'none'

    document.body.appendChild(iframe)

    const printWindow = iframe.contentWindow
    const doc = printWindow?.document
    if (!printWindow || !doc) {
      iframe.remove()
      resolve(false)
      return
    }

    printWindow.onafterprint = finish
    doc.open()
    doc.write(buildReceiptHtml(order))
    doc.close()

    window.setTimeout(() => {
      printWindow.focus()
      printWindow.print()
      cleanupTimer = window.setTimeout(finish, 3000)
    }, 150)
  })
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
