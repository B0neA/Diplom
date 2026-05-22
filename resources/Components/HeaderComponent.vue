<template>
  <header>
    <div class="headerBody">
      <div class="headerLeft">
        <a href="/"><img :src="settings.logo_icon" alt="Logo" height="45" width="50" @error="onIconError"></a>
        
        <div class="search-container">
          <input class="search-input" v-model="searchQuery" placeholder="Поиск ресторанов..."
            @input="searchRestaurants" @focus="showResults = !!searchQuery" @blur="hideResults">
          <div v-if="showResults && filtered.length" class="search-results">
            <div v-for="r in filtered" :key="r.id" class="search-result-item" @mousedown="goToRestaurant(r.id)">
              <span class="restaurant-name">{{ r.title }}</span>
              <span class="restaurant-rating">★ {{ r.rating }}</span>
            </div>
          </div>
          <div v-if="showResults && searchQuery && !filtered.length" class="search-results">
            <div class="search-no-results">Рестораны не найдены</div>
          </div>
        </div>
        
        <input class="location-input" v-model="searchLocation" placeholder="Ваш Адрес">
      </div>
                  
      <nav class="headerNav">
  <!-- Корзина -->
  <a v-if="isOnHomePage" href="#" id="headerCart" @click.prevent="showCart = !showCart">Корзина</a>
  
  <!-- Профиль / Вход -->
  <a v-if="user" href="/profile" id="headerCart" class="header-profile-link">{{ userName }}</a>
  <a v-else href="/auth" id="headerCart">Войти</a>
</nav>

      <!-- Поп-ап корзины -->
      <div v-if="showCart" class="cart-popup-overlay" @click="showCart = false">
        <div class="cart-popup" @click.stop>
          <div class="cart-popup-header">
            <h3>Ваша корзина</h3>
            <button class="close-btn" @click="showCart = false">×</button>
          </div>
          <div class="cart-popup-content">
            <div v-if="!cartItems.length" class="empty-cart-popup">
              <img :src="settings.cart_empty_icon" alt="Пусто" class="empty-cart-icon" @error="onIconError" />
              <p>Корзина пуста</p>
            </div>
            <div v-else class="cart-items-popup">
              <div v-for="group in groupedCart" :key="group.id" class="restaurant-group">
                <div class="restaurant-header">
                  <div class="restaurant-header-top">
                    <h4>{{ group.name }}</h4>
                    <button class="go-to-restaurant-btn" @click="goToRestaurant(group.id)" title="Перейти">→</button>
                  </div>
                </div>
                <div class="restaurant-items">
                  <div v-for="item in group.items" :key="item.id" class="cart-item-popup">
                    <div class="cart-item-info">
                      <div class="item-name">{{ item.name }}</div>
                      <div class="item-details">
                        <span>{{ item.quantity }} × {{ item.price }} ₽</span>
                        <span class="item-total">{{ item.price * item.quantity }} ₽</span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="restaurant-total">Итого: {{ group.total }} ₽</div>
              </div>
              <div class="cart-popup-summary">
                <p><strong>Общая сумма: {{ totalPrice }} ₽</strong></p>
                <button class="view-full-cart-btn" @click="goToCartPage">Оформить заказ</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script>
import { usePage, router } from '@inertiajs/vue3';
import { supabase, api } from '../../resources/js/supabase.js';


const RESTAURANTS = [
  { id: 1, title: 'KFC', rating: 4 }, { id: 2, title: 'Велопицца', rating: 5 },
  { id: 3, title: 'Вкусно и точка', rating: 3 }, { id: 4, title: 'На лаваше', rating: 4 },
  { id: 5, title: 'Pizzaiolo', rating: 4 }, { id: 6, title: 'Burger King', rating: 4 },
  { id: 7, title: 'Subway', rating: 4 }, { id: 8, title: 'Starbucks', rating: 5 },
  { id: 10, title: 'Теремок', rating: 4 }, { id: 11, title: 'Шаурма №1', rating: 3 },
  { id: 12, title: 'Dominos Pizza', rating: 5 }, { id: 13, title: 'Суши Wok', rating: 4 },
  { id: 14, title: 'Kebab House', rating: 4 }, { id: 15, title: 'Кофемания', rating: 5 },
  { id: 16, title: 'Чайхона №1', rating: 4 }, { id: 17, title: 'Папа Джонс', rating: 4 },
  { id: 18, title: 'Вареничная №1', rating: 5 }, { id: 19, title: 'Ванлав', rating: 4 },
  { id: 20, title: 'Арома', rating: 4 },
];

