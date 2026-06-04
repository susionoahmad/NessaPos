<template>
  <div class="cms-panel">
    <div class="cms-tabs">
      <button :class="{ active: cmsTab === 'landing' }" @click="cmsTab = 'landing'">Landing Manager</button>
      <button :class="{ active: cmsTab === 'blog' }" @click="cmsTab = 'blog'">Blog Manager</button>
      <button :class="{ active: cmsTab === 'affiliate' }" @click="cmsTab = 'affiliate'">Affiliate Manager</button>
      <button :class="{ active: cmsTab === 'device' }" @click="cmsTab = 'device'">Device Manager</button>
      <button :class="{ active: cmsTab === 'promotion' }" @click="cmsTab = 'promotion'">Promotion Manager</button>
    </div>

    <div v-if="alert.msg" :class="['cms-alert', alert.type]">{{ alert.msg }}</div>

    <section v-if="cmsTab === 'landing'" class="cms-section">
      <div class="cms-header">
        <div>
          <h2>Landing Page CMS</h2>
          <p>Edit hero, fitur, pricing, dan konten landing tanpa ubah kode.</p>
        </div>
        <button class="cms-primary" @click="openPageForm()">+ Page</button>
      </div>

      <div class="cms-grid">
        <article v-for="page in pages" :key="page.id" class="cms-card">
          <div class="cms-card-title">{{ page.title }}</div>
          <div class="cms-muted">/{{ page.slug }}</div>
          <div class="cms-meta">{{ page.sections?.length || 0 }} section</div>
          <div class="cms-actions">
            <button @click="openPageForm(page)">Edit</button>
            <button class="danger" @click="deletePage(page)">Hapus</button>
          </div>
        </article>
      </div>

      <div v-if="showPageForm" class="cms-form">
        <h3>{{ pageForm.id ? 'Edit Page' : 'Page Baru' }}</h3>
        <div class="form-grid">
          <label>Title<input v-model="pageForm.title" /></label>
          <label>Slug<input v-model="pageForm.slug" placeholder="home" /></label>
          <label>Meta Title<input v-model="pageForm.meta_title" /></label>
          <label class="wide">Meta Description<textarea v-model="pageForm.meta_description" rows="2"></textarea></label>
        </div>
        <div class="cms-switch"><input v-model="pageForm.is_active" type="checkbox" /> Aktif</div>

        <div class="section-editor">
          <div class="section-editor-head">
            <h4>Sections</h4>
            <button @click="addPageSection">+ Section</button>
          </div>
          <div v-for="(section, sectionIndex) in pageForm.sections" :key="sectionIndex" class="section-box">
            <div class="form-grid compact">
              <label>Name<input v-model="section.name" placeholder="hero" /></label>
              <label>Order<input v-model.number="section.order" type="number" /></label>
            </div>
            <div class="content-row" v-for="(content, contentIndex) in section.contents" :key="contentIndex">
              <select v-model="content.type">
                <option value="text">text</option>
                <option value="image">image</option>
                <option value="button">button</option>
                <option value="json">json</option>
              </select>
              <input v-model="content.key" placeholder="title" />
              <template v-if="content.type === 'json'">
                <textarea v-model="content.value" placeholder="Isi konten JSON" rows="3"></textarea>
              </template>
              <template v-else>
                <input v-model="content.value" placeholder="Isi konten" />
              </template>
              <button class="ghost" @click="section.contents.splice(contentIndex, 1)">Hapus</button>
            </div>
            <button class="ghost" @click="section.contents.push({ type: 'text', key: '', value: '' })">+ Content</button>
          </div>
        </div>

        <div class="form-actions">
          <button class="ghost" @click="showPageForm = false">Batal</button>
          <button class="cms-primary" @click="savePage">Simpan Page</button>
        </div>
      </div>
    </section>

    <section v-else-if="cmsTab === 'blog'" class="cms-section">
      <div class="cms-header">
        <div>
          <h2>Blog + SEO</h2>
          <p>Kelola artikel SEO dan sambungkan affiliate link secukupnya.</p>
        </div>
        <button class="cms-primary" @click="openPostForm()">+ Post</button>
      </div>

      <div class="cms-table">
        <div class="cms-table-row head"><span>Judul</span><span>Status</span><span>Affiliate</span><span>Aksi</span></div>
        <div v-for="post in posts" :key="post.id" class="cms-table-row">
          <span>{{ post.title }}</span>
          <span>{{ post.status }}</span>
          <span>{{ post.affiliates?.length || 0 }} link</span>
          <span class="row-actions"><button @click="openPostForm(post)">Edit</button><button class="danger" @click="deletePost(post)">Hapus</button></span>
        </div>
      </div>

      <div v-if="showPostForm" class="cms-form">
        <h3>{{ postForm.id ? 'Edit Post' : 'Post Baru' }}</h3>
        <div class="form-grid">
          <label>Title<input v-model="postForm.title" /></label>
          <label>Slug<input v-model="postForm.slug" /></label>
          <label>Status<select v-model="postForm.status"><option value="draft">draft</option><option value="published">published</option></select></label>
          <label>Published At<input v-model="postForm.published_at" type="datetime-local" /></label>
          <label class="wide">Excerpt<textarea v-model="postForm.excerpt" rows="2"></textarea></label>
          <label class="wide">Content<textarea v-model="postForm.content" rows="8"></textarea></label>
          <label>Meta Title<input v-model="postForm.meta_title" /></label>
          <label>Thumbnail<input v-model="postForm.thumbnail" /></label>
          <label class="wide">Meta Description<textarea v-model="postForm.meta_description" rows="2"></textarea></label>
        </div>
        <div class="check-list">
          <label v-for="link in affiliateLinks" :key="link.id">
            <input v-model="postForm.affiliate_ids" :value="link.id" type="checkbox" /> {{ link.title }}
          </label>
        </div>
        <div class="form-actions">
          <button class="ghost" @click="showPostForm = false">Batal</button>
          <button class="cms-primary" @click="savePost">Simpan Post</button>
        </div>
      </div>
    </section>

    <section v-else-if="cmsTab === 'affiliate'" class="cms-section">
      <div class="cms-header">
        <div>
          <h2>Affiliate Manager</h2>
          <p>Kelola link Shopee, Tokopedia, atau platform lain untuk blog dan rekomendasi device.</p>
        </div>
        <button class="cms-primary" @click="openAffiliateForm()">+ Link</button>
      </div>

      <div class="cms-grid">
        <article v-for="link in affiliateLinks" :key="link.id" class="cms-card">
          <div class="cms-card-title">{{ link.title }}</div>
          <div class="cms-muted">{{ link.platform }} - {{ link.product_name }}</div>
          <div class="cms-meta">Rp {{ Number(link.price || 0).toLocaleString('id-ID') }}</div>
          <div class="cms-actions">
            <button @click="openAffiliateForm(link)">Edit</button>
            <button class="danger" @click="deleteAffiliate(link)">Hapus</button>
          </div>
        </article>
      </div>

      <div v-if="showAffiliateForm" class="cms-form">
        <h3>{{ affiliateForm.id ? 'Edit Affiliate' : 'Affiliate Baru' }}</h3>
        <div class="form-grid">
          <label>Title<input v-model="affiliateForm.title" /></label>
          <label>Platform<input v-model="affiliateForm.platform" placeholder="shopee" /></label>
          <label>Product Name<input v-model="affiliateForm.product_name" /></label>
          <label>Price<input v-model.number="affiliateForm.price" type="number" /></label>
          <label class="wide">URL<input v-model="affiliateForm.url" /></label>
          <label>Image<input v-model="affiliateForm.image" /></label>
        </div>
        <div class="cms-switch"><input v-model="affiliateForm.is_active" type="checkbox" /> Aktif</div>
        <div class="form-actions">
          <button class="ghost" @click="showAffiliateForm = false">Batal</button>
          <button class="cms-primary" @click="saveAffiliate">Simpan Link</button>
        </div>
      </div>
    </section>

    <section v-else-if="cmsTab === 'device'" class="cms-section">
      <div class="cms-header">
        <div>
          <h2>Device Recommendation</h2>
          <p>Kelola printer, scanner, laptop, dan mapping affiliate untuk rekomendasi POS.</p>
        </div>
        <button class="cms-primary" @click="openDeviceForm()">+ Device</button>
      </div>

      <div class="cms-grid">
        <article v-for="device in devices" :key="device.id" class="cms-card">
          <div class="cms-card-title">{{ device.name }}</div>
          <div class="cms-muted">{{ device.type }} - {{ device.brand || '-' }}</div>
          <div class="cms-meta">{{ device.affiliates?.length || 0 }} affiliate</div>
          <div class="cms-actions">
            <button @click="openDeviceForm(device)">Edit</button>
            <button class="danger" @click="deleteDevice(device)">Hapus</button>
          </div>
        </article>
      </div>

      <div v-if="showDeviceForm" class="cms-form">
        <h3>{{ deviceForm.id ? 'Edit Device' : 'Device Baru' }}</h3>
        <div class="form-grid">
          <label>Name<input v-model="deviceForm.name" /></label>
          <label>Type<input v-model="deviceForm.type" placeholder="printer" /></label>
          <label>Brand<input v-model="deviceForm.brand" /></label>
          <label>Image<input v-model="deviceForm.image" /></label>
          <label class="wide">Description<textarea v-model="deviceForm.description" rows="3"></textarea></label>
        </div>
        <div class="check-list">
          <label v-for="link in affiliateLinks" :key="link.id">
            <input v-model="deviceForm.affiliate_ids" :value="link.id" type="checkbox" /> {{ link.title }}
          </label>
        </div>
        <div class="cms-switch"><input v-model="deviceForm.is_active" type="checkbox" /> Aktif</div>
        <div class="form-actions">
          <button class="ghost" @click="showDeviceForm = false">Batal</button>
          <button class="cms-primary" @click="saveDevice">Simpan Device</button>
        </div>
      </div>
    </section>

    <section v-else class="cms-section">
      <div class="cms-header">
        <div>
          <h2>Promotion & Plan</h2>
          <p>Kelola paket marketing dan diskon tanpa mengganggu approval pembayaran yang sudah berjalan.</p>
        </div>
        <div class="header-actions">
          <button class="cms-primary" @click="openPlanForm()">+ Plan</button>
          <button class="cms-primary" @click="openPromotionForm()">+ Promo</button>
        </div>
      </div>

      <div class="cms-grid">
        <article v-for="plan in plans" :key="plan.id" class="cms-card">
          <div class="cms-card-title">{{ plan.name }}</div>
          <div class="cms-muted">{{ plan.billing_type }}</div>
          <div class="cms-meta">Rp {{ Number(plan.price || 0).toLocaleString('id-ID') }}</div>
          <div class="cms-actions">
            <button @click="openPlanForm(plan)">Edit</button>
            <button class="danger" @click="deletePlan(plan)">Hapus</button>
          </div>
        </article>
        <article v-for="promo in promotions" :key="`promo-${promo.id}`" class="cms-card promo">
          <div class="cms-card-title">{{ promo.title }}</div>
          <div class="cms-muted">{{ promo.discount_type }} - {{ promo.discount_value }}</div>
          <div class="cms-meta">{{ promo.plans?.length || 0 }} plan</div>
          <div class="cms-actions">
            <button @click="openPromotionForm(promo)">Edit</button>
            <button class="danger" @click="deletePromotion(promo)">Hapus</button>
          </div>
        </article>
      </div>

      <div v-if="showPlanForm" class="cms-form">
        <h3>{{ planForm.id ? 'Edit Plan' : 'Plan Baru' }}</h3>
        <div class="form-grid">
          <label>Name<input v-model="planForm.name" /></label>
          <label>Billing<select v-model="planForm.billing_type"><option value="monthly">monthly</option><option value="yearly">yearly</option></select></label>
          <label>Price<input v-model.number="planForm.price" type="number" /></label>
          <label class="wide">Features<textarea v-model="planForm.featuresStr" rows="3" placeholder="Pisahkan dengan koma"></textarea></label>
        </div>
        <div class="cms-switch"><input v-model="planForm.is_active" type="checkbox" /> Aktif</div>
        <div class="form-actions">
          <button class="ghost" @click="showPlanForm = false">Batal</button>
          <button class="cms-primary" @click="savePlan">Simpan Plan</button>
        </div>
      </div>

      <div v-if="showPromotionForm" class="cms-form">
        <h3>{{ promotionForm.id ? 'Edit Promo' : 'Promo Baru' }}</h3>
        <div class="form-grid">
          <label>Title<input v-model="promotionForm.title" /></label>
          <label>Type<select v-model="promotionForm.discount_type"><option value="percentage">percentage</option><option value="fixed">fixed</option></select></label>
          <label>Value<input v-model.number="promotionForm.discount_value" type="number" /></label>
          <label>Start<input v-model="promotionForm.start_date" type="datetime-local" /></label>
          <label>End<input v-model="promotionForm.end_date" type="datetime-local" /></label>
          <label class="wide">Description<textarea v-model="promotionForm.description" rows="3"></textarea></label>
        </div>
        <div class="check-list">
          <label v-for="plan in plans" :key="plan.id">
            <input v-model="promotionForm.plan_ids" :value="plan.id" type="checkbox" /> {{ plan.name }}
          </label>
        </div>
        <div class="cms-switch"><input v-model="promotionForm.is_active" type="checkbox" /> Aktif</div>
        <div class="form-actions">
          <button class="ghost" @click="showPromotionForm = false">Batal</button>
          <button class="cms-primary" @click="savePromotion">Simpan Promo</button>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import api from '../api'

