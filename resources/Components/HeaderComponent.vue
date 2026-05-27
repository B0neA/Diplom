<template>
  <div class="header-root">
  <header>
    <div class="headerBody">
      <div class="headerLeft">
        <a href="/"><img :src="logoSrc" alt="Logo" height="45" width="50" @error="onIconError"></a>
        
        <div class="search-container">
          <input class="search-input" v-model="searchQuery" placeholder="Поиск ресторанов..."
            @input="searchRestaurants" @focus="showResults = !!searchQuery" @blur="hideResults">
          <div v-if="showResults && filtered.length" class="search-results">
            <div v-for="r in filtered" :key="r.id" class="search-result-item" @mousedown="goToRestaurant(r.id)">
              <span class="restaurant-name">{{ r.title }}</span>
              <span class="restaurant-rating">★ {{ formatSearchRating(r) }}</span>
            </div>
          </div>
          <div v-if="showResults && searchQuery && !filtered.length" class="search-results">
            <div class="search-no-results">Рестораны не найдены</div>
          </div>
        </div>
      </div>
                  
      <nav class="headerNav">
  <a href="#" id="headerCart" class="header-nav-support" @click.prevent="showSupportModal = true">Поддержка</a>
  <a v-if="isOnHomePage" href="#" id="headerCart" @click.prevent="showCart = !showCart">Корзина</a>
  <a v-if="isUserAdmin" href="/admin" id="headerCart">Админ</a>
  <a v-if="user" href="/profile" id="headerCart">{{ userName }}</a>
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
              <div v-if="groupedCart.length > 0" class="cart-tabs-wrap">
                <p class="cart-tabs-hint">
                  {{ groupedCart.length > 1
                    ? 'Выберите: показать все рестораны или один'
                    : 'Состав корзины' }}
                </p>
                <div class="cart-tabs">
                  <button
                    v-if="groupedCart.length > 1"
                    type="button"
                    :class="['cart-tab', { active: activeCartTab === 'all' }]"
                    @click="setActiveCartTab('all')"
                  >
                    Все рестораны
                  </button>
                  <button
                    v-for="group in groupedCart"
                    :key="`tab-${group.id}`"
                    type="button"
                    :class="['cart-tab', { active: String(activeCartTab) === String(group.id) }]"
                    @click="setActiveCartTab(group.id)"
                  >
                    {{ group.name }}
                  </button>
                </div>
              </div>

              <div v-for="group in displayedCartGroups" :key="group.id" class="restaurant-group">
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
              <p v-if="activeCartTab !== 'all'" class="cart-scope-note">
                Показан заказ только из «{{ activeCartTabName }}»
              </p>
              <div class="cart-popup-summary">
                <div class="cart-summary-rows">
                  <div class="cart-summary-row">
                    <span>Товары</span>
                    <span>{{ selectedTotalPrice }} ₽</span>
                  </div>
                  <div class="cart-summary-row">
                    <span>Сервисный сбор</span>
                    <span>{{ quickOrderServiceFee }} ₽</span>
                  </div>
                  <div v-if="birthdayDiscountAmount > 0" class="cart-summary-row cart-summary-row--discount">
                    <span>Скидка на ДР (−10%)</span>
                    <span>−{{ birthdayDiscountAmount }} ₽</span>
                  </div>
                  <div class="cart-summary-row cart-summary-row--total">
                    <span>К оплате</span>
                    <strong>{{ quickOrderFinalTotal }} ₽</strong>
                  </div>
                </div>
                <p v-if="user && !profileComplete" class="quick-order-hint">
                  Для быстрого заказа заполните профиль: имя, телефон, адрес, карта, срок и CVC.
                  <a href="/profile" class="quick-order-profile-link" @click.prevent="goToProfile">Перейти в профиль</a>
                </p>
                <button
                  type="button"
                  class="quick-order-btn"
                  :class="{ 'quick-order-btn--disabled': !canQuickOrder }"
                  :disabled="!canQuickOrder"
                  @click="quickOrderSelected"
                >
                  Быстрый заказ выбранного
                </button>
                <button class="view-full-cart-btn" @click="goToCartPage">Оформить заказ</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <OrderSuccessModal
    :show="showOrderModal"
    :order-number="quickOrderNumber"
    :total="quickOrderTotal"
    :success-icon="settings.success_icon"
    @close="closeOrderModal"
    @go-home="goHomeAfterOrder"
  />

  <BirthdayDiscountModal :show="showBirthdayModal" @close="closeBirthdayModal" />

  <SupportModal
    :show="showSupportModal"
    :initial-name="supportInitialName"
    :initial-phone="supportInitialPhone"
    :user-id="user?.id || null"
    @close="showSupportModal = false"
  />
  </div>
</template>

