<template>
  <div class="page-container">
    <div class="header">
      <h2>Dashboard Admin</h2>
      <p>Selamat datang di panel kendali SmartPOS.</p>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="label">Total Penjualan Hari Ini</div>
        <div class="value">{{ formatCurrency(daily) }}</div>
        <router-link to="/reports" class="link">Lihat Laporan</router-link>
      </div>
      <div class="stat-card">
        <div class="label">Saldo Brankas</div>
        <div class="value">{{ formatCurrency(vaultBalance) }}</div>
        <router-link to="/reports" class="link">Kelola Kas</router-link>
      </div>
      <div class="stat-card">
        <div class="label">Produk Terdaftar</div>
        <div class="value">{{ productCount }}</div>
        <router-link to="/products" class="link">Kelola Produk</router-link>
      </div>
      <div class="stat-card">
        <div class="label">Bulan Ini</div>
        <div class="value">{{ formatCurrency(monthly) }}</div>
        <router-link to="/reports" class="link">Detail Penjualan</router-link>
      </div>
    </div>

    <div class="info-section">
      <h3>Panduan Cepat</h3>
      <ul>
        <li>Gunakan menu <strong>Products</strong> untuk menambah atau mengupdate stok barang.</li>
        <li>Gunakan menu <strong>Reports</strong> untuk melihat mutasi brankas dan rincian sesi kasir.</li>
        <li>Gunakan menu <strong>Settings</strong> untuk mengubah informasi toko dan tarif pajak.</li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'
import { formatCurrency } from '../utils/format'

const router = useRouter()
const daily = ref(0)
const monthly = ref(0)
const vaultBalance = ref(0)
const productCount = ref(0)

onMounted(async () => {
  try {
    const stats = await api.get('/orders/stats')
    daily.value = stats.data.sales || 0
    monthly.value = stats.data.monthly_sales || 0
    
    const v = await api.get('/vault')
    vaultBalance.value = v.data?.balance || 0
    
    const p = await api.get('/products')
    productCount.value = p.data?.length || 0
  } catch (e: any) {
    if (e.response?.status === 404 && e.response?.data?.needs_setup) {
      router.push('/setup-wizard')
    } else {
      console.error("Dashboard error", e)
    }
  }
})
</script>

<style scoped>
.page-container {
  padding: 32px;
  background-color: #f8fafc;
  min-height: 100vh;
}

.header {
  margin-bottom: 32px;
}

.header h2 {
  margin: 0;
  color: #0f172a; /* Darker navy for better contrast */
  font-size: 28px;
  font-weight: 800;
  letter-spacing: -0.025em;
}

.header p {
  color: #475569; /* Darker gray */
  margin-top: 6px;
  font-size: 15px;
  font-weight: 500;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 24px;
  margin-bottom: 40px;
}

.stat-card {
  background: white;
  padding: 24px;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.stat-card .label {
  font-size: 13px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 12px;
}

.stat-card .value {
  font-size: 26px;
  font-weight: 800;
  color: #0f172a; /* Darker for readability */
  margin-bottom: 20px;
}

/* Specific accent colors for values */
.stat-card:nth-child(1) .value { color: #0284c7; } /* Blue */
.stat-card:nth-child(2) .value { color: #059669; } /* Green */
.stat-card:nth-child(4) .value { color: #7c3aed; } /* Purple */

.stat-card .link {
  font-size: 14px;
  color: #3b82f6;
  text-decoration: none;
  font-weight: 700;
  display: flex;
  align-items: left;
  gap: 6px;
  margin-top: auto;
}

.stat-card .link:hover {
  color: #2563eb;
}

.info-section {
  background: white;
  padding: 32px;
  border-radius: 20px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.info-section h3 {
  margin-top: 0;
  color: #0f172a;
  font-size: 20px;
  font-weight: 800;
  margin-bottom: 20px;
}

.info-section ul {
  padding-left: 0;
  list-style: none;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 16px;
}

.info-section li {
  padding: 16px;
  background: #f1f5f9;
  border-radius: 12px;
  color: #334155;
  font-size: 14px;
  font-weight: 500;
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.info-section li strong {
  color: #0f172a;
  font-weight: 700;
}
</style>