const cmsTab = ref('landing')
const alert = ref({ msg: '', type: 'success' })

const pages = ref<any[]>([])
const posts = ref<any[]>([])
const affiliateLinks = ref<any[]>([])
const devices = ref<any[]>([])
const plans = ref<any[]>([])
const promotions = ref<any[]>([])

const showPageForm = ref(false)
const showPostForm = ref(false)
const showAffiliateForm = ref(false)
const showDeviceForm = ref(false)
const showPlanForm = ref(false)
const showPromotionForm = ref(false)

const pageForm = ref<any>({})
const postForm = ref<any>({})
const affiliateForm = ref<any>({})
const deviceForm = ref<any>({})
const planForm = ref<any>({})
const promotionForm = ref<any>({})

const notify = (msg: string, type = 'success') => {
  alert.value = { msg, type }
  setTimeout(() => alert.value = { msg: '', type: 'success' }, 3500)
}

const apiError = (e: any) => e.response?.data?.message || e.message || 'Terjadi kesalahan'

const fetchAll = async () => {
  await Promise.all([
    fetchPages(),
    fetchPosts(),
    fetchAffiliates(),
    fetchDevices(),
    fetchPlans(),
    fetchPromotions()
  ])
}

const fetchPages = async () => { pages.value = (await api.get('/superadmin/cms/pages')).data }
const fetchPosts = async () => { posts.value = (await api.get('/superadmin/cms/posts')).data }
const fetchAffiliates = async () => { affiliateLinks.value = (await api.get('/superadmin/cms/affiliate-links')).data }
const fetchDevices = async () => { devices.value = (await api.get('/superadmin/cms/devices')).data }
const fetchPlans = async () => { plans.value = (await api.get('/superadmin/cms/plans')).data }
const fetchPromotions = async () => { promotions.value = (await api.get('/superadmin/cms/promotions')).data }

