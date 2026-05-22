<script setup>
import HeaderComponent from '../../Components/HeaderComponent.vue';
import { router } from '@inertiajs/vue3';
import { ref, reactive, watch, onMounted } from 'vue';
import axios from 'axios';

const api = axios.create({
  baseURL: 'https://cuibxmcjdkgjffmmzwgd.supabase.co/rest/v1',
  headers: {
    apikey: 'sb_publishable_5BPLZkbZvQXw7ZfrMWufFA_K77_nZxh',
    Authorization: 'Bearer sb_publishable_5BPLZkbZvQXw7ZfrMWufFA_K77_nZxh',
    'Content-Type': 'application/json',
  },
});

const view = ref('grid');
const sortBy = ref('rating');
const sortOrder = ref('desc');
const loading = ref(false);
const error = ref(null);
const restaurants = ref([]);

const settings = reactive({
  list_icon: '',
  grid_icon: '',
  sort_desc_icon: '',
  sort_asc_icon: '',
  reset_icon: '',
  close_icon: '',
  restaurant_placeholder: '',
});

const loadSettings = async () => {
  try {
    const { data } = await api.get('/site_settings', {
      params: { id: 'eq.1', select: '*' },
    });
    if (data?.[0]) {
      Object.keys(data[0]).forEach(key => {
        if (data[0][key] !== null && data[0][key] !== undefined) {
          settings[key] = data[0][key];
        }
      });
    }
  } catch (err) {
    console.error('Ошибка загрузки настроек:', err);
  }
};

const loadRestaurants = async () => {
  loading.value = true;
  error.value = null;
  try {
    const params = { select: '*', order: `${sortBy.value}.${sortOrder.value}` };
    const { data } = await api.get('/restaurants', { params });
    restaurants.value = data || [];
  } catch (err) {
    error.value = 'Не удалось загрузить рестораны.';
  } finally { loading.value = false; }
};

const isGrid = () => view.value === 'grid';
const goToRestaurant = (id) => router.visit(`/product/${id}`);
const applyFilters = () => loadRestaurants();
const resetFilters = () => {
  sortBy.value = 'rating';
  sortOrder.value = 'desc';
  loadRestaurants();
};

const onIconError = (e) => {
  e.target.style.display = 'none';
};

const onImageError = (e) => {
  e.target.src = 'data:image/svg+xml,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="267" viewBox="0 0 400 267" fill="none">' +
    '<rect width="400" height="267" fill="%23eef0f2"/>' +
    '<text x="200" y="140" text-anchor="middle" fill="%23ccc" font-size="16" font-family="sans-serif">Нет фото</text>' +
    '</svg>'
  );
};

watch([sortBy, sortOrder], applyFilters);
onMounted(() => {
  loadSettings();
  loadRestaurants();
});
</script>