export default {
  name: 'HeaderComponent',
  data: () => ({
    searchQuery: '', searchLocation: '', showCart: false, showResults: false, filtered: [],
    user: null, userName: '',
    settings: { logo_icon: '', profile_icon: '', location_icon: '', search_icon: '', cart_empty_icon: '' },
  }),
  computed: {
    isOnHomePage() { return ['/', '/restaurans'].includes(usePage().url); },
    cartItems() {
      try { return JSON.parse(localStorage.getItem('shoppingCart') || '[]'); }
      catch { return []; }
    },
    groupedCart() {
      const groups = {};
      this.cartItems.forEach(item => {
        const id = item.restaurantId || item.restaurant_id;
        if (!groups[id]) {
          const r = RESTAURANTS.find(r => r.id == id);
          groups[id] = { id, name: r?.title || `Ресторан #${id}`, items: [], total: 0 };
        }
        groups[id].items.push(item);
        groups[id].total += item.price * item.quantity;
      });
      return Object.values(groups).sort((a, b) => a.name.localeCompare(b.name));
    },
    totalPrice() { return this.cartItems.reduce((t, i) => t + i.price * i.quantity, 0); },
  },
  methods: {
    async checkAuth() {
      try {
        const { data: { user } } = await supabase.auth.getUser();
        if (user) {
          this.user = user;
          this.userName = user.user_metadata?.full_name || user.email?.split('@')[0] || 'Профиль';
        }
      } catch { this.user = null; }
    },
    async loadSettings() {
      try {
        const { data } = await api.get('/site_settings', { params: { id: 'eq.1', select: '*' } });
        if (data?.[0]) {
          Object.keys(data[0]).forEach(key => {
            if (data[0][key]) this.settings[key] = data[0][key];
          });
        }
      } catch (err) { console.error('Ошибка загрузки иконок:', err); }
    },
    async searchRestaurants() {
      if (!this.searchQuery.trim()) { this.filtered = []; this.showResults = false; return; }
      try {
        const { data } = await api.get('/restaurants', {
          params: { select: '*', title: `ilike.*${this.searchQuery}*`, order: 'rating.desc', limit: 5 },
        });
        this.filtered = data || [];
      } catch {
        const q = this.searchQuery.toLowerCase();
        this.filtered = RESTAURANTS.filter(r => r.title.toLowerCase().includes(q)).slice(0, 5);
      }
      this.showResults = true;
    },
    hideResults() { setTimeout(() => this.showResults = false, 200); },
    goToRestaurant(id) { this.searchQuery = ''; this.showResults = false; router.visit(`/product/${id}`); },
    goToCartPage() { this.showCart = false; router.visit('/check'); },
    onIconError(e) {
      e.target.src = 'data:image/svg+xml,' + encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="50" height="45" fill="%23ff6b00"><rect width="50" height="45" rx="10"/><text x="25" y="28" text-anchor="middle" fill="white" font-size="18" font-weight="bold" font-family="sans-serif">FB</text></svg>'
      );
    },
  },
  mounted() {
    this.loadSettings();
    this.checkAuth();
    this._esc = e => { if (e.key === 'Escape') { this.showCart = false; this.showResults = false; } };
    document.addEventListener('keydown', this._esc);
  },
  beforeUnmount() { document.removeEventListener('keydown', this._esc); },
};
</script>

<style scoped>
@import "../assets/styles/header.css";

