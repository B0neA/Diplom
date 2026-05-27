<template>
  <div class="checkout-page">
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
    </Head>
    <HeaderComponent />
    <div class="container">
      <div class="checkout-layout">
        <!-- Левая колонка - Состав заказа -->
        <div class="order-details-section">
          <h1 class="restaurant-title">Оформление заказа</h1>

          <div v-if="groupedCartItems.length > 0" class="checkout-tabs-wrap">
            <p class="checkout-tabs-hint">
              {{ groupedCartItems.length > 1
                ? 'Оформите заказ из всех ресторанов сразу или выберите один ресторан'
                : 'Состав заказа' }}
            </p>
            <div class="checkout-tabs">
              <button
                v-if="groupedCartItems.length > 1"
                type="button"
                :class="['checkout-tab', { active: activeTab === 'all' }]"
                @click="setActiveTab('all')"
              >
                Все рестораны
              </button>
              <button
                v-for="group in groupedCartItems"
                :key="group.id"
                type="button"
                :class="['checkout-tab', { active: String(activeTab) === String(group.id) }]"
                @click="setActiveTab(group.id)"
              >
                {{ group.name }}
              </button>
            </div>
          </div>
          
          <div v-if="displayedGroups.length > 0">
            <div v-for="group in displayedGroups" :key="group.id" class="restaurant-order-group">
              <h3 v-if="activeTab === 'all'" class="restaurant-name">{{ group.name }}</h3>
              <div class="restaurant-items">
                <div v-for="item in group.items" :key="item.id" class="order-item-detail">
                  <div class="item-info">
                    <span class="item-name">{{ item.name }}</span>
                    <span class="item-description">{{ item.description }}</span>
                  </div>
                  <div class="item-quantity-price">
                    <span class="item-quantity">{{ item.quantity }} × {{ item.price }} ₽</span>
                    <span class="item-total">{{ item.price * item.quantity }} ₽</span>
                  </div>
                </div>
              </div>
              <div class="restaurant-total">Итого по ресторану: {{ group.total }} ₽</div>
            </div>
            <p v-if="activeTab !== 'all'" class="scope-note">Оформляется заказ только из «{{ activeTabName }}»</p>
          </div>
          
          <div v-else class="empty-cart">
            <p>Корзина пуста</p>
            <a href="/restaurans" class="back-to-menu">Вернуться к меню</a>
          </div>
        </div>

        <!-- Правая колонка - Форма оплаты -->
        <div class="payment-form-section">
          <div class="order-summary-card">
            <h2 class="summary-title">Оплата</h2>
            
            <div class="payment-section">
              <h3 class="payment-title">Способ оплаты</h3>
              <p class="payment-hint">Данные карты подставляются из личного кабинета, их можно изменить</p>
              <div class="card-input-group">
                <div class="card-input-wrapper">
                  <img 
                    :src="settings.card_icon" 
                    alt="Card" 
                    class="card-icon" 
                    @error="onIconError($event, 'card')" 
                  />
                  <input 
                    type="text" 
                    class="card-input" 
                    v-model="cardNumber" 
                    placeholder="Номер карты" 
                    maxlength="19" 
                    @input="formatCardNumber"
                  />
                </div>
                <div class="card-details">
                  <input 
                    type="text" 
                    class="card-expiry" 
                    v-model="cardExpiry" 
                    placeholder="ММ/ГГ" 
                    maxlength="5" 
                    @input="formatExpiry"
                  />
                  <input 
                    type="password" 
                    class="card-cvc" 
                    v-model="cardCvc" 
                    placeholder="CVC" 
                    maxlength="3"
                  />
                </div>
              </div>
            </div>

            <div class="summary-divider"></div>

            <div class="contact-section">
              <h3 class="contact-title">Контактные данные</h3>
              <div class="form-group">
                <label class="form-label">Имя</label>
                <input type="text" class="form-input" v-model="customerName" placeholder="Ваше имя" />
              </div>
              <div class="form-group">
                <label class="form-label form-label--with-icon">
                  <img v-if="settings.phone_icon" :src="settings.phone_icon" alt="" class="label-icon" @error="onFieldIconError" />
                  Телефон
                </label>
                <input
                  type="text"
                  class="form-input"
                  v-model="customerPhone"
                  placeholder="+7 (___) ___-__-__"
                  @input="formatPhone"
                />
              </div>
              <div class="form-group">
                <label class="form-label form-label--with-icon">
                  <img v-if="settings.location_icon" :src="settings.location_icon" alt="" class="label-icon" @error="onFieldIconError" />
                  Адрес доставки
                </label>
                <input
                  type="text"
                  class="form-input"
                  v-model="deliveryAddress"
                  placeholder="Улица, дом, квартира"
                />
              </div>
              <div class="form-group">
                <label class="form-label">Комментарий к заказу</label>
                <textarea class="form-textarea" v-model="orderComment" placeholder="Дополнительные пожелания..." rows="3"></textarea>
              </div>
            </div>

            <div class="summary-divider"></div>

            <div class="price-breakdown">
              <h3 class="price-title">Состав счета</h3>
              <div class="price-row">
                <span class="price-label">Товары</span>
                <span class="price-value">{{ cartTotal }} ₽</span>
              </div>
              <div class="price-row">
                <span class="price-label">Доставка</span>
                <span class="price-value" :class="{ 'price-value--free': deliveryFee === 0 }">
                  {{ formatDeliveryFee(deliveryFee) }}
                </span>
              </div>
              <p v-if="deliveryHint" class="delivery-hint">{{ deliveryHint }}</p>
              <div class="price-row">
                <span class="price-label">Сервисный сбор</span>
                <span class="price-value">{{ serviceFee }} ₽</span>
              </div>
              <div class="promo-section">
                <input type="text" class="promo-input" v-model="promoCode" placeholder="Промокод" />
                <button class="apply-promo-button" @click="applyPromo">Применить</button>
              </div>
              <div v-if="birthdayDiscount > 0" class="discount-row discount-row--birthday">
                <span class="discount-label">Скидка в честь дня рождения (−10%)</span>
                <span class="discount-value">-{{ birthdayDiscount }} ₽</span>
              </div>
              <div v-if="promoDiscount > 0" class="discount-row">
                <span class="discount-label">Промокод</span>
                <span class="discount-value">-{{ promoDiscount }} ₽</span>
              </div>
            </div>

            <div class="summary-divider"></div>

            <div class="final-summary">
              <div class="total-row">
                <span class="total-label">К оплате</span>
                <span class="total-value">{{ finalTotal }} ₽</span>
              </div>
              <div class="action-buttons">
                <button class="clear-cart-button" @click="clearCartAndRedirect">Очистить корзину</button>
                <button class="pay-button" @click="submitOrder" :disabled="!isFormValid || submitting || !authChecked || !activeItems.length">
                  {{ submitting ? 'Оформление...' : `Оплатить ${finalTotal} ₽` }}
                </button>
              </div>
              <p class="agreement-text">
                Нажимая на кнопку, вы принимаете условия
                <a href="#" class="agreement-link">оферты</a>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <OrderSuccessModal
      :show="showOrderModal"
      :order-number="orderNumber"
      :total="finalTotal"
      :success-icon="settings.success_icon"
      @close="closeModal"
      @go-home="goToHome"
    />

    <BirthdayDiscountModal :show="showBirthdayModal" @close="closeBirthdayModal" />
  </div>