<template>
  <HeaderComponent />
  <section class="page">
    <div class="content-container">
      <!-- ФИЛЬТРЫ -->
      <div class="filters">
        <div class="filters__row">
          <div class="filters__sort-group">
            <select v-model="sortBy" class="filter-select">
              <option value="rating">По рейтингу</option>
              <option value="title">По названию</option>
              <option value="delivery_time">По времени доставки</option>
            </select>
            
            <!-- Кнопка сортировки по убыванию (↓) -->
            <button 
              @click="sortOrder = 'desc'"
              :class="['filter-btn', 'filter-btn--sort', { 'filter-btn--active': sortOrder === 'desc' }]"
              title="По убыванию"
            >
              <img :src="settings.sort_desc_icon" class="btn-icon" @error="onIconError" />
            </button>
            
            <!-- Кнопка сортировки по возрастанию (↑) -->
            <button 
              @click="sortOrder = 'asc'"
              :class="['filter-btn', 'filter-btn--sort', { 'filter-btn--active': sortOrder === 'asc' }]"
              title="По возрастанию"
            >
              <img :src="settings.sort_asc_icon" class="btn-icon" @error="onIconError" />
            </button>
          </div>
          
          <div class="filters__actions">
            <!-- Кнопка переключения вида (сетка/список) -->
            <button 
              @click="view = view === 'grid' ? 'list' : 'grid'" 
              class="filter-btn" 
              :title="view === 'grid' ? 'Список' : 'Сетка'"
            >
              <img v-if="view === 'grid'" :src="settings.list_icon" class="btn-icon" @error="onIconError" />
              <img v-else :src="settings.grid_icon" class="btn-icon" @error="onIconError" />
            </button>
            
            <!-- Кнопка сброса -->
            <button @click="resetFilters" class="filter-btn filter-btn--reset" title="Сбросить фильтры">
              <img :src="settings.reset_icon" class="btn-icon" @error="onIconError" />
            </button>
          </div>
        </div>
        
        <!-- Активные фильтры -->
        <div v-if="sortBy !== 'rating' || sortOrder !== 'desc'" class="filters__active">
          <span class="active-label">Фильтры:</span>
          
          <span v-if="sortBy !== 'rating'" class="active-tag">
            {{ sortBy === 'title' ? 'По названию' : 'По доставке' }}
            <button @click="sortBy = 'rating'" class="tag-close">
              <img :src="settings.close_icon" class="tag-icon" @error="onIconError" />
            </button>
          </span>
          
          <span v-if="sortOrder !== 'desc'" class="active-tag">
            По возрастанию
            <button @click="sortOrder = 'desc'" class="tag-close">
              <img :src="settings.close_icon" class="tag-icon" @error="onIconError" />
            </button>
          </span>
          
          <button @click="resetFilters" class="clear-all">Сбросить всё</button>
        </div>
      </div>

      <!-- СТАТУСЫ -->
      <div v-if="loading" class="status">Загрузка ресторанов...</div>
      <div v-else-if="error" class="status error">
        {{ error }}
        <button @click="loadRestaurants" class="retry-btn">Повторить</button>
      </div>
      <div v-else-if="!restaurants.length" class="status">Рестораны не найдены</div>

      <!-- КАРТОЧКИ -->
      <div v-else :class="['grid', { list: !isGrid() }]">
        <article v-for="r in restaurants" :key="r.id" class="card" @click="goToRestaurant(r.id)">
          <div class="card-img-wrapper">
            <img :src="r.img || settings.restaurant_placeholder" :alt="r.title" loading="lazy" @error="onImageError" />
          </div>
          <div class="card-body">
            <h3>{{ r.title }}</h3>
            <div class="rating-row">
              <span class="star">★</span>
              <span class="rating-value">{{ r.rating }}</span>
              <span class="delivery-time">30-45 мин</span>
            </div>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* ========== БАЗА ========== */
.page {
  background: #fefaf5;
  min-height: 100vh;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  color: #1e1e1e;
  line-height: 1.5;
  padding-top: 30px;
}

.content-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1.5rem 3rem;
}

/* ========== ФИЛЬТРЫ ========== */
.filters {
  background: #fff;
  border-radius: 20px;
  padding: 20px;
  margin-bottom: 24px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
}

.filters__row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.filters__sort-group {
  display: flex;
  gap: 8px;
  flex: 1;
}

.filter-select {
  padding: 12px 16px;
  border: 2px solid #eee;
  border-radius: 14px;
  font-size: 15px;
  background: #fafafa;
  cursor: pointer;
  transition: all 0.2s;
  flex: 1;
  max-width: 250px;
  color: #333;
  font-weight: 500;
}

.filter-select:focus {
  outline: none;
  border-color: #ff6b00;
  background: #fff;
  box-shadow: 0 0 0 4px rgba(255, 107, 0, 0.06);
}

.filters__actions {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}

/* ========== КНОПКИ ========== */
.filter-btn {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #eee;
  border-radius: 14px;
  background: #fafafa;
  cursor: pointer;
  transition: all 0.2s;
}

.filter-btn:hover {
  background: #f0f0f0;
  border-color: #ddd;
}

/* Кнопки сортировки */
.filter-btn--sort {
  width: 44px;
  height: 44px;
  flex-shrink: 0;
}

/* Активная кнопка сортировки */
.filter-btn--active {
  border-color: #ff6b00 !important;
  background: #fff8f0 !important;
  box-shadow: 0 0 0 2px rgba(255, 107, 0, 0.15);
}

