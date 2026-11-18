<template>
  <div class="modal-overlay receipt-modal" @click.self="$emit('close')">
    <div class="receipt-content" id="receipt-printarea">
      <div class="text-center header">
        <div v-if="settings?.receipt_logo" class="receipt-logo-container">
          <img :src="formatImageUrl(settings.receipt_logo)" class="receipt-logo" alt="Logo" />
        </div>
        <h3>{{ settings?.store_name || 'NessaPOS Store' }}</h3>
        <p v-if="settings?.store_address">{{ settings.store_address }}</p>
        <p v-if="settings?.store_phone">Tel: {{ settings.store_phone }}</p>
      </div>
      
      <div class="divider"></div>
      
      <div class="info">
        <p v-if="order?.id">No: #{{ order.id }}</p>
        <p>Waktu: {{ order?.date || new Date().toLocaleString('id-ID') }}</p>
        <p>Kasir: {{ cashierDisplay }}</p>
        <p v-if="order?.payment_method">Metode: {{ order.payment_method }}</p>
      </div>

      <div class="divider"></div>

      <div class="items">
        <div v-for="(item, i) in (order?.items || [])" :key="i" class="item">
          <div class="item-name">{{ item.product_name }}</div>
          <div class="item-line">
            <span>{{ item.quantity }} x {{ formatCurrency(item.price) }}</span>
            <span>{{ formatCurrency(Number(item.price || 0) * Number(item.quantity || 0)) }}</span>
          </div>
          <div v-if="Number(item.discount || 0) > 0" class="item-discount">
             Diskon: -{{ formatCurrency(item.discount) }}
          </div>
        </div>
      </div>

      <div class="divider"></div>

      <div class="summary">
        <div class="summary-line">
          <span>Subtotal</span>
          <span>{{ formatCurrency(order?.total_amount) }}</span>
        </div>
        <div v-if="Number(order?.discount || 0) > 0" class="summary-line">
          <span>Diskon</span>
          <span>-{{ formatCurrency(order.discount) }}</span>
        </div>
        <div v-if="Number(order?.tax_amount || 0) > 0" class="summary-line">
          <span>{{ settings?.tax_type === 'inclusive' ? 'Pajak (Inc)' : 'Pajak (Exc)' }}</span>
          <span>{{ settings?.tax_type === 'inclusive' ? '' : '+' }}{{ formatCurrency(order.tax_amount) }}</span>
        </div>
        <div class="summary-line total">
          <span>TOTAL</span>
          <span>{{ formatCurrency(order?.final_amount) }}</span>
        </div>
        <div class="summary-line">
          <span>Bayar</span>
          <span>{{ formatCurrency(order?.amount_paid) }}</span>
        </div>
        <div class="summary-line">
          <span>Kembalian</span>
          <span>{{ formatCurrency(order?.change_amount) }}</span>
        </div>
      </div>

      <div class="divider"></div>

      <div class="footer text-center">
        <p style="white-space: pre-line;">{{ settings?.receipt_text || 'Terima Kasih Atas Kunjungan Anda' }}</p>
      </div>

      <div class="actions hide-print">
        <button class="btn-print" @click="handlePrint" :disabled="printing">
          {{ printing ? 'Mencetak...' : 'Cetak Struk' }}
        </button>
        <button class="btn-close" @click="$emit('close')">Tutup</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { formatCurrency } from '../utils/format'
import { printReceipt as unifiedPrint, resolveReceiptLogoUrl } from '../utils/printer'

const props = defineProps<{
  order: any,
  settings: any
}>()

const emit = defineEmits(['close'])
const printing = ref(false)

const formatImageUrl = (path: string) => resolveReceiptLogoUrl(path)

const cashierDisplay = computed(() => {
  if (props.order?.cashier_name) return props.order.cashier_name
  if (props.order?.user_name) return props.order.user_name
  if (props.order?.user?.username) return props.order.user.username
  try {
    const u = JSON.parse(localStorage.getItem('user') || '{}')
    if (u?.username) return u.username
  } catch {}
  return `User ID ${props.order?.user_id || '-'}`
})

const handlePrint = async () => {
  if (printing.value) return
  printing.value = true
  try {
    await unifiedPrint(props.order)
    emit('close')
  } catch (e: any) {
    console.error('Print failed:', e)
    alert('Gagal mencetak struk: ' + (e?.message || e))
  } finally {
    printing.value = false
  }
}

const handleKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape') {
    emit('close')
  } else if (e.key === 'Enter') {
    handlePrint()
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(15, 23, 42, 0.8);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
  padding: 16px;
}

.receipt-content {
  background: white;
  width: 100%;
  max-width: 380px;
  max-height: 90vh;
  overflow-y: auto;
  padding: 24px 26px;
  border-radius: 10px;
  font-family: 'Courier New', Courier, monospace;
  color: #000;
}

.text-center {
  text-align: center;
}

.header h3 {
  margin: 0 0 6px 0;
  font-size: 18px;
}
.header p {
  margin: 3px 0;
  font-size: 13px;
}

.receipt-logo-container {
  margin-bottom: 12px;
  display: flex;
  justify-content: center;
}

.receipt-logo {
  max-width: 80px;
  max-height: 80px;
  object-fit: contain;
  filter: grayscale(100%) contrast(150%);
}

.divider {
  border-bottom: 1px dashed #000;
  margin: 12px 0;
}

.info p {
  margin: 3px 0;
  font-size: 13px;
}

.item {
  margin-bottom: 10px;
}
.item-name {
  font-weight: bold;
  font-size: 13px;
}
.item-line {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  font-size: 13px;
}
.item-discount {
  font-size: 12px;
  font-style: italic;
  text-align: right;
  margin-top: 2px;
}

.summary-line {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 5px;
  font-size: 13px;
}
.summary-line.total {
  font-weight: bold;
  font-size: 15px;
  margin-top: 6px;
  margin-bottom: 6px;
}

.footer {
  margin-top: 12px;
  font-size: 13px;
}
.footer p {
  margin: 0;
}

.actions {
  display: flex;
  gap: 12px;
  margin-top: 22px;
}

.btn-print {
  flex: 1;
  padding: 12px;
  background: #0f172a;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: bold;
  cursor: pointer;
}
.btn-print:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-close {
  flex: 1;
  padding: 12px;
  background: #e2e8f0;
  color: #0f172a;
  border: none;
  border-radius: 8px;
  font-weight: bold;
  cursor: pointer;
}
@media print {
  .modal-overlay.receipt-modal {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: auto !important;
    background: white !important;
    display: block !important;
    padding: 0 !important;
    margin: 0 !important;
    backdrop-filter: none !important;
    z-index: 9999 !important;
  }
  
  .receipt-content {
    box-shadow: none !important;
    border: none !important;
    width: 100% !important;
    max-width: 80mm !important;
    max-height: none !important;
    overflow: visible !important;
    padding: 0 !important;
    margin: 0 !important;
    display: block !important;
  }
  
  .hide-print {
    display: none !important;
  }
}
</style>