<script>
import { usePage, router } from '@inertiajs/vue3';
import { supabase, getCurrentUser, loadProfile, isAdmin, waitForAuthInit, formatPhoneDisplay } from '../js/supabase.js';
import SupportModal from './SupportModal.vue';
import {
  getCart,
  saveCart,
  setActiveCartUser,
  syncCartWithAuth,
  clearCartForLogout,
  notifyCartChanged,
} from '../js/cart.js';
import { loadSiteSettings, applySiteSettings } from '../js/settingsCache.js';
import OrderSuccessModal from './OrderSuccessModal.vue';
import BirthdayDiscountModal from './BirthdayDiscountModal.vue';
import {
  calcBirthdayDiscount,
  isBirthdayToday,
  markBirthdayModalShown,
  shouldShowBirthdayModal,
} from '../js/birthdayDiscount.js';


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
  components: { OrderSuccessModal, BirthdayDiscountModal, SupportModal },
  data: () => ({
    searchQuery: '', showCart: false, showResults: false, filtered: [],
    user: null, userName: '', isUserAdmin: false,
    activeCartTab: 'all',
    cartVersion: 0,
    authListener: null,
    settings: { logo_icon: '', profile_icon: '', location_icon: '', search_icon: '', cart_empty_icon: '', success_icon: '' },
    showOrderModal: false,
    quickOrderNumber: '',
    quickOrderTotal: 0,
    userProfile: null,
    quickOrderServiceFee: 50,
    showBirthdayModal: false,
    showSupportModal: false,
  }),
  computed: {
    supportInitialName() {
      if (this.userProfile?.full_name) return this.userProfile.full_name;
      if (this.user?.user_metadata?.full_name) return this.user.user_metadata.full_name;
      return '';
    },
    supportInitialPhone() {
      const raw = this.userProfile?.phone || this.user?.user_metadata?.phone || '';
      return raw ? formatPhoneDisplay(raw) : '';
    },
    isOnHomePage() { return ['/', '/restaurans'].includes(usePage().url); },
    cartItems() {
      void this.cartVersion;
      return getCart();
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
    displayedCartGroups() {
      if (this.activeCartTab === 'all') return this.groupedCart;
      return this.groupedCart.filter(g => String(g.id) === String(this.activeCartTab));
    },
    activeCartItems() {
      if (this.activeCartTab === 'all') return this.cartItems;
      return this.cartItems.filter(
        i => String(i.restaurantId || i.restaurant_id) === String(this.activeCartTab)
      );
    },
    activeCartTabName() {
      if (this.activeCartTab === 'all') return 'Все рестораны';
      const group = this.groupedCart.find(g => String(g.id) === String(this.activeCartTab));
      return group?.name || '';
    },
    selectedTotalPrice() {
      return this.activeCartItems.reduce((t, i) => t + (Number(i.price) || 0) * (Number(i.quantity) || 1), 0);
    },
    quickOrderSubtotal() {
      return this.selectedTotalPrice + this.quickOrderServiceFee;
    },
    birthdayDiscountAmount() {
      if (!this.userProfile?.birth_date || !isBirthdayToday(this.userProfile.birth_date)) {
        return 0;
      }
      return calcBirthdayDiscount(this.selectedTotalPrice);
    },
    quickOrderFinalTotal() {
      return Math.max(0, this.quickOrderSubtotal - this.birthdayDiscountAmount);
    },
    profileComplete() {
      return this.profileIsComplete(this.userProfile);
    },
    canQuickOrder() {
      return Boolean(this.user) && this.profileComplete && this.activeCartItems.length > 0;
    },
    totalPrice() { return this.cartItems.reduce((t, i) => t + i.price * i.quantity, 0); },
    logoSrc() {
      return this.settings.logo_url || this.settings.logo_icon || '';
    },
  },
  methods: {
    formatSearchRating(r) {
      const val = Number(r?.rating);
      return val > 0 ? val.toFixed(1) : 'Нет отзывов';
    },
    bumpCart() {
      this.cartVersion += 1;
      this.syncCartTabState();
    },
    async applyAuthSession(session) {
      const user = session?.user ?? null;
      if (!user) {
        this.user = null;
        this.userName = '';
        this.isUserAdmin = false;
        this.userProfile = null;
        setActiveCartUser(null);
        this.bumpCart();
        return;
      }
      this.user = user;
      setActiveCartUser(user.id);
      try {
        const profile = await loadProfile(user.id);
        this.userProfile = profile;
        this.userName = profile?.full_name || user.user_metadata?.full_name || user.email?.split('@')[0] || 'Профиль';
        this.isUserAdmin = await isAdmin(user.id);
      } catch (err) {
        console.warn('Профиль не загружен:', err);
        this.userProfile = null;
        this.userName = user.user_metadata?.full_name || user.email?.split('@')[0] || 'Профиль';
        this.isUserAdmin = false;
      }
      this.bumpCart();
      this.tryShowBirthdayModal();
    },
    tryShowBirthdayModal() {
      if (!this.user?.id || !this.userProfile?.birth_date) return;
      if (!shouldShowBirthdayModal(this.user.id, this.userProfile.birth_date)) return;
      this.showBirthdayModal = true;
    },
    closeBirthdayModal() {
      this.showBirthdayModal = false;
      if (this.user?.id) markBirthdayModalShown(this.user.id);
    },
    async checkAuth() {
      const session = await waitForAuthInit();
      await this.applyAuthSession(session);
      await syncCartWithAuth();
    },
    async loadSettings() {
      try {
        const data = await loadSiteSettings();
        applySiteSettings(this.settings, data);
      } catch (err) { console.error('Ошибка загрузки иконок:', err); }
    },
    async searchRestaurants() {
      if (!this.searchQuery.trim()) { this.filtered = []; this.showResults = false; return; }
      try {
        const { data } = await window.axios.get('/api/restaurants');
        const q = this.searchQuery.trim().toLowerCase();
        this.filtered = (data || [])
          .filter(r => r.title?.toLowerCase().includes(q))
          .sort((a, b) => (Number(b.rating) || 0) - (Number(a.rating) || 0))
          .slice(0, 5);
      } catch {
        const q = this.searchQuery.toLowerCase();
        this.filtered = RESTAURANTS.filter(r => r.title.toLowerCase().includes(q)).slice(0, 5);
      }
      this.showResults = true;
    },
    hideResults() { setTimeout(() => this.showResults = false, 200); },
    goToRestaurant(id) { this.searchQuery = ''; this.showResults = false; router.visit(`/product/${id}`); },
    setActiveCartTab(tab) {
      this.activeCartTab = tab;
    },
    syncCartTabState() {
      if (!this.groupedCart.length) {
        this.activeCartTab = 'all';
        return;
      }
      if (this.groupedCart.length === 1) {
        this.activeCartTab = this.groupedCart[0].id;
        return;
      }
      if (this.activeCartTab !== 'all' && !this.groupedCart.find(g => String(g.id) === String(this.activeCartTab))) {
        this.activeCartTab = 'all';
      }
    },
    profileIsComplete(profile) {
      if (!profile) return false;
      const phone = String(profile.phone || '').replace(/\D/g, '');
      const card = String(profile.payment_card || '').replace(/\D/g, '');
      const cvc = String(profile.payment_cvc || '').replace(/\D/g, '');
      const expiry = String(profile.payment_expiry || '').trim();
      return Boolean(
        String(profile.full_name || '').trim() &&
        phone.length >= 11 &&
        String(profile.address || '').trim() &&
        card.length === 16 &&
        expiry.length >= 4 &&
        cvc.length === 3
      );
    },
    goToProfile() {
      this.showCart = false;
      router.visit('/profile');
    },
    async refreshUserProfile() {
      if (!this.user?.id) return;
      try {
        this.userProfile = await loadProfile(this.user.id);
      } catch {
        this.userProfile = null;
      }
    },
    async quickOrderSelected() {
      const user = await getCurrentUser();
      if (!user) {
        router.visit('/auth?redirect=/check');
        return;
      }
      const profile = await loadProfile(user.id);
      this.userProfile = profile;
      if (!this.profileIsComplete(profile)) {
        alert('Заполните профиль: имя, телефон, адрес, номер карты, срок и CVC.');
        this.goToProfile();
        return;
      }
      if (!this.activeCartItems.length) return;
      const orderTotal = this.quickOrderFinalTotal;
      try {
        const payload = {
          customerName: profile.full_name,
          customerPhone: String(profile.phone).replace(/\D/g, ''),
          deliveryAddress: profile.address,
          orderComment: this.activeCartTab === 'all' ? 'Быстрый заказ из корзины' : `Быстрый заказ из «${this.activeCartTabName}»`,
          total: orderTotal,
          items: this.activeCartItems,
          userId: user.id,
        };
        if (this.activeCartTab !== 'all') payload.restaurantId = this.activeCartTab;
        const { data } = await window.axios.post('/api/orders', payload);
        if (!data) throw new Error('Order failed');
        const created = Array.isArray(data) ? data[0] : data;
        if (this.activeCartTab === 'all') {
          saveCart([]);
        } else {
          const orderedIds = new Set(this.activeCartItems.map(i => i.id));
          const rest = this.cartItems.filter(i => !orderedIds.has(i.id));
          saveCart(rest);
        }
        notifyCartChanged();
        this.bumpCart();
        this.showCart = false;
        this.quickOrderNumber = created?.id ? String(created.id) : 'ORD' + Date.now().toString().slice(-8);
        const savedTotal = Number(created?.total_amount);
        this.quickOrderTotal = savedTotal > 0 ? savedTotal : orderTotal;
        this.showOrderModal = true;
      } catch {
        alert('Не удалось оформить быстрый заказ');
      }
    },
    closeOrderModal() {
      this.showOrderModal = false;
    },
    goHomeAfterOrder() {
      this.closeOrderModal();
      router.visit('/');
    },
    async goToCartPage() {
      this.showCart = false;
      const user = await getCurrentUser();
      if (!user) {
        router.visit('/auth?redirect=/check');
        return;
      }
      router.visit('/check');
    },
    onIconError(e) {
      e.target.src = 'data:image/svg+xml,' + encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="50" height="45" fill="%23ff6b00"><rect width="50" height="45" rx="10"/><text x="25" y="28" text-anchor="middle" fill="white" font-size="18" font-weight="bold" font-family="sans-serif">FB</text></svg>'
      );
    },
  },
  mounted() {
    this.loadSettings();
    this.checkAuth();
    this._onCartUpdate = () => this.bumpCart();
    window.addEventListener('cart-updated', this._onCartUpdate);
    this.authListener = supabase.auth.onAuthStateChange(async (event, session) => {
      if (event === 'SIGNED_OUT') {
        clearCartForLogout();
        this.user = null;
        this.userName = '';
        this.isUserAdmin = false;
        this.userProfile = null;
        this.bumpCart();
        return;
      }
      if (session?.user) {
        await this.applyAuthSession(session);
        if (event === 'SIGNED_IN') {
          await syncCartWithAuth();
        }
      }
    });
    this._esc = e => { if (e.key === 'Escape') { this.showCart = false; this.showResults = false; } };
    document.addEventListener('keydown', this._esc);
  },
  watch: {
    groupedCart() {
      this.syncCartTabState();
    },
    async showCart(open) {
      if (open && this.user) {
        await this.refreshUserProfile();
        this.tryShowBirthdayModal();
      }
    },
  },
  beforeUnmount() {
    document.removeEventListener('keydown', this._esc);
    window.removeEventListener('cart-updated', this._onCartUpdate);
    this.authListener?.data?.subscription?.unsubscribe?.();
  },
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

