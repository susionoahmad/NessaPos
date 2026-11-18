import { isWails, bridgeKickDrawer, bridgePrintReceipt, checkBridgeStatus } from './bridge'
import { KickDrawer, PrintReceiptThermal } from '../../wailsjs/go/api/API'
import { formatCurrency } from './format'
import { getApiUrl } from '../api'

export type PrintMethod = 'wails' | 'rawbt' | 'browser'
export type PrinterWidth = '58mm' | '80mm'

export const getPrintMethod = (): PrintMethod => {
  const saved = localStorage.getItem('print_method') as PrintMethod | null
  if (saved === 'wails' || saved === 'rawbt' || saved === 'browser') {
    return saved
  }
  return isWails() ? 'wails' : 'browser'
}

export const setPrintMethod = (method: PrintMethod) => {
  localStorage.setItem('print_method', method)
}

export const getPrinterWidth = (): PrinterWidth => {
  return localStorage.getItem('printer_width') === '80mm' ? '80mm' : '58mm'
}

export interface PrintOptions {
  kickDrawer?: boolean
  methodOverride?: PrintMethod
}

const safeParseJson = (key: string): Record<string, any> => {
  try {
    return JSON.parse(localStorage.getItem(key) || '{}') || {}
  } catch {
    return {}
  }
}

const getStorageBaseUrl = (): string => {
  return getApiUrl().replace(/\/api$/i, '')
}