</template>

<script>
import { router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import HeaderComponent from '../../Components/HeaderComponent.vue';
import { usePageSeo } from '../usePageSeo.js';
import OrderSuccessModal from '../../Components/OrderSuccessModal.vue';
import BirthdayDiscountModal from '../../Components/BirthdayDiscountModal.vue';
import { loadSiteSettings, applySiteSettings } from '../settingsCache.js';
import {
  calcBirthdayDiscount,
  isBirthdayToday,
  markBirthdayModalShown,
  shouldShowBirthdayModal,
} from '../birthdayDiscount.js';
import {
  api,
  getCurrentUser,
  getCurrentSession,
  loadProfile,
  formatPhoneInput,
  formatPhoneDisplay,
} from '../supabase.js';
import { getCart, saveCart, notifyCartChanged, syncCartWithAuth } from '../cart.js';
import {
  calcDeliveryFee,
  amountUntilFreeDelivery,
  formatDeliveryFee,
  ORDER_SERVICE_FEE,
} from '../deliveryFee.js';

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
  name: 'CheckoutPage',
  components: { HeaderComponent, Head, OrderSuccessModal, BirthdayDiscountModal },
  setup() {
    const seo = usePageSeo(
      'Оформление заказа — Лопать Подано',
      'Укажите адрес и способ оплаты, примените промокод и подтвердите доставку еды из выбранных ресторанов.'
    );
    return { seo };
  },
  data() {
    return {
      cartItems: [],
      restaurants: RESTAURANTS,
      customerName: '',
      customerPhone: '',
      deliveryAddress: '',
      orderComment: '',
      cardNumber: '',
      cardExpiry: '',
      cardCvc: '',
      promoCode: '',
      promoDiscount: 0,
      userBirthDate: null,
      showBirthdayModal: false,
      showOrderModal: false,
      orderNumber: '',
      authChecked: false,
      userId: null,
      submitting: false,
      activeTab: 'all',
      settings: {
        card_icon: '',
        success_icon: '',
        location_icon: '',
        phone_icon: '',
      },
    };
  },

  computed: {
    groupedCartItems() {
      const groups = {};
      this.cartItems.forEach(item => {
        const id = item.restaurantId || item.restaurant_id;
        if (!groups[id]) {
          const r = this.restaurants.find(r => r.id == id);
          groups[id] = {
            id,
            name: r ? r.title : `Ресторан #${id}`,
            items: [],
            total: 0,
          };
        }
        groups[id].items.push(item);
        groups[id].total += item.price * item.quantity;
      });
      return Object.values(groups);
    },

    displayedGroups() {
      if (this.activeTab === 'all') return this.groupedCartItems;
      return this.groupedCartItems.filter(g => String(g.id) === String(this.activeTab));
    },

    activeTabName() {
      if (this.activeTab === 'all') return 'Все рестораны';
      const g = this.groupedCartItems.find(x => String(x.id) === String(this.activeTab));
      return g?.name || '';
    },

    activeItems() {
      if (this.activeTab === 'all') return this.cartItems;
      return this.cartItems.filter(
        i => String(i.restaurantId || i.restaurant_id) === String(this.activeTab)
      );
    },

    cartTotal() {
      return this.activeItems.reduce((t, i) => t + i.price * i.quantity, 0);
    },

    deliveryFee() {
      return calcDeliveryFee(this.cartTotal);
    },
    deliveryHint() {
      const left = amountUntilFreeDelivery(this.cartTotal);
      if (left <= 0) return '';
      return `Добавьте блюд ещё на ${left} ₽ — доставка будет бесплатной`;
    },
    serviceFee() {
      return ORDER_SERVICE_FEE;
    },
    birthdayDiscount() {
      if (!isBirthdayToday(this.userBirthDate)) return 0;
      return calcBirthdayDiscount(this.cartTotal);
    },
    finalTotal() {
      return Math.max(
        0,
        this.cartTotal + this.deliveryFee + this.serviceFee - this.birthdayDiscount - this.promoDiscount
      );
    },

    isFormValid() {
      return (
        this.customerName.trim() &&
        this.customerPhone.replace(/\D/g, '').length >= 11 &&
        this.deliveryAddress.trim() &&
        this.cardNumber.replace(/\D/g, '').length === 16 &&
        this.cardExpiry.length === 5 &&
        this.cardCvc.length === 3
      );
    },
  },

  methods: {
    formatDeliveryFee,
    loadCart() {
      this.cartItems = [...getCart()];
    },

    async loadRestaurantsFromApi() {
      try {
        const { data } = await window.axios.get('/api/restaurants');
        if (data?.length) this.restaurants = data;
      } catch {
        /* fallback RESTAURANTS */
      }
    },

    formatCardNumber() {
      const n = this.cardNumber.replace(/\D/g, '').slice(0, 16);
      this.cardNumber = Array.from({ length: Math.ceil(n.length / 4) }, (_, i) =>
        n.slice(i * 4, i * 4 + 4)
      ).join(' ');
    },

    formatExpiry() {
      const n = this.cardExpiry.replace(/\D/g, '');
      this.cardExpiry = n.length >= 2 ? n.slice(0, 2) + '/' + n.slice(2, 4) : n;
    },

    formatPhone() {
      this.customerPhone = formatPhoneInput(this.customerPhone);
    },

    setActiveTab(tab) {
      this.activeTab = tab;
      this.promoDiscount = 0;
      this.promoCode = '';
    },

    initCheckoutTab() {
      const groups = this.groupedCartItems;
      if (!groups.length) {
        this.activeTab = 'all';
        return;
      }
      if (groups.length === 1) {
        this.activeTab = groups[0].id;
        return;
      }
      if (this.activeTab === 'all') return;
      if (!groups.find(g => String(g.id) === String(this.activeTab))) {
        this.activeTab = 'all';
      }
    },

    async checkAuthAndLoadProfile() {
      const session = await getCurrentSession();
      const user = session?.user ?? (await getCurrentUser());
      if (!user) {
        window.location.href = '/auth?redirect=/check';
        return;
      }
      this.userId = user.id;
      this.authChecked = true;
      const profile = await loadProfile(user.id);
      if (profile) {
        this.customerName = profile.full_name || user.user_metadata?.full_name || '';
        this.customerPhone = formatPhoneDisplay(profile.phone || user.user_metadata?.phone);
        this.deliveryAddress = profile.address || '';
        if (profile.payment_card) {
          const digits = String(profile.payment_card).replace(/\D/g, '').slice(0, 16);
          this.cardNumber = Array.from({ length: Math.ceil(digits.length / 4) }, (_, i) =>
            digits.slice(i * 4, i * 4 + 4)
          ).join(' ');
        }
        if (profile.payment_expiry) {
          this.cardExpiry = profile.payment_expiry;
        }
        if (profile.payment_cvc) {
          this.cardCvc = String(profile.payment_cvc).replace(/\D/g, '').slice(0, 3);
        }
        this.userBirthDate = profile.birth_date || null;
        this.tryShowBirthdayModal();
      } else {
        this.customerName = user.user_metadata?.full_name || '';
        this.customerPhone = formatPhoneDisplay(user.user_metadata?.phone);
      }
    },

    tryShowBirthdayModal() {
      if (!this.userId || !this.userBirthDate) return;
      if (!shouldShowBirthdayModal(this.userId, this.userBirthDate)) return;
      this.showBirthdayModal = true;
    },
    closeBirthdayModal() {
      this.showBirthdayModal = false;
      if (this.userId) markBirthdayModalShown(this.userId);
    },
    async applyPromo() {
      const code = this.promoCode.trim();
      if (!code) return;
      try {
        const { data } = await window.axios.post('/api/promo/validate', {
          code,
          cartTotal: this.cartTotal,
          userId: this.userId || null,
        });
        this.promoDiscount = Number(data.discount) || 0;
        alert(data.message || 'Промокод применён');
      } catch (e) {
        this.promoDiscount = 0;
        alert(e?.response?.data?.error || 'Промокод не найден');
      }
    },

    clearCartAndRedirect() {
      if (confirm('Очистить корзину?')) {
        saveCart([]);
        notifyCartChanged();
        this.cartItems = [];
        router.visit('/restaurans');
      }
    },

    async submitOrder() {
      if (!this.isFormValid) {
        alert('Заполните все поля');
        return;
      }
      if (!this.activeItems.length) {
        alert('Нет позиций для оформления');
        return;
      }
      this.submitting = true;
      const scopeComment = this.activeTab === 'all'
        ? this.orderComment
        : `[${this.activeTabName}] ${this.orderComment}`.trim();
      try {
        const payload = {
          customerName: this.customerName,
          customerPhone: this.customerPhone.replace(/\D/g, ''),
          deliveryAddress: this.deliveryAddress,
          orderComment: scopeComment,
          total: this.finalTotal,
          items: this.activeItems,
          userId: this.userId,
        };
        if (this.activeTab !== 'all') {
          payload.restaurantId = this.activeTab;
        }
        const { data } = await window.axios.post('/api/orders', payload);
        const created = Array.isArray(data) ? data[0] : data;
        this.orderNumber = created?.id ? String(created.id) : 'ORD' + Date.now().toString().slice(-8);
        this.showOrderModal = true;
        if (this.activeTab === 'all') {
          saveCart([]);
          this.cartItems = [];
        } else {
          const orderedIds = new Set(this.activeItems.map(i => i.id));
          this.cartItems = this.cartItems.filter(i => !orderedIds.has(i.id));
          saveCart(this.cartItems);
        }
        notifyCartChanged();
      } catch (e) {
        alert('Не удалось оформить заказ. Попробуйте снова.');
        console.error(e);
      } finally {
        this.submitting = false;
      }
    },

    closeModal() {
      this.showOrderModal = false;
    },

    goToHome() {
      this.closeModal();
      router.visit('/');
    },

    async loadSettings() {
      try {
        const data = await loadSiteSettings();
        applySiteSettings(this.settings, data);
      } catch (err) {
        console.error('Ошибка загрузки иконок:', err);
      }
    },

    onIconError(e, type) {
      const fallbacks = {
        card: 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><rect x="2" y="4" width="20" height="16" rx="2" stroke="%23999" stroke-width="2"/><line x1="2" y1="10" x2="22" y2="10" stroke="%23999" stroke-width="2"/></svg>'),
      };
      e.target.src = fallbacks[type] || '';
    },

    onFieldIconError(e) {
      e.target.style.display = 'none';
    },
  },

  watch: {
    groupedCartItems() {
      this.initCheckoutTab();
    },
  },

  async mounted() {
    await this.checkAuthAndLoadProfile();
    await syncCartWithAuth();
    await this.loadRestaurantsFromApi();
    this.loadCart();
    this.initCheckoutTab();
    this.loadSettings();
  },
};
</script>