.empty-cart-icon { width: 120px; height: 100px; opacity: .6; margin-bottom: 16px; object-fit: contain; }

.cart-tabs-wrap { padding: 12px 16px 4px; border-bottom: 1px solid #f5f5f5; }
.cart-tabs-hint { margin: 0 0 10px; font-size: 13px; color: #888; }
.cart-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
.cart-tab {
  border: 2px solid #eee;
  background: #fafafa;
  border-radius: 30px;
  padding: 6px 12px;
  font-size: 13px;
  font-weight: 600;
  color: #666;
  cursor: pointer;
  transition: 0.2s;
}
.cart-tab:hover { border-color: #ff6b00; color: #ff6b00; }
.cart-tab.active { background: #ff6b00; border-color: #ff6b00; color: #fff; }

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
.cart-scope-note { margin: 8px 16px 0; font-size: 13px; color: #666; }
.cart-popup-summary { padding: 16px; background: #fefaf5; text-align: center; margin-top: 8px; }
.cart-summary-rows { text-align: left; margin-bottom: 12px; }
.cart-summary-row { display: flex; justify-content: space-between; align-items: center; font-size: 14px; color: #666; padding: 4px 0; }
.cart-summary-row--discount { color: #2e7d32; font-weight: 600; }
.cart-summary-row--total { margin-top: 6px; padding-top: 8px; border-top: 1px solid #f0e6dc; color: #1e1e1e; font-size: 16px; }
.cart-summary-row--total strong { color: #ff6b00; font-size: 18px; }
.quick-order-hint { font-size: 12px; color: #888; line-height: 1.45; margin: 0 0 10px; text-align: left; }
.quick-order-profile-link { color: #ff6b00; font-weight: 600; text-decoration: none; display: inline-block; margin-top: 4px; }
.quick-order-profile-link:hover { text-decoration: underline; }
.view-full-cart-btn { background: #ff6b00; color: #fff; border: none; border-radius: 40px; padding: 12px 24px; cursor: pointer; font-size: 15px; font-weight: 700; width: 100%; transition: .2s; }
.view-full-cart-btn:hover { background: #e05e00; }
.quick-order-btn { background: #fff; color: #ff6b00; border: 2px solid #ff6b00; border-radius: 40px; padding: 10px 20px; cursor: pointer; font-size: 14px; font-weight: 700; width: 100%; margin-bottom: 8px; transition: .2s; }
.quick-order-btn:hover:not(:disabled) { background: #fff8f0; }
.quick-order-btn--disabled,
.quick-order-btn:disabled { opacity: 0.45; cursor: not-allowed; border-color: #ddd; color: #aaa; background: #f5f5f5; }
</style>