.search-container { position: relative; flex: 1; max-width: 400px; }
.search-input { width: 100%; padding: 12px 16px; border: 2px solid #eee; border-radius: 14px; font-size: 15px; transition: .2s; background: #fafafa; }
.search-input:focus { outline: none; border-color: #ff6b00; background: #fff; box-shadow: 0 0 0 4px rgba(255,107,0,0.06); }
.search-results { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 2px solid #eee; border-radius: 14px; margin-top: 6px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); z-index: 1000; max-height: 300px; overflow-y: auto; }
.search-result-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; cursor: pointer; transition: background .15s; border-bottom: 1px solid #f5f5f5; }
.search-result-item:last-child { border-bottom: none; }
.search-result-item:hover { background: #fff8f0; }
.restaurant-name { font-size: 15px; font-weight: 600; color: #1e1e1e; }
.restaurant-rating { font-size: 13px; color: #ff6b00; font-weight: 600; }
.search-no-results { padding: 16px; text-align: center; color: #999; font-size: 14px; }

/* Стиль кнопок как у корзины */
.header-profile-link {
  background: #fff8f0 !important;
  border-color: #ff6b00 !important;
  color: #ff6b00 !important;
}

.empty-cart-icon { width: 120px; height: 100px; opacity: .6; margin-bottom: 16px; object-fit: contain; }

.cart-popup-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; justify-content: flex-end; align-items: flex-start; z-index: 1000; padding-top: 80px; }
.cart-popup { background: #fff; border-radius: 20px; width: 420px; max-height: 600px; margin-right: 20px; box-shadow: 0 12px 40px rgba(0,0,0,0.2); display: flex; flex-direction: column; }
.cart-popup-header { display: flex; justify-content: space-between; align-items: center; padding: 18px 20px; border-bottom: 1px solid #f0f0f0; }
.cart-popup-header h3 { margin: 0; font-size: 18px; font-weight: 700; color: #1e1e1e; }
.close-btn { background: none; border: none; font-size: 24px; cursor: pointer; color: #aaa; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: .2s; }
.close-btn:hover { background: #f5f5f5; color: #333; }
.cart-popup-content { flex: 1; overflow-y: auto; }
.empty-cart-popup { padding: 40px 20px; text-align: center; color: #999; font-size: 16px; display: flex; flex-direction: column; align-items: center; }
.restaurant-group { margin-bottom: 12px; }
.restaurant-group:last-child { margin-bottom: 0; }
.restaurant-header { background: #fefaf5; padding: 12px 16px; }
.restaurant-header-top { display: flex; justify-content: space-between; align-items: center; }
.restaurant-header h4 { margin: 0; font-size: 15px; color: #1e1e1e; font-weight: 700; }
.go-to-restaurant-btn { background: #ff6b00; color: #fff; border: none; border-radius: 50%; width: 32px; height: 32px; font-size: 16px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: .2s; }
.go-to-restaurant-btn:hover { background: #e05e00; transform: translateX(2px); }
.restaurant-items { padding: 0 16px; }
.cart-item-popup { padding: 10px 0; border-bottom: 1px solid #f8f8f8; }
.cart-item-popup:last-child { border-bottom: none; }
.cart-item-info { display: flex; justify-content: space-between; align-items: flex-start; }
.item-name { font-weight: 600; color: #1e1e1e; flex: 1; padding-right: 10px; font-size: 14px; }
.item-details { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; min-width: 110px; }
.item-details span { font-size: 13px; color: #999; }
.item-total { font-weight: 700; color: #ff6b00; font-size: 14px; }
.restaurant-total { background: #fff8f0; padding: 8px 16px; font-size: 14px; color: #1e1e1e; font-weight: 600; text-align: right; }
.cart-popup-summary { padding: 16px; background: #fefaf5; text-align: center; margin-top: 8px; }
.cart-popup-summary p { margin: 0 0 12px; font-size: 18px; color: #1e1e1e; font-weight: 700; }
.view-full-cart-btn { background: #ff6b00; color: #fff; border: none; border-radius: 40px; padding: 12px 24px; cursor: pointer; font-size: 15px; font-weight: 700; width: 100%; transition: .2s; }
.view-full-cart-btn:hover { background: #e05e00; }
</style>