<template>
  <div class="modal-overlay receipt-modal">
    <div class="receipt-content" id="receipt-printarea">
      <div class="text-center header">
        <div v-if="settings?.receipt_logo" class="receipt-logo-container">
          <img :src="formatImageUrl(settings.receipt_logo)" class="receipt-logo" />
        </div>
        <h3>{{ settings?.store_name || 'SmartPOS Store' }}</h3>
        <p>{{ settings?.store_address || 'Address not set' }}</p>
        <p>Tel: {{ settings?.store_phone || '-' }}</p>
      </div>
      
      <div class="divider"></div>
      
      <div class="info">
        <p>Date: {{ order.date }}</p>
        <p>Cashier: User ID {{ order.user_id }}</p>
      </div>

      <div class="divider"></div>

      <div class="items">
        <div v-for="(item, i) in order.items" :key="i" class="item">
          <div class="item-name">{{ item.product_name }}</div>
          <div class="item-line">
            <span>{{ item.quantity }} x {{ formatCurrency(item.price) }}</span>
            <span>{{ formatCurrency(item.price * item.quantity) }}</span>
          </div>
          <div v-if="item.discount > 0" class="item-discount">
             Diskon: -{{ formatCurrency(item.discount) }}
          </div>
        </div>
      </div>

      <div class="divider"></div>

      <div class="summary">
        <div class="summary-line">
          <span>Subtotal</span>
          <span>{{ formatCurrency(order.total_amount) }}</span>
        </div>
        <div v-if="order.discount > 0" class="summary-line">
          <span>Diskon</span>
          <span>-{{ formatCurrency(order.discount) }}</span>
        </div>
        <div class="summary-line">
          <span>{{ settings?.tax_type === 'inclusive' ? 'Pajak (Inc)' : 'Pajak (Exc)' }}</span>
          <span>{{ settings?.tax_type === 'inclusive' ? '' : '+' }}{{ formatCurrency(order.tax_amount) }}</span>
        </div>
        <div class="summary-line total">
          <span>TOTAL</span>
          <span>{{ formatCurrency(order.final_amount) }}</span>
        </div>
        <div class="summary-line">
          <span>Bayar</span>
          <span>{{ formatCurrency(order.amount_paid) }}</span>
        </div>
        <div class="summary-line">
          <span>Kembalian</span>
          <span>{{ formatCurrency(order.change_amount) }}</span>
        </div>
      </div>

      <div class="footer text-center">
        <p>{{ settings?.receipt_text || 'Thank You!' }}</p>
      </div>

      <div class="actions hide-print">
        <button class="btn-print" @click="handlePrint">Print</button>
        <button class="btn-close" @click="$emit('close')">Close</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { PrintReceiptThermal } from '../../wailsjs/go/api/API'
import { isWails, bridgePrintReceipt } from '../utils/bridge'
import { onMounted, onUnmounted } from 'vue'
import { formatCurrency } from '../utils/format'

const formatImageUrl = (path: string) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${import.meta.env.VITE_API_BASE_URL || ''}/storage/${path}`
}

const props = defineProps<{
  order: any,
  settings: any
}>()

const emit = defineEmits(['close'])

import { printReceipt as unifiedPrint } from '../utils/printer'

const handlePrint = async () => {
  try {
    await unifiedPrint(props.order)
  } catch (e) {
    console.error('Print failed:', e)
    window.print()
  } finally {
    emit('close')
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
}

.receipt-content {
  background: white;
  width: 100%;
  max-width: 380px;
  padding: 30px;
  border-radius: 8px;
  font-family: 'Courier New', Courier, monospace;
  color: #000;
}

.text-center {
  text-align: center;
}

.header h3 {
  margin: 0 0 8px 0;
  font-size: 20px;
}
.header p {
  margin: 4px 0;
  font-size: 14px;
}

.receipt-logo-container {
  margin-bottom: 15px;
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
  margin: 16px 0;
}

.info p {
  margin: 4px 0;
  font-size: 14px;
}

.item {
  margin-bottom: 12px;
}
.item-name {
  font-weight: bold;
  font-size: 14px;
}
.item-detail {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
}
.item-discount {
  font-size: 12px;
  font-style: italic;
  text-align: right;
  margin-top: 2px;
}

.summary .row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 6px;
  font-size: 14px;
}
.grand-total {
  font-weight: bold;
  font-size: 16px;
  margin-top: 8px;
}

.footer {
  margin-top: 24px;
  font-size: 14px;
}

.actions {
  display: flex;
  gap: 16px;
  margin-top: 32px;
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
    max-width: 80mm !important; /* Standard thermal size */
    padding: 0 !important;
    margin: 0 !important;
    display: block !important;
  }
  
  .hide-print {
    display: none !important;
  }
}
</style>
