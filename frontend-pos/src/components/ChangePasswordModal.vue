<template>
  <div class="modal-overlay">
    <div class="modal-content">
      <h2>Ganti Password</h2>
      <p class="subtitle">Masukkan password lama dan password baru.</p>

      <div class="form-group">
        <label>Password Lama</label>
        <input v-model="oldPassword" type="password" placeholder="Password lama" />
      </div>

      <div class="form-group">
        <label>Password Baru</label>
        <input v-model="newPassword" type="password" placeholder="Minimal 6 karakter" />
      </div>

      <div class="form-group">
        <label>Ulangi Password Baru</label>
        <input v-model="confirmPassword" type="password" placeholder="Ulangi password baru" />
      </div>

      <div class="actions">
        <button class="btn-cancel" @click="$emit('close')">Batal</button>
        <button class="btn-confirm" :disabled="loading" @click="submit">
          <span v-if="loading">Menyimpan...</span>
          <span v-else>Simpan</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { ChangePassword } from '../../wailsjs/go/api/API'
import { useAuthStore } from '../store/auth'

const emit = defineEmits(['close', 'success'])
const auth = useAuthStore()

const oldPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const loading = ref(false)

const submit = async () => {
  if (!oldPassword.value || !newPassword.value || !confirmPassword.value) {
    return alert('Lengkapi semua field.')
  }
  if (newPassword.value.length < 6) {
    return alert('Password baru minimal 6 karakter.')
  }
  if (newPassword.value !== confirmPassword.value) {
    return alert('Konfirmasi password tidak sama.')
  }
  if (!auth.user?.id) {
    return alert('User tidak valid.')
  }

  loading.value = true
  try {
    await ChangePassword(auth.user.id, oldPassword.value, newPassword.value)
    alert('Password berhasil diubah.')
    emit('success')
    emit('close')
  } catch (e) {
    alert('Gagal mengganti password: ' + e)
  } finally {
    loading.value = false
  }
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
  padding: 28px;
  border-radius: 16px;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  color: #1e293b;
}
h2 { margin: 0 0 6px; font-weight: 800; }
.subtitle { color: #64748b; margin-bottom: 18px; font-size: 13px; }
.form-group { margin-bottom: 14px; }
.form-group label { display: block; font-weight: 700; margin-bottom: 6px; color: #475569; font-size: 12px; text-transform: uppercase; }
.form-group input {
  width: 100%;
  padding: 10px 12px;
  border: 2px solid #f1f5f9;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  background: #f8fafc;
}
.actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 12px;
}
.btn-cancel {
  background: #e2e8f0;
  color: #475569;
  border: none;
  padding: 10px 16px;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
}
.btn-confirm {
  background: #0ea5e9;
  color: white;
  border: none;
  padding: 10px 18px;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
}
.btn-confirm:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