<style scoped>
/* Общие */
.checkout-page {
  background: #fefaf5;
  min-height: 100vh;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  padding: 2rem 0 4rem;
}
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}
.checkout-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 30px;
  margin-top: 20px;
}

/* Карточки */
.order-details-section,
.order-summary-card {
  background: #fff;
  border-radius: 20px;
  padding: 28px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
}
.restaurant-title,
.summary-title {
  font-size: 24px;
  font-weight: 700;
  color: #1e1e1e;
  margin: 0 0 20px;
  padding-bottom: 15px;
  border-bottom: 2px solid #f0f0f0;
}

.checkout-tabs-wrap {
  margin-bottom: 20px;
}

.checkout-tabs-hint {
  font-size: 14px;
  color: #888;
  margin: 0 0 12px;
}

.checkout-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.checkout-tab {
  padding: 10px 18px;
  border: 2px solid #eee;
  border-radius: 40px;
  background: #fafafa;
  font-size: 14px;
  font-weight: 600;
  color: #666;
  cursor: pointer;
  transition: 0.2s;
}

.checkout-tab:hover {
  border-color: #ff6b00;
  color: #ff6b00;
}

.checkout-tab.active {
  background: #ff6b00;
  border-color: #ff6b00;
  color: #fff;
}

.scope-note {
  margin-top: 12px;
  padding: 10px 14px;
  background: #fff8f0;
  border-radius: 12px;
  font-size: 14px;
  color: #e65100;
  font-weight: 500;
}