onMounted(() => {
  fetchAll().catch(e => notify(apiError(e), 'error'))
})

const textValue = (value: any) => {
  if (value === null || value === undefined) return ''
  return typeof value === 'object' ? JSON.stringify(value) : String(value)
}

const normalizeDateTime = (value: string) => value ? value.replace('T', ' ') : null

const openPageForm = (page?: any) => {
  pageForm.value = page ? {
    id: page.id,
    title: page.title,
    slug: page.slug,
    meta_title: page.meta_title || '',
    meta_description: page.meta_description || '',
    is_active: page.is_active,
    sections: (page.sections || []).map((section: any) => ({
      name: section.name,
      order: section.order,
      is_active: section.is_active,
      contents: (section.contents || []).map((content: any) => ({
        type: content.type,
        key: content.key,
        value: textValue(content.value)
      }))
    }))
  } : {
    title: '',
    slug: '',
    meta_title: '',
    meta_description: '',
    is_active: true,
    sections: [{ name: 'hero', order: 1, is_active: true, contents: [{ type: 'text', key: 'title', value: '' }] }]
  }
  showPageForm.value = true
}

const addPageSection = () => {
  pageForm.value.sections.push({ name: '', order: pageForm.value.sections.length + 1, is_active: true, contents: [] })
}