export const resolveReceiptLogoUrl = (path?: string | null): string => {
  if (!path) return ''
  if (/^https?:\/\//i.test(path) || path.startsWith('data:')) return path
  const clean = path.replace(/^\/+/, '').replace(/^storage\//i, '')
  return `${getStorageBaseUrl()}/storage/${clean}`
}

const formatOrderDate = (rawOrder: any): string => {
  if (rawOrder?.date) return String(rawOrder.date)
  if (rawOrder?.created_at) {
    const dt = new Date(rawOrder.created_at)
    if (!Number.isNaN(dt.getTime())) {
      return dt.toLocaleString('id-ID')
    }
  }
  return new Date().toLocaleString('id-ID')
}

export interface NormalizedOrderItem {
  product_id: number
  product_name: string
  quantity: number
  price: number
  discount: number
  total: number
}

/**
 * Normalize order payloads from POS checkout, Reports (/orders API), or Settings Test Print
 * so numeric fields, nested payment/user relations, and store metadata are always valid.
 */
export const normalizeOrderForPrint = (rawOrder: any) => {
  const tenant = safeParseJson('tenant')
  const user = safeParseJson('user')
  const settings = safeParseJson('settings')
  const width = getPrinterWidth()

  const rawItems = Array.isArray(rawOrder?.items) ? rawOrder.items : []
  const items: NormalizedOrderItem[] = rawItems.map((it: any): NormalizedOrderItem => {
    const qty = Number(it?.quantity ?? 0) || 0
    const price = Number(it?.price ?? 0) || 0
    const discount = Number(it?.discount ?? 0) || 0
    const total = Number(it?.total ?? (price * qty - discount)) || 0
    return {
      product_id: Number(it?.product_id ?? 0) || 0,
      product_name: String(it?.product_name ?? 'Produk'),
      quantity: qty,
      price,
      discount,
      total,
    }
  })

  const payment = rawOrder?.payment || {}
  const finalAmount = Number(rawOrder?.final_amount ?? 0) || 0
  const amountPaid = Number(rawOrder?.amount_paid ?? payment?.amount_paid ?? finalAmount) || 0
  const changeAmount = Number(rawOrder?.change_amount ?? payment?.change_amount ?? Math.max(0, amountPaid - finalAmount)) || 0
  const paymentMethod = String(rawOrder?.payment_method ?? payment?.payment_method ?? 'Cash')
  const cashierName = String(
    rawOrder?.cashier_name ??
    rawOrder?.user_name ??
    rawOrder?.user?.username ??
    user?.username ??
    `User #${Number(rawOrder?.user_id ?? user?.id ?? 1)}`
  )
  const numericId = Number(rawOrder?.id)
  const displayId = rawOrder?.id !== undefined && rawOrder?.id !== null && String(rawOrder.id) !== ''
    ? String(rawOrder.id)
    : '-'

  return {
    id: Number.isFinite(numericId) && numericId >= 0 ? numericId : 0,
    display_id: displayId,
    user_id: Number(rawOrder?.user_id ?? user?.id ?? 1) || 1,
    cashier_name: cashierName,
    date: formatOrderDate(rawOrder),
    total_amount: Number(rawOrder?.total_amount ?? 0) || 0,
    tax_amount: Number(rawOrder?.tax_amount ?? 0) || 0,
    tax_type: String(rawOrder?.tax_type ?? settings?.tax_type ?? 'exclusive'),
    discount: Number(rawOrder?.discount ?? 0) || 0,
    final_amount: finalAmount,
    payment_method: paymentMethod,
    amount_paid: amountPaid,
    change_amount: changeAmount,
    items,
    store_name: String(settings?.store_name || tenant?.name || 'NessaPOS'),
    store_address: String(settings?.store_address || tenant?.address || ''),
    store_phone: String(settings?.store_phone || tenant?.phone || ''),
    receipt_text: String(settings?.receipt_text || tenant?.receipt_text || 'Terima Kasih Atas Kunjungan Anda'),
    receipt_logo: resolveReceiptLogoUrl(settings?.receipt_logo || ''),
    printer_width: width,
  }
}

/**
 * Main Printing Entry Point
 */
export const printReceipt = async (order: any, options: PrintOptions = {}) => {
  const method = options.methodOverride || getPrintMethod()
  const normalized = normalizeOrderForPrint(order)

  if (method === 'wails') {
    return handleWailsPrint(normalized, options)
  } else if (method === 'rawbt') {
    return handleRawBTPrint(normalized, options)
  } else {
    return handleBrowserPrint(normalized)
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

const buildReceiptHtml = (order: ReturnType<typeof normalizeOrderForPrint>) => {
  const is80mm = order.printer_width === '80mm'
  const pageWidth = is80mm ? '80mm' : '58mm'
  const fontSize = is80mm ? '12px' : '11px'
  const storeFontSize = is80mm ? '16px' : '14px'
  const totalFontSize = is80mm ? '14px' : '13px'

  const itemRows = order.items.map((it) => {
    const grossLine = it.price * it.quantity
    return `
      <div class="item">
        <div class="item-name">${escapeHtml(it.product_name)}</div>
        <div class="row">
          <span>${escapeHtml(it.quantity)} x ${escapeHtml(formatCurrency(it.price))}</span>
          <span>${escapeHtml(formatCurrency(grossLine))}</span>
        </div>
        ${it.discount > 0 ? `<div class="discount">Diskon: -${escapeHtml(formatCurrency(it.discount))}</div>` : ''}
      </div>
    `
  }).join('')

  const logoHtml = order.receipt_logo
    ? `<div class="logo-wrap"><img src="${escapeHtml(order.receipt_logo)}" class="logo" alt="Logo" /></div>`
    : ''

  const footerLines = escapeHtml(order.receipt_text).replace(/\r?\n/g, '<br/>')
  const taxLabel = order.tax_type === 'inclusive' ? 'Pajak (Inc)' : 'Pajak (Exc)'
  const taxPrefix = order.tax_type === 'inclusive' ? '' : '+'

  return `
    <!doctype html>
    <html>
      <head>
        <meta charset="utf-8">
        <title>Struk #${escapeHtml(order.display_id)}</title>
        <style>
          @page { size: ${pageWidth} auto; margin: 0; }
          * { box-sizing: border-box; }
          html, body {
            margin: 0;
            padding: 0;
            width: ${pageWidth};
            color: #000;
            background: #fff;
            font-family: "Courier New", Courier, monospace;
            font-size: ${fontSize};
            line-height: 1.35;
          }
          body {
            padding: 3mm 3.5mm 6mm;
          }
          .center { text-align: center; }
          .logo-wrap { margin-bottom: 4px; text-align: center; }
          .logo {
            max-width: ${is80mm ? '42mm' : '32mm'};
            max-height: 22mm;
            object-fit: contain;
            filter: grayscale(100%) contrast(160%);
          }
          .store { font-size: ${storeFontSize}; font-weight: 700; margin-bottom: 2px; }
          .meta { font-size: ${is80mm ? '11px' : '10px'}; }
          .divider { border-top: 1px dashed #000; margin: 6px 0; }
          .row { display: flex; justify-content: space-between; gap: 6px; }
          .row span:last-child { white-space: nowrap; }
          .item { margin-bottom: 5px; }
          .item-name { font-weight: 700; overflow-wrap: anywhere; }
          .discount { font-size: 10px; text-align: right; font-style: italic; }
          .total { font-weight: 700; font-size: ${totalFontSize}; margin: 3px 0; }
          .footer { margin-top: 6px; font-size: ${is80mm ? '11px' : '10px'}; }
        </style>
      </head>
      <body>
        <div class="center">
          ${logoHtml}
          <div class="store">${escapeHtml(order.store_name)}</div>
          ${order.store_address ? `<div class="meta">${escapeHtml(order.store_address)}</div>` : ''}
          ${order.store_phone ? `<div class="meta">Tel: ${escapeHtml(order.store_phone)}</div>` : ''}
        </div>
        <div class="divider"></div>
        <div class="meta">No: #${escapeHtml(order.display_id)}</div>
        <div class="meta">Kasir: ${escapeHtml(order.cashier_name)}</div>
        <div class="meta">Waktu: ${escapeHtml(order.date)}</div>
        <div class="meta">Metode: ${escapeHtml(order.payment_method)}</div>
        <div class="divider"></div>
        ${itemRows}
        <div class="divider"></div>
        <div class="row"><span>Subtotal</span><span>${escapeHtml(formatCurrency(order.total_amount))}</span></div>
        ${order.discount > 0 ? `<div class="row"><span>Diskon</span><span>-${escapeHtml(formatCurrency(order.discount))}</span></div>` : ''}
        ${order.tax_amount > 0 ? `<div class="row"><span>${taxLabel}</span><span>${taxPrefix}${escapeHtml(formatCurrency(order.tax_amount))}</span></div>` : ''}
        <div class="row total"><span>TOTAL</span><span>${escapeHtml(formatCurrency(order.final_amount))}</span></div>
        <div class="row"><span>Bayar</span><span>${escapeHtml(formatCurrency(order.amount_paid))}</span></div>
        <div class="row"><span>Kembali</span><span>${escapeHtml(formatCurrency(order.change_amount))}</span></div>
        <div class="divider"></div>
        <div class="center footer">${footerLines}</div>
      </body>
    </html>
  `
}

const waitForImages = (doc: Document, timeoutMs = 800): Promise<void> => {
  const imgs = Array.from(doc.images || [])
  if (imgs.length === 0) return Promise.resolve()

  return new Promise((resolve) => {
    let settled = false
    const done = () => {
      if (settled) return
      settled = true
      resolve()
    }
    const timer = window.setTimeout(done, timeoutMs)
    let remaining = imgs.length

    const checkOne = () => {
      remaining -= 1
      if (remaining <= 0) {
        window.clearTimeout(timer)
        done()
      }
    }

    imgs.forEach((img) => {
      if (img.complete) {
        checkOne()
      } else {
        img.addEventListener('load', checkOne, { once: true })
        img.addEventListener('error', checkOne, { once: true })
      }
    })
  })
}

const handleBrowserPrint = (order: ReturnType<typeof normalizeOrderForPrint>) => {
  return new Promise<boolean>((resolve, reject) => {
    const iframe = document.createElement('iframe')
    let resolved = false
    let cleanupTimer: number | undefined

    const finish = () => {
      if (resolved) return
      resolved = true
      window.clearTimeout(cleanupTimer)
      window.setTimeout(() => {
        iframe.remove()
      }, 300)
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
      reject(new Error('Browser gagal membuat jendela cetak.'))
      return
    }

    printWindow.onafterprint = finish
    doc.open()
    doc.write(buildReceiptHtml(order))
    doc.close()

    waitForImages(doc, 800).then(() => {
      window.setTimeout(() => {
        try {
          printWindow.focus()
          printWindow.print()
          // Fallback cleanup if browser doesn't fire onafterprint
          cleanupTimer = window.setTimeout(finish, 60000)
        } catch (err) {
          iframe.remove()
          reject(err)
        }
      }, 100)
    })
  })
}

const handleWailsPrint = async (
  order: ReturnType<typeof normalizeOrderForPrint>,
  options: PrintOptions
) => {
  const payload = {
    id: order.id,
    user_id: order.user_id,
    cashier_name: order.cashier_name,
    total_amount: order.total_amount,
    tax_amount: order.tax_amount,
    discount: order.discount,
    final_amount: order.final_amount,
    payment_method: order.payment_method,
    amount_paid: order.amount_paid,
    change_amount: order.change_amount,
    items: order.items,
    store_name: order.store_name,
    store_address: order.store_address,
    store_phone: order.store_phone,
    receipt_text: order.receipt_text,
    printer_width: order.printer_width,
  }

  try {
    if (isWails()) {
      await PrintReceiptThermal(payload as any)
      if (options.kickDrawer) {
        await KickDrawer()
      }
    } else {
      const bridgeOk = await checkBridgeStatus()
      if (!bridgeOk) {
        const port = localStorage.getItem('bridge_port') || '12348'
        throw new Error(
          `Aplikasi desktop tidak terdeteksi dan bridge offline (port ${port}). ` +
          'Buka NessaPOS Online Desktop di PC kasir, atau ubah metode cetak ke "Browser Print".'
        )
      }
      await bridgePrintReceipt(payload)
      if (options.kickDrawer) {
        await bridgeKickDrawer()
      }
    }
    return true
  } catch (e: any) {
    console.error('Wails/Bridge print failed', e)
    const message = e?.message || (typeof e === 'string' ? e : 'Unknown error')
    throw new Error(message)
  }
}

/**
 * Helper to pad left/right columns cleanly for 58mm (32 chars) or 80mm (48 chars)
 */
const formatThermalLine = (left: string, right: string, cols: number): string => {
  const l = left.replace(/\r?\n/g, ' ')
  const r = right.replace(/\r?\n/g, ' ')
  const spaces = Math.max(1, cols - l.length - r.length)
  if (l.length + r.length + 1 > cols) {
    const trimmedLeft = l.slice(0, Math.max(0, cols - r.length - 1))
    return `${trimmedLeft} ${r}`
  }
  return `${l}${' '.repeat(spaces)}${r}`
}

/**
 * Encode ESC/POS string (mixing control bytes + UTF-8 text) safely to Base64
 */
const encodeEscPosBase64 = (escPosText: string): string => {
  const bytes = new TextEncoder().encode(escPosText)
  let binary = ''
  const chunkSize = 0x8000
  for (let i = 0; i < bytes.length; i += chunkSize) {
    const chunk = bytes.subarray(i, i + chunkSize)
    binary += String.fromCharCode(...chunk)
  }
  return btoa(binary)
}

/**
 * Construct RawBT Base64 Intent URL for Android Bluetooth/USB Thermal Printers
 */
const handleRawBTPrint = (
  order: ReturnType<typeof normalizeOrderForPrint>,
  options: PrintOptions
) => {
  const cols = order.printer_width === '80mm' ? 48 : 32
  const divider = '-'.repeat(cols) + '\n'
  const fmtMoney = (val: number) => formatCurrency(val).replace(/^Rp\s*/i, '').trim()

  // ESC/POS Control Codes
  const ESC = '\x1b'
  const GS = '\x1d'

  let text = ''
  text += `${ESC}@`        // Initialize printer
  text += `${ESC}a\x01`    // Center align
  text += `${GS}!\x11`     // Double height & width
  text += `${order.store_name}\n`
  text += `${GS}!\x00`     // Normal size
  if (order.store_address) text += `${order.store_address}\n`
  if (order.store_phone) text += `Tel: ${order.store_phone}\n`
  text += divider

  text += `${ESC}a\x00`    // Left align
  text += `No: #${order.display_id}\n`
  text += `Kasir: ${order.cashier_name}\n`
  text += `Waktu: ${order.date}\n`
  text += `Metode: ${order.payment_method}\n`
  text += divider

  order.items.forEach((it) => {
    text += `${it.product_name}\n`
    const left = `${it.quantity} x ${fmtMoney(it.price)}`
    const right = fmtMoney(it.price * it.quantity)
    text += formatThermalLine(left, right, cols) + '\n'
    if (it.discount > 0) {
      text += formatThermalLine('  Diskon Item', `-${fmtMoney(it.discount)}`, cols) + '\n'
    }
  })

  text += divider
  text += formatThermalLine('Subtotal', `Rp ${fmtMoney(order.total_amount)}`, cols) + '\n'

  if (order.discount > 0) {
    text += formatThermalLine('Diskon', `-Rp ${fmtMoney(order.discount)}`, cols) + '\n'
  }

  if (order.tax_amount > 0) {
    const taxLabel = order.tax_type === 'inclusive' ? 'Pajak (Inc)' : 'Pajak (Exc)'
    text += formatThermalLine(taxLabel, `Rp ${fmtMoney(order.tax_amount)}`, cols) + '\n'
  }

  text += divider
  text += `${ESC}E\x01`    // Bold ON
  text += `${GS}!\x01`     // Double height
  text += formatThermalLine('TOTAL', `Rp ${fmtMoney(order.final_amount)}`, cols) + '\n'
  text += `${GS}!\x00`     // Normal size
  text += `${ESC}E\x00`    // Bold OFF

  text += formatThermalLine('Bayar', `Rp ${fmtMoney(order.amount_paid)}`, cols) + '\n'
  text += formatThermalLine('Kembali', `Rp ${fmtMoney(order.change_amount)}`, cols) + '\n'
  text += divider

  text += `${ESC}a\x01`    // Center align
  text += `${order.receipt_text}\n`
  text += '\n\n\n'         // Feed paper for tear off
  if (options.kickDrawer) {
    text += `${ESC}p\x00\x19\xfa` // Kick cash drawer pin 2
  }
  text += `${GS}V\x42\x00` // Partial cut

  const base64Payload = encodeEscPosBase64(text)
  const rawbtUrl = `rawbt:base64,${base64Payload}`

  window.location.href = rawbtUrl
  return true
}