/* Пустая корзина */
.empty-cart {
  text-align: center;
  padding: 60px 20px;
}
.empty-cart p {
  font-size: 18px;
  color: #666;
  margin-bottom: 20px;
}
.back-to-menu {
  display: inline-block;
  padding: 12px 28px;
  background: #ff6b00;
  color: #fff;
  text-decoration: none;
  border-radius: 40px;
  font-weight: 600;
  transition: 0.2s;
}
.back-to-menu:hover {
  background: #e05e00;
  transform: scale(1.02);
}

/* Группы ресторанов */
.restaurant-order-group {
  margin-bottom: 25px;
  padding-bottom: 20px;
  border-bottom: 1px solid #f0f0f0;
}
.restaurant-order-group:last-child {
  border-bottom: none;
  margin-bottom: 0;
}
.restaurant-name {
  font-size: 18px;
  font-weight: 600;
  color: #1e1e1e;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid #f5f5f5;
}

/* Позиции заказа */
.order-item-detail {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 12px 0;
  border-bottom: 1px solid #fafafa;
}
.order-item-detail:last-child {
  border-bottom: none;
}
.item-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.item-name {
  font-size: 16px;
  font-weight: 600;
  color: #1e1e1e;
}
.item-description {
  font-size: 14px;
  color: #888;
}
.item-quantity-price {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
  min-width: 120px;
}
.item-quantity {
  font-size: 14px;
  color: #999;
}
.item-total {
  font-size: 16px;
  font-weight: 700;
  color: #ff6b00;
}
.restaurant-total {
  text-align: right;
  font-size: 16px;
  font-weight: 600;
  color: #1e1e1e;
  padding-top: 10px;
  border-top: 1px solid #f0f0f0;
  margin-top: 4px;
}