/* Неактивная кнопка сортировки */
.filter-btn--sort:not(.filter-btn--active) {
  border-color: #eee;
  background: #fafafa;
  opacity: 0.6;
}

.filter-btn--sort:not(.filter-btn--active):hover {
  opacity: 1;
  border-color: #ddd;
  background: #f0f0f0;
}

/* Кнопка сброса */
.filter-btn--reset:hover {
  border-color: #e74c3c;
  color: #e74c3c;
  background: #fff5f5;
}

.btn-icon {
  width: 20px;
  height: 20px;
  object-fit: contain;
}

/* ========== АКТИВНЫЕ ФИЛЬТРЫ ========== */
.filters__active {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #f0f0f0;
  flex-wrap: wrap;
}

.active-label {
  font-size: 13px;
  color: #999;
  font-weight: 500;
}

.active-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: #fff8f0;
  border: 1px solid #ff6b00;
  border-radius: 20px;
  font-size: 13px;
  color: #ff6b00;
  font-weight: 500;
}

.tag-close {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
}

.tag-icon {
  width: 12px;
  height: 12px;
  object-fit: contain;
}

.clear-all {
  background: none;
  border: none;
  color: #e74c3c;
  cursor: pointer;
  font-size: 13px;
  font-weight: 500;
  margin-left: auto;
  padding: 4px 8px;
  border-radius: 8px;
  transition: 0.2s;
}

.clear-all:hover {
  background: #fff5f5;
}

/* ========== СЕТКА КАРТОЧЕК ========== */
.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.grid.list {
  grid-template-columns: 1fr;
}

/* ========== КАРТОЧКА ========== */
.card {
  border-radius: 20px;
  overflow: hidden;
  cursor: pointer;
  transition: 0.2s;
  background: #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.card:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
}

.card-img-wrapper {
  position: relative;
  width: 100%;
  padding-top: 66.67%;
  overflow: hidden;
  background: #eef0f2;
}

.card-img-wrapper img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s;
}

.card:hover .card-img-wrapper img {
  transform: scale(1.06);
}

.card-body {
  padding: 14px 16px 16px;
}

.card-body h3 {
  margin: 0 0 8px;
  font-size: 17px;
  font-weight: 600;
  color: #1a1a1a;
}

.rating-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.star {
  color: #ff6b00;
  font-size: 15px;
}

.rating-value {
  font-size: 14px;
  font-weight: 600;
  color: #333;
}

.delivery-time {
  margin-left: auto;
  font-size: 13px;
  color: #999;
  background: #fafafa;
  padding: 4px 10px;
  border-radius: 20px;
}

/* ========== СТАТУСЫ ========== */
.status {
  text-align: center;
  padding: 60px 20px;
  font-size: 18px;
  color: #666;
}

.status.error {
  color: #e74c3c;
}

.retry-btn {
  margin-top: 15px;
  padding: 12px 28px;
  background: #ff6b00;
  color: #fff;
  border: none;
  border-radius: 40px;
  cursor: pointer;
  font-weight: 600;
  font-size: 15px;
  transition: 0.2s;
}

.retry-btn:hover {
  background: #e05e00;
}

/* ========== РЕЖИМ СПИСКА ========== */
.grid.list .card {
  display: flex;
  flex-direction: row;
}

.grid.list .card-img-wrapper {
  width: 200px;
  padding-top: 0;
  height: 140px;
  flex-shrink: 0;
}

.grid.list .card-body {
  display: flex;
  flex-direction: column;
  justify-content: center;
}

/* ========== АДАПТИВ ========== */
@media (max-width: 768px) {
  .filters__row {
    flex-direction: column;
  }

  .filters__sort-group {
    width: 100%;
  }

  .filter-select {
    max-width: 100%;
  }

  .filters__actions {
    width: 100%;
    justify-content: flex-end;
  }

  .grid {
    grid-template-columns: 1fr;
  }

  .grid.list .card {
    flex-direction: column;
  }

  .grid.list .card-img-wrapper {
    width: 100%;
    height: 180px;
  }
}
</style>