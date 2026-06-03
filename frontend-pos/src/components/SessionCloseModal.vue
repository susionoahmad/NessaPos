<template>
  <div class="modal-overlay">
    <div class="modal-content">
      <h2>Tutup Sesi Kasir</h2>
      <p class="subtitle">Hitung rincian fisik uang di laci untuk rekonsiliasi</p>

      <div class="stats-grid">
        <div class="stat-item">
          <label>Modal Awal</label>
          <div class="val">{{ formatCurrency(session.start_amount) }}</div>
        </div>
        <div class="stat-item">
          <label>Penjualan Tunai</label>
          <div class="val">{{ formatCurrency(sales) }}</div>
        </div>
        <div class="stat-item highlight">
          <label>Estimasi Saldo Akhir</label>
          <div class="val">{{ formatCurrency(toNum(session.start_amount) + toNum(sales)) }}</div>
        </div>
      </div>

      <div class="form-group">
        <label>Total Uang Fisik (Rp)</label>
        <input 
          type="text" 
          :value="formatCurrency(physicalAmount)" 
          readonly 
          class="total-display"
          :class="{ 'text-danger': difference < 0, 'text-success': difference > 0 }"
        />
        <div v-if="difference !== 0" class="diff-label">
          <div class="diff-badge" :class="difference >= 0 ? 'bg-success' : 'bg-danger'">
            Selisih: {{ difference > 0 ? '+' : '' }}{{ formatCurrency(difference) }}
          </div>
        </div>
      </div>

      <div class="denom-container">
        <!-- Uang Kertas -->
        <div class="denom-section">
          <h3>Uang Kertas</h3>
          <div v-for="(denom, index) in paperDenoms" :key="'p'+denom.val" class="denom-row">
            <label>{{ formatCurrency(denom.val) }}</label>
            <div class="input-wrapper">
              <input 
                type="number" 
                v-model.number="denom.count" 
                @input="calcTotal"
                @keydown.enter.prevent="focusNext('p', index)"
                :ref="el => paperRefs[index] = el"
                min="0"
                placeholder="0"
              />
              <span class="x-mark">x</span>
            </div>
          </div>
        </div>

        <!-- Uang Logam -->
        <div class="denom-section">
          <h3>Uang Logam</h3>
          <div v-for="(denom, index) in coinDenoms" :key="'c'+denom.val" class="denom-row">
            <label>{{ formatCurrency(denom.val) }}</label>
            <div class="input-wrapper">
              <input 
                type="number" 
                v-model.number="denom.count" 
                @input="calcTotal"
                @keydown.enter.prevent="focusNext('c', index)"
                :ref="el => coinRefs[index] = el"
                min="0"
                placeholder="0"
              />
              <span class="x-mark">x</span>
            </div>
          </div>
        </div>
      </div>

      <div class="actions">
        <button class="btn-cancel" @click="$emit('cancel')">Batal</button>
        <button 
          class="btn-confirm" 
          @click="handleClose" 
          :disabled="physicalAmount <= 0"
        >
          Tutup Sesi (Setor ke Brankas)
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue'
import { formatCurrency, toNum } from '../utils/format'

const props = defineProps<{
  session: any
  sales: number
}>()

const emit = defineEmits(['close', 'cancel'])

const physicalAmount = ref(0)

const paperDenoms = ref([
  { val: 100000, count: 0 },
  { val: 50000, count: 0 },
  { val: 20000, count: 0 },
  { val: 10000, count: 0 },
  { val: 5000, count: 0 },
  { val: 2000, count: 0 },
  { val: 1000, count: 0 },
])

const coinDenoms = ref([
  { val: 1000, count: 0 },
  { val: 500, count: 0 },
  { val: 200, count: 0 },
  { val: 100, count: 0 },
])

const paperRefs = ref<any[]>([])
const coinRefs = ref<any[]>([])

onMounted(() => {
  nextTick(() => {
    if (paperRefs.value[0]) paperRefs.value[0].focus()
  })
})