const savePage = async () => {
  try {
    const payload = { ...pageForm.value }
    delete payload.id
    if (pageForm.value.id) await api.put(`/superadmin/cms/pages/${pageForm.value.id}`, payload)
    else await api.post('/superadmin/cms/pages', payload)
    showPageForm.value = false
    await fetchPages()
    notify('Landing page berhasil disimpan.')
  } catch (e: any) {
    notify(apiError(e), 'error')
  }
}

const deletePage = async (page: any) => {
  if (!confirm(`Hapus page "${page.title}"?`)) return
  await api.delete(`/superadmin/cms/pages/${page.id}`)
  await fetchPages()
  notify('Page dihapus.')
}

const openPostForm = (post?: any) => {
  postForm.value = post ? {
    id: post.id,
    title: post.title,
    slug: post.slug,
    content: post.content || '',
    excerpt: post.excerpt || '',
    thumbnail: post.thumbnail || '',
    meta_title: post.meta_title || '',
    meta_description: post.meta_description || '',
    status: post.status || 'draft',
    published_at: post.published_at ? post.published_at.substring(0, 16) : '',
    affiliate_ids: (post.affiliates || []).map((item: any) => item.id)
  } : {
    title: '',
    slug: '',
    content: '',
    excerpt: '',
    thumbnail: '',
    meta_title: '',
    meta_description: '',
    status: 'draft',
    published_at: '',
    affiliate_ids: []
  }
  showPostForm.value = true
}

