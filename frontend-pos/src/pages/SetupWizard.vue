<template>
  <div class="wizard-wrapper">
    <div class="wizard-card">
      <div class="wizard-header">
        <div class="step-indicator">
          <div class="step" :class="{ active: currentStep >= 1, completed: currentStep > 1 }">1</div>
          <div class="line"></div>
          <div class="step" :class="{ active: currentStep >= 2, completed: currentStep > 2 }">2</div>
        </div>
        <h2>Konfigurasi Toko</h2>
        <p v-if="currentStep === 1">Lengkapi identitas dasar toko Anda.</p>
        <p v-if="currentStep === 2">Pajak dan pengaturan akhir.</p>
      </div>

      <div class="wizard-body">
        <!-- Step 1: Basic Info -->
        <div v-if="currentStep === 1" class="step-content">
          <div class="form-group">
            <label>Nama Toko</label>
            <input v-model="form.store_name" type="text" placeholder="e.g. Kedai Kopi" />
          </div>
          <div class="form-group">
            <label>Alamat Lengkap</label>
            <textarea v-model="form.store_address" rows="3" placeholder="Alamat fisik toko"></textarea>
          </div>
          <div class="form-group">
            <label>Nomor Telepon</label>
            <input v-model="form.store_phone" type="text" placeholder="08xxxxxxxx" />
          </div>
        </div>

        <!-- Step 2: Tax Info -->
        <div v-if="currentStep === 2" class="step-content">
          <div class="form-group">
            <label>Pajak Layanan (%)</label>
            <input v-model.number="form.tax_rate" type="number" step="0.5" />
            <p class="hint">Biaya tambahan otomatis pada setiap transaksi.</p>
          </div>
          
          <div class="finish-illustration">
            <div class="icon">✅</div>
            <h3>Siap Digunakan!</h3>
            <p>Anda bisa mengubah data ini kapan saja melalui menu Pengaturan.</p>
          </div>
        </div>
      </div>

      <div class="wizard-footer">
        <button v-if="currentStep > 1" class="btn-outline" @click="currentStep--">Kembali</button>
        <button v-if="currentStep < 2" class="btn-primary" @click="currentStep++" :disabled="!isStep1Valid">Lanjut</button>
        <button v-if="currentStep === 2" class="btn-primary" @click="handleFinish" :disabled="loading">
          {{ loading ? 'Menyimpan...' : 'Selesaikan Pengaturan' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'

const router = useRouter()
const currentStep = ref(1)
const loading = ref(false)

const form = reactive({
  store_name: JSON.parse(localStorage.getItem('tenant') || '{}').name || '',
  store_address: '',
  store_phone: '',
  tax_rate: 0
})

const isStep1Valid = computed(() => {
  return form.store_name && form.store_address && form.store_phone
})

const handleFinish = async () => {
  loading.value = true
  try {
    await api.post('/settings/setup', form)
    router.push('/dashboard')
  } catch (e) {
    console.error("Setup failed", e)
    alert("Gagal menyimpan pengaturan. Silakan coba lagi.")
    loading.value = false
  }
}
</script>

<style scoped>
.wizard-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100vh;
  background: #f1f5f9;
}

.wizard-card {
  background: white;
  width: 100%;
  max-width: 500px;
  border-radius: 20px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.wizard-header {
  padding: 30px;
  text-align: center;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.step-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 20px;
}
.step {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  color: #64748b;
  font-size: 14px;
}
.step.active { background: #0ea5e9; color: white; }
.step.completed { background: #10b981; color: white; }
.line {
  width: 40px;
  height: 2px;
  background: #e2e8f0;
}

.wizard-header h2 { margin: 0; font-size: 20px; color: #0f172a; }
.wizard-header p { margin: 8px 0 0; color: #64748b; font-size: 14px; }

.wizard-body { padding: 30px; min-height: 240px; }

.form-group { margin-bottom: 20px; }
.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 700;
  color: #334155;
  margin-bottom: 6px;
}
.form-group input, .form-group textarea {
  width: 100%;
  padding: 12px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  box-sizing: border-box;
}
.finish-illustration {
  text-align: center;
  padding: 20px 0;
}
.finish-illustration .icon { font-size: 48px; margin-bottom: 12px; }

.wizard-footer {
  padding: 20px 30px;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.btn-primary {
  padding: 10px 24px;
  background: #0ea5e9;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
}
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-outline {
  padding: 10px 24px;
  background: transparent;
  border: 1px solid #e2e8f0;
  color: #64748b;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
}
</style>