const calcTotal = () => {
  let t = 0
  paperDenoms.value.forEach(d => t += d.val * (d.count || 0))
  coinDenoms.value.forEach(d => t += d.val * (d.count || 0))
  physicalAmount.value = t
}

const difference = computed(() => {
  const expected = toNum(props.session.start_amount) + toNum(props.sales)
  return physicalAmount.value - expected
})

const focusNext = (type: string, index: number) => {
  if (type === 'p') {
    if (index < paperDenoms.value.length - 1) {
      paperRefs.value[index + 1].focus()
    } else {
      coinRefs.value[0].focus()
    }
  } else {
    if (index < coinDenoms.value.length - 1) {
      coinRefs.value[index + 1].focus()
    } else {
      if (physicalAmount.value > 0) {
        handleClose()
      }
    }
  }
}

const handleClose = () => {
  const allDenoms: Record<string, number> = {}
  paperDenoms.value.forEach(d => allDenoms[d.val.toString()] = d.count || 0)
  coinDenoms.value.forEach(d => {
      const key = d.val.toString()
      allDenoms[key] = (allDenoms[key] || 0) + (d.count || 0)
  })

  emit('close', {
    physicalAmount: physicalAmount.value,
    denoms: JSON.stringify(allDenoms)
  })
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(15, 23, 42, 0.8);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}
.modal-content {
  background: white;
  padding: 30px;
  border-radius: 20px;
  width: 100%;
  max-width: 600px;
  max-height: 95vh;
  overflow-y: auto;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  color: #1e293b;
}

h2 { margin: 0 0 4px; font-weight: 800; }
.subtitle { color: #64748b; margin-bottom: 24px; font-size: 14px; }

.stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1.5fr;
  gap: 12px;
  margin-bottom: 24px;
}
.stat-item {
  padding: 12px;
  background: #f8fafc;
  border-radius: 12px;
  text-align: center;
}
.stat-item label { font-size: 10px; text-transform: uppercase; color: #64748b; font-weight: 800; display: block; margin-bottom: 4px; letter-spacing: 0.05em; }
.stat-item .val { font-size: 14px; font-weight: 700; color: #1e293b; }
.stat-item.highlight { background: #f0f9ff; border: 1px solid #bae6fd; }
.stat-item.highlight .val { color: #0369a1; font-size: 16px; }

.form-group { margin-bottom: 24px; }
.form-group label { display: block; font-weight: 700; margin-bottom: 8px; color: #475569; font-size: 13px; text-transform: uppercase; }
.total-display {
  width: 100%;
  padding: 14px;
  font-size: 24px;
  font-weight: 800;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  background: #f8fafc;
  text-align: center;
}
.text-danger { color: #ef4444 !important; }
.text-success { color: #22c55e !important; }

.diff-label { text-align: center; margin-top: 8px; font-size: 14px; color: #64748b; }

.denom-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 30px;
  margin-bottom: 30px;
}

.denom-section h3 {
  font-size: 11px;
  text-transform: uppercase;
  color: #94a3b8;
  margin-bottom: 15px;
  letter-spacing: 0.1em;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 8px;
}

.denom-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}
.denom-row label {
  font-size: 14px;
  font-weight: 600;
  color: #334155;
  width: 80px;
}

.input-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 1;
}

.denom-row input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  outline: none;
  font-weight: 600;
  text-align: right;
}
.denom-row input:focus {
  border-color: #0ea5e9;
  box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
}
.x-mark {
  color: #94a3b8;
  font-size: 12px;
  font-weight: 700;
}

.actions { display: flex; gap: 12px; }
.actions button { flex: 1; padding: 14px; border-radius: 12px; font-weight: 700; cursor: pointer; border: none; transition: transform 0.1s; }
.actions button:active { transform: scale(0.98); }
.btn-cancel { background: #f1f5f9; color: #475569; }
.btn-confirm { background: #1e293b; color: white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2); }
.btn-confirm:disabled { background: #cbd5e1; color: #94a3b8; cursor: not-allowed; box-shadow: none; }
</style>