/* Разделители */
.summary-divider {
  height: 1px;
  background: #f0f0f0;
  margin: 20px 0;
}

/* Заголовки секций */
.payment-section,
.contact-section,
.price-breakdown {
  margin: 20px 0;
}
.payment-title,
.contact-title,
.price-title {
  font-size: 17px;
  font-weight: 600;
  color: #1e1e1e;
  margin-bottom: 15px;
}

.payment-hint {
  font-size: 13px;
  color: #888;
  margin: -8px 0 12px;
}

/* Поле карты */
.card-input-group {
  display: flex;
  flex-direction: column;
  gap: 15px;
}
.card-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}
.card-icon {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  width: 24px;
  height: 24px;
  z-index: 1;
  object-fit: contain;
  opacity: 0.7;
}
.card-input {
  width: 100%;
  padding: 14px 16px 14px 48px;
  border: 2px solid #eee;
  border-radius: 14px;
  font-size: 16px;
  letter-spacing: 1px;
  background: #fafafa;
  transition: 0.2s;
  box-sizing: border-box;
}
.card-details {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
}
.card-expiry,
.card-cvc {
  width: 100%;
  padding: 14px 16px;
  border: 2px solid #eee;
  border-radius: 14px;
  font-size: 16px;
  background: #fafafa;
  transition: 0.2s;
  box-sizing: border-box;
}
.card-input:focus,
.card-expiry:focus,
.card-cvc:focus,
.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #ff6b00;
  background: #fff;
  box-shadow: 0 0 0 4px rgba(255, 107, 0, 0.06);
}