const savePost = async () => {
  try {
    const payload = { ...postForm.value, published_at: normalizeDateTime(postForm.value.published_at) }
    delete payload.id
    if (postForm.value.id) await api.put(`/superadmin/cms/posts/${postForm.value.id}`, payload)
    else await api.post('/superadmin/cms/posts', payload)
    showPostForm.value = false
    await fetchPosts()
    notify('Post berhasil disimpan.')
  } catch (e: any) {
    notify(apiError(e), 'error')
  }
}

const deletePost = async (post: any) => {
  if (!confirm(`Hapus post "${post.title}"?`)) return
  await api.delete(`/superadmin/cms/posts/${post.id}`)
  await fetchPosts()
  notify('Post dihapus.')
}

const openAffiliateForm = (link?: any) => {
  affiliateForm.value = link ? { ...link } : {
    title: '',
    url: '',
    platform: 'shopee',
    product_name: '',
    image: '',
    price: 0,
    is_active: true
  }
  showAffiliateForm.value = true
}

const saveAffiliate = async () => {
  try {
    const payload = { ...affiliateForm.value }
    delete payload.id
    if (affiliateForm.value.id) await api.put(`/superadmin/cms/affiliate-links/${affiliateForm.value.id}`, payload)
    else await api.post('/superadmin/cms/affiliate-links', payload)
    showAffiliateForm.value = false
    await fetchAffiliates()
    notify('Affiliate link berhasil disimpan.')
  } catch (e: any) {
    notify(apiError(e), 'error')
  }
}

const deleteAffiliate = async (link: any) => {
  if (!confirm(`Hapus affiliate "${link.title}"?`)) return
  await api.delete(`/superadmin/cms/affiliate-links/${link.id}`)
  await fetchAffiliates()
  notify('Affiliate link dihapus.')
}

const openDeviceForm = (device?: any) => {
  deviceForm.value = device ? {
    ...device,
    affiliate_ids: (device.affiliates || []).map((item: any) => item.id)
  } : {
    name: '',
    type: 'printer',
    brand: '',
    description: '',
    image: '',
    is_active: true,
    affiliate_ids: []
  }
  showDeviceForm.value = true
}

const saveDevice = async () => {
  try {
    const payload = { ...deviceForm.value }
    delete payload.id
    delete payload.affiliates
    if (deviceForm.value.id) await api.put(`/superadmin/cms/devices/${deviceForm.value.id}`, payload)
    else await api.post('/superadmin/cms/devices', payload)
    showDeviceForm.value = false
    await fetchDevices()
    notify('Device berhasil disimpan.')
  } catch (e: any) {
    notify(apiError(e), 'error')
  }
}

