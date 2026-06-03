<template>
  <div class="modal-overlay">
    <div class="modal-content">
      <h2>Payment Process</h2>
      
      <div class="summary">
        Total Due: <span>Rp {{ total.toLocaleString() }}</span>
      </div>

      <div class="method-select">
        <label>Payment Method:</label>
        <div class="methods">
          <button 
            :class="['method-btn', method === 'Cash' ? 'active' : '']" 
            @click="method = 'Cash'"
          >Cash</button>
          <button 
            :class="['method-btn', method === 'QRIS' ? 'active' : '']" 
            @click="method = 'QRIS'"
          >QRIS</button>
        </div>
      </div>

      <div v-if="method === 'Cash'" class="amount-input">
        <label>Amount Received:</label>
        <input 
          v-model.number="amountReceived" 
          type="number" 
          placeholder="0" 
          ref="amountInput"
          @keyup.enter="confirm"
        />
      </div>

      <div v-if="method === 'QRIS'" class="qr-placeholder">
        <div class="qr-box">Wait for customer to scan QR (Simulated)</div>
      </div>

      <div class="change-display" v-if="method === 'Cash' && typeof amountReceived === 'number' && amountReceived > 0">
        Change: <span :class="{'negative': changeAmount < 0}">Rp {{ changeAmount.toLocaleString() }}</span>
      </div>

      <div class="actions">
        <button class="btn-cancel" @click="$emit('close')">Cancel</button>
        <button 
          class="btn-confirm" 
          :disabled="!isValid" 
          @click="confirm"
        >Confirm Payment</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'

const props = defineProps<{
  total: number
}>()

const emit = defineEmits(['close', 'confirm'])

const method = ref('Cash')
const amountReceived = ref<number | ''>('')
const amountInput = ref<HTMLInputElement | null>(null)

const handleKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape') {
    emit('close')
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
  nextTick(() => {
    if (amountInput.value) {
      amountInput.value.focus()
    }
  })
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
})

const changeAmount = computed(() => {
  if (method.value === 'QRIS') return 0
  if (amountReceived.value === '') return 0
  return (amountReceived.value as number) - props.total
})

const isValid = computed(() => {
  if (method.value === 'QRIS') return true
  if (amountReceived.value === '') return false
  return (amountReceived.value as number) >= props.total
})

const confirm = () => {
  if (isValid.value) {
    emit('confirm', {
      method: method.value,
      amount: method.value === 'Cash' ? amountReceived.value : props.total,
      change: method.value === 'Cash' ? changeAmount.value : 0
    })
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(15, 23, 42, 0.7);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.modal-content {
  background: white;
  width: 100%;
  max-width: 450px;
  padding: 30px;
  border-radius: 16px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

h2 {
  margin: 0 0 24px 0;
  color: #1e293b;
  font-size: 22px;
  font-weight: 700;
  text-align: center;
}

.summary {
  font-size: 20px;
  font-weight: 600;
  color: #334155;
  text-align: center;
  padding: 20px;
  background: #f8fafc;
  border-radius: 12px;
  margin-bottom: 24px;
}

.summary span {
  color: #0ea5e9;
  font-weight: 700;
}

.method-select {
  margin-bottom: 20px;
}
.method-select label {
  display: block;
  font-weight: 600;
  margin-bottom: 10px;
  color: #475569;
}

.methods {
  display: flex;
  gap: 12px;
}

.method-btn {
  flex: 1;
  padding: 14px;
  border: 2px solid #e2e8f0;
  background: white;
  border-radius: 10px;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 16px;
}

.method-btn.active {
  border-color: #0ea5e9;
  background: #f0f9ff;
  color: #0ea5e9;
}

.amount-input {
  margin-bottom: 20px;
}

.amount-input label {
  display: block;
  font-weight: 600;
  margin-bottom: 10px;
  color: #475569;
}

.amount-input input {
  width: 100%;
  padding: 16px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 20px;
  font-weight: 600;
  outline: none;
  transition: border-color 0.2s;
  color: #0f172a;
  box-sizing: border-box;
}

.amount-input input:focus {
  border-color: #38bdf8;
}

.qr-placeholder {
  display: flex;
  justify-content: center;
  margin: 20px 0;
}

.qr-box {
  width: 200px;
  height: 200px;
  background: #f1f5f9;
  border: 2px dashed #94a3b8;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 20px;
  color: #64748b;
  font-weight: 500;
}

.change-display {
  font-size: 18px;
  font-weight: 600;
  color: #334155;
  text-align: center;
  margin-bottom: 24px;
}

.change-display span {
  color: #10b981;
}

.change-display span.negative {
  color: #ef4444;
}

.actions {
  display: flex;
  gap: 16px;
}

.btn-cancel {
  flex: 1;
  padding: 16px;
  background: #f1f5f9;
  color: #475569;
  border: none;
  border-radius: 10px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-cancel:hover {
  background: #e2e8f0;
}

.btn-confirm {
  flex: 2;
  padding: 16px;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-confirm:hover:not(:disabled) {
  background: #059669;
}

.btn-confirm:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}
</style>