/* Форма */
.form-group {
  margin-bottom: 15px;
}
.form-label {
  display: block;
  font-size: 14px;
  color: #888;
  margin-bottom: 6px;
  font-weight: 500;
}
.form-label--with-icon {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.label-icon {
  width: 18px;
  height: 18px;
  object-fit: contain;
}
.input-with-icon {
  position: relative;
}
.field-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  width: 18px;
  height: 18px;
  object-fit: contain;
  pointer-events: none;
  opacity: 0.75;
  z-index: 1;
}
.form-input--with-icon {
  padding-left: 44px;
}
.form-input,
.form-textarea {
  width: 100%;
  padding: 14px 16px;
  border: 2px solid #eee;
  border-radius: 14px;
  font-size: 16px;
  background: #fafafa;
  transition: 0.2s;
  box-sizing: border-box;
}
.form-textarea {
  resize: vertical;
  min-height: 80px;
}

/* Счёт */
.price-row,
.discount-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
}
.price-label,
.discount-label {
  font-size: 15px;
  color: #888;
}
.delivery-hint {
  font-size: 12px;
  color: #ff6b00;
  margin: -4px 0 10px;
  line-height: 1.4;
}
.price-value--free {
  color: #2e7d32;
  font-weight: 600;
}
.price-value,
.discount-value {
  font-size: 15px;
  color: #1e1e1e;
  font-weight: 600;
}
.discount-row {
  color: #ff6b00;
}

/* Промокод */
.promo-section {
  display: flex;
  gap: 10px;
  margin-top: 15px;
}
.promo-input {
  flex: 1;
  padding: 12px 16px;
  border: 2px solid #eee;
  border-radius: 14px;
  font-size: 14px;
  background: #fafafa;
}
.apply-promo-button {
  padding: 12px 24px;
  background: #fff;
  border: 2px solid #eee;
  border-radius: 14px;
  font-size: 14px;
  color: #1e1e1e;
  cursor: pointer;
  font-weight: 600;
  transition: 0.2s;
}
.apply-promo-button:hover {
  background: #f5f5f5;
  border-color: #ddd;
}

/* Итого */
.final-summary {
  text-align: center;
}
.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding: 15px 0;
  border-top: 2px solid #f0f0f0;
}
.total-label {
  font-size: 18px;
  font-weight: 600;
  color: #1e1e1e;
}
.total-value {
  font-size: 28px;
  font-weight: 800;
  color: #ff6b00;
}

/* Кнопки действий */
.action-buttons {
  display: flex;
  gap: 12px;
  margin-bottom: 15px;
}
.clear-cart-button {
  flex: 1;
  padding: 15px;
  background: #fff;
  border: 2px solid #eee;
  border-radius: 14px;
  font-size: 15px;
  color: #888;
  cursor: pointer;
  font-weight: 600;
  transition: 0.2s;
}
.clear-cart-button:hover {
  border-color: #e74c3c;
  color: #e74c3c;
  background: #fff5f5;
}
.pay-button {
  flex: 2;
  padding: 15px;
  background: #ff6b00;
  border: none;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 700;
  color: #fff;
  cursor: pointer;
  transition: 0.2s;
}
.pay-button:hover:not(:disabled) {
  background: #e05e00;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(255, 107, 0, 0.3);
}
.pay-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Соглашение */
.agreement-text {
  font-size: 12px;
  color: #aaa;
  margin-top: 10px;
}
.agreement-link {
  color: #ff6b00;
  text-decoration: none;
}
.agreement-link:hover {
  text-decoration: underline;
}

@media (max-width: 768px) {
  .checkout-layout {
    grid-template-columns: 1fr;
  }
}
</style>