const deleteDevice = async (device: any) => {
  if (!confirm(`Hapus device "${device.name}"?`)) return
  await api.delete(`/superadmin/cms/devices/${device.id}`)
  await fetchDevices()
  notify('Device dihapus.')
}

const openPlanForm = (plan?: any) => {
  planForm.value = plan ? {
    id: plan.id,
    name: plan.name,
    price: plan.price,
    billing_type: plan.billing_type,
    featuresStr: (plan.features || []).join(', '),
    is_active: plan.is_active
  } : { name: '', price: 0, billing_type: 'monthly', featuresStr: '', is_active: true }
  showPlanForm.value = true
}

const savePlan = async () => {
  try {
    const payload = {
      name: planForm.value.name,
      price: planForm.value.price,
      billing_type: planForm.value.billing_type,
      features: (planForm.value.featuresStr || '').split(',').map((item: string) => item.trim()).filter(Boolean),
      is_active: planForm.value.is_active
    }
    if (planForm.value.id) await api.put(`/superadmin/cms/plans/${planForm.value.id}`, payload)
    else await api.post('/superadmin/cms/plans', payload)
    showPlanForm.value = false
    await fetchPlans()
    notify('Plan berhasil disimpan.')
  } catch (e: any) {
    notify(apiError(e), 'error')
  }
}

const deletePlan = async (plan: any) => {
  if (!confirm(`Hapus plan "${plan.name}"?`)) return
  await api.delete(`/superadmin/cms/plans/${plan.id}`)
  await fetchPlans()
  notify('Plan dihapus.')
}

const openPromotionForm = (promotion?: any) => {
  promotionForm.value = promotion ? {
    id: promotion.id,
    title: promotion.title,
    description: promotion.description || '',
    discount_type: promotion.discount_type,
    discount_value: promotion.discount_value,
    start_date: promotion.start_date ? promotion.start_date.substring(0, 16) : '',
    end_date: promotion.end_date ? promotion.end_date.substring(0, 16) : '',
    is_active: promotion.is_active,
    plan_ids: (promotion.plans || []).map((item: any) => item.id)
  } : {
    title: '',
    description: '',
    discount_type: 'percentage',
    discount_value: 0,
    start_date: '',
    end_date: '',
    is_active: true,
    plan_ids: []
  }
  showPromotionForm.value = true
}

const savePromotion = async () => {
  try {
    const payload = {
      ...promotionForm.value,
      start_date: normalizeDateTime(promotionForm.value.start_date),
      end_date: normalizeDateTime(promotionForm.value.end_date)
    }
    delete payload.id
    if (promotionForm.value.id) await api.put(`/superadmin/cms/promotions/${promotionForm.value.id}`, payload)
    else await api.post('/superadmin/cms/promotions', payload)
    showPromotionForm.value = false
    await Promise.all([fetchPromotions(), fetchPlans()])
    notify('Promo berhasil disimpan.')
  } catch (e: any) {
    notify(apiError(e), 'error')
  }
}

const deletePromotion = async (promotion: any) => {
  if (!confirm(`Hapus promo "${promotion.title}"?`)) return
  await api.delete(`/superadmin/cms/promotions/${promotion.id}`)
  await fetchPromotions()
  notify('Promo dihapus.')
}
</script>

