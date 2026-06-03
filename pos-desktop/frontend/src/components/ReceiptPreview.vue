<template>
  <div class="modal-overlay receipt-modal">
    <div class="receipt-content" id="receipt-printarea">
      <div class="text-center header">
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
          <div class="item-detail">
            <span>{{ item.quantity }} x {{ item.price.toLocaleString() }}</span>
            <span>Rp {{ (item.price * item.quantity).toLocaleString() }}</span>
          </div>
          <div v-if="item.discount > 0" class="item-discount">
             Diskon: -Rp {{ item.discount.toLocaleString() }}
          </div>
        </div>
      </div>

      <div class="divider"></div>

      <div class="summary">
        <div class="row">
          <span>Subtotal:</span>
          <span>Rp {{ order.total_amount.toLocaleString() }}</span>
        </div>
        <div v-if="order.discount > 0" class="row">
          <span>Diskon:</span>
          <span>-Rp {{ order.discount.toLocaleString() }}</span>
        </div>
        <div v-if="order.tax_amount > 0" class="row">
          <span>Pajak ({{ settings?.tax_type === 'inclusive' ? 'Inc' : 'Exc' }}):</span>
          <span>{{ settings?.tax_type === 'inclusive' ? '' : '+' }}Rp {{ order.tax_amount.toLocaleString() }}</span>
        </div>
        <div class="row grand-total">
          <span>Total:</span>
          <span>Rp {{ order.final_amount.toLocaleString() }}</span>
        </div>
      </div>

      <div class="divider"></div>

      <div class="summary">
        <div class="row">
          <span>{{ order.payment_method }}:</span>
          <span>Rp {{ order.amount_paid.toLocaleString() }}</span>
        </div>
        <div class="row">
          <span>Change:</span>
          <span>Rp {{ order.change_amount.toLocaleString() }}</span>
        </div>
      </div>

      <div class="footer text-center">
        <p>{{ settings?.receipt_text || 'Thank You!' }}</p>
      </div>

      <div class="actions hide-print">
        <button class="btn-print" @click="printReceipt">Print</button>
        <button class="btn-close" @click="$emit('close')">Close</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { PrintReceiptThermal } from '../../wailsjs/go/api/API'
import { onMounted, onUnmounted } from 'vue'

const props = defineProps<{
  order: any,
  settings: any
}>()

const emit = defineEmits(['close'])

const printReceipt = async () => {
  try {
    const payload = JSON.parse(JSON.stringify(props.order))
    await PrintReceiptThermal(payload)
  } catch (e) {
    console.error('Thermal print failed:', e)
    alert('Gagal mencetak struk ke printer thermal.')
  } finally {
    // Auto close after print attempt
    setTimeout(() => {
      emit('close')
    }, 300)
  }
}

const handleKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape') {
    emit('close')
  } else if (e.key === 'Enter') {
    printReceipt()
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
</style>