<style scoped>
.cms-panel { display: flex; flex-direction: column; gap: 18px; }
.cms-tabs { display: flex; flex-wrap: wrap; gap: 10px; }
.cms-tabs button,
.cms-actions button,
.row-actions button,
.ghost {
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.1);
  color: #dbeafe;
  padding: 9px 12px;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
}
.cms-tabs button.active { background: #0ea5e9; border-color: #0ea5e9; color: white; }
.cms-section { display: flex; flex-direction: column; gap: 18px; }
.cms-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
.cms-header h2 { margin: 0; font-size: 20px; color: white; }
.cms-header p { margin: 4px 0 0; color: #94a3b8; font-size: 13px; }
.header-actions { display: flex; gap: 10px; }
.cms-primary {
  background: linear-gradient(135deg, #0ea5e9, #6366f1);
  border: none;
  color: white;
  padding: 10px 16px;
  border-radius: 9px;
  font-weight: 800;
  cursor: pointer;
}
.cms-alert { padding: 12px 16px; border-radius: 10px; font-weight: 700; }
.cms-alert.success { background: rgba(52,211,153,0.14); color: #34d399; border: 1px solid rgba(52,211,153,0.4); }
.cms-alert.error { background: rgba(239,68,68,0.14); color: #fca5a5; border: 1px solid rgba(239,68,68,0.4); }
.cms-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }
.cms-card {
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 12px;
  padding: 16px;
}
.cms-card.promo { border-top: 3px solid #f59e0b; }
.cms-card-title { color: white; font-weight: 900; margin-bottom: 4px; }
.cms-card-title,
.cms-muted,
.cms-meta {
  overflow-wrap: anywhere;
}
.cms-muted { color: #94a3b8; font-size: 13px; }
.cms-meta { color: #38bdf8; font-size: 13px; font-weight: 800; margin-top: 12px; }
.cms-actions { display: flex; gap: 8px; margin-top: 14px; }
.danger { color: #fca5a5 !important; border-color: rgba(239,68,68,0.35) !important; }
.cms-table { display: flex; flex-direction: column; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; overflow: hidden; }
.cms-table-row { display: grid; grid-template-columns: 1.5fr 110px 110px 140px; gap: 12px; align-items: center; padding: 12px 14px; background: rgba(255,255,255,0.04); border-bottom: 1px solid rgba(255,255,255,0.08); }
.cms-table-row.head { background: rgba(255,255,255,0.08); color: #94a3b8; font-size: 12px; font-weight: 900; text-transform: uppercase; }
.row-actions { display: flex; gap: 8px; }
.cms-form {
  background: rgba(15,23,42,0.84);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 14px;
  padding: 18px;
}
.cms-form h3 { margin: 0 0 14px; color: white; }
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
.form-grid.compact { grid-template-columns: 1fr 120px; }
label { display: flex; flex-direction: column; gap: 6px; color: #94a3b8; font-size: 12px; font-weight: 800; }
label.wide { grid-column: 1 / -1; }
input, select, textarea {
  width: 100%;
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.12);
  color: white;
  border-radius: 8px;
  padding: 10px 12px;
  outline: none;
}
select option { background: #1e293b; }
.cms-switch { margin: 12px 0; color: #cbd5e1; display: flex; align-items: center; gap: 8px; }
.cms-switch input { width: auto; }
.form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px; }
.section-editor { margin-top: 14px; display: flex; flex-direction: column; gap: 12px; }
.section-editor-head { display: flex; justify-content: space-between; align-items: center; }
.section-editor-head h4 { margin: 0; color: white; }
.section-box { border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 12px; display: flex; flex-direction: column; gap: 10px; }
.content-row { display: grid; grid-template-columns: 110px 1fr 2fr 90px; gap: 8px; align-items: center; }
.check-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 8px; margin-top: 12px; }
.check-list label { flex-direction: row; align-items: center; color: #cbd5e1; font-weight: 600; }
.check-list input { width: auto; }

@media (max-width: 760px) {
  .cms-header, .header-actions { flex-direction: column; align-items: stretch; }
  .form-grid, .form-grid.compact, .content-row, .cms-table-row { grid-template-columns: 1fr; }

  .cms-tabs {
    flex-wrap: nowrap;
    margin: 0 -4px;
    overflow-x: auto;
    padding-bottom: 4px;
    scrollbar-width: thin;
  }

  .cms-tabs button {
    flex: 0 0 auto;
    white-space: nowrap;
  }

  .cms-grid {
    grid-template-columns: 1fr;
  }

  .cms-header h2 {
    font-size: 18px;
  }

  .cms-primary {
    width: 100%;
  }

  .cms-actions,
  .row-actions,
  .form-actions {
    flex-direction: column;
  }

  .cms-actions button,
  .row-actions button,
  .form-actions button,
  .ghost {
    width: 100%;
    min-height: 40px;
  }

  .cms-table {
    border-radius: 10px;
  }

  .cms-table-row {
    gap: 6px;
    padding: 14px;
  }

  .cms-table-row.head {
    display: none;
  }

  .cms-form {
    padding: 14px;
  }

  .section-editor-head {
    align-items: stretch;
    flex-direction: column;
    gap: 10px;
  }

  .check-list {
    grid-template-columns: 1fr;
  }
}
</style>
