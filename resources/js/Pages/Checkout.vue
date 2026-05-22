<template>
  <div class="checkout-page">
    <HeaderComponent />
    <div class="container">
      <div class="checkout-layout">
        <!-- Левая колонка - Состав заказа -->
        <div class="order-details-section">
          <h1 class="restaurant-title">Оформление заказа</h1>
          
          <div v-if="groupedCartItems.length > 0">
            <div v-for="group in groupedCartItems" :key="group.id" class="restaurant-order-group">
              <h3 class="restaurant-name">{{ group.name }}</h3>
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
                <label class="form-label">Телефон</label>
                <input type="text" class="form-input" v-model="customerPhone" placeholder="+7 (___) ___-__-__" @input="formatPhone" />
              </div>
              <div class="form-group">
                <label class="form-label">Адрес доставки</label>
                <input type="text" class="form-input" v-model="deliveryAddress" placeholder="Улица, дом, квартира" />
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
                <span class="price-value">0 ₽</span>
              </div>
              <div class="price-row">
                <span class="price-label">Сервисный сбор</span>
                <span class="price-value">{{ serviceFee }} ₽</span>
              </div>
              <div class="promo-section">
                <input type="text" class="promo-input" v-model="promoCode" placeholder="Промокод" />
                <button class="apply-promo-button" @click="applyPromo">Применить</button>
              </div>
              <div v-if="discount > 0" class="discount-row">
                <span class="discount-label">Скидка</span>
                <span class="discount-value">-{{ discount }} ₽</span>
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
                <button class="pay-button" @click="submitOrder" :disabled="!isFormValid">
                  Оплатить {{ finalTotal }} ₽
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

    <!-- Модальное окно -->
    <div v-if="showOrderModal" class="order-modal-overlay" @click="closeModal">
      <div class="order-modal" @click.stop>
        <div class="modal-header">
          <h3>Заказ оформлен!</h3>
          <button class="close-modal-btn" @click="closeModal">×</button>
        </div>
        <div class="modal-content">
          <img 
            :src="settings.success_icon" 
            alt="Success" 
            class="success-icon" 
            @error="onIconError($event, 'success')" 
          />
          <p class="modal-message">Ваш заказ успешно оформлен и ожидает подтверждения</p>
          <div class="order-info">
            <div class="info-row">
              <span class="info-label">Номер заказа:</span>
              <span class="info-value">#{{ orderNumber }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Сумма:</span>
              <span class="info-value">{{ finalTotal }} ₽</span>
            </div>
            <div class="info-row">
              <span class="info-label">Статус:</span>
              <span class="status-badge">Ожидает подтверждения</span>
            </div>
          </div>
          <p class="modal-note">Мы свяжемся с вами для подтверждения заказа в течение 15 минут</p>
          <button class="back-to-home-button" @click="goToHome">Вернуться на главную</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { router } from '@inertiajs/vue3';
import HeaderComponent from '../../Components/HeaderComponent.vue';
import axios from 'axios';

const api = axios.create({
  baseURL: 'https://cuibxmcjdkgjffmmzwgd.supabase.co/rest/v1',
  headers: {
    apikey: 'sb_publishable_5BPLZkbZvQXw7ZfrMWufFA_K77_nZxh',
    Authorization: 'Bearer sb_publishable_5BPLZkbZvQXw7ZfrMWufFA_K77_nZxh',
  },
});

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
  components: { HeaderComponent },
  
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
      discount: 0,
      serviceFee: 50,
      showOrderModal: false,
      orderNumber: '',
      settings: {
        card_icon: '',
        success_icon: '',
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

    cartTotal() {
      return this.cartItems.reduce((t, i) => t + i.price * i.quantity, 0);
    },

    finalTotal() {
      return Math.max(0, this.cartTotal + this.serviceFee - this.discount);
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
    loadCart() {
      try {
        this.cartItems = JSON.parse(localStorage.getItem('shoppingCart') || '[]');
      } catch {
        this.cartItems = [];
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
      let n = this.customerPhone.replace(/\D/g, '').slice(0, 11);
      if (n.startsWith('8')) n = '7' + n.slice(1);
      if (n.startsWith('9') && n.length === 10) n = '7' + n;
      this.customerPhone = n.length
        ? `+7 (${n.slice(1, 4)}) ${n.slice(4, 7)}-${n.slice(7, 9)}-${n.slice(9, 11)}`
        : '';
    },

    applyPromo() {
      const code = this.promoCode.toUpperCase();
      if (code === 'PIZZA20') {
        this.discount = Math.floor(this.cartTotal * 0.2);
        alert('Скидка 20%!');
      } else if (code === 'FREE50') {
        this.discount = 50;
        alert('Скидка 50₽!');
      } else if (code) {
        alert('Промокод не найден');
      }
    },

    clearCartAndRedirect() {
      if (confirm('Очистить корзину?')) {
        localStorage.removeItem('shoppingCart');
        this.cartItems = [];
        router.visit('/restaurans');
      }
    },

    submitOrder() {
      if (!this.isFormValid) {
        alert('Заполните все поля');
        return;
      }
      this.orderNumber = 'ORD' + Date.now().toString().slice(-8);
      this.showOrderModal = true;
      localStorage.removeItem('shoppingCart');
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
        const { data } = await api.get('/site_settings', {
          params: { id: 'eq.1', select: 'card_icon,success_icon' },
        });
        if (data?.[0]) {
          if (data[0].card_icon) this.settings.card_icon = data[0].card_icon;
          if (data[0].success_icon) this.settings.success_icon = data[0].success_icon;
        }
      } catch (err) {
        console.error('Ошибка загрузки иконок:', err);
      }
    },

    onIconError(e, type) {
      const fallbacks = {
        card: 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><rect x="2" y="4" width="20" height="16" rx="2" stroke="%23999" stroke-width="2"/><line x1="2" y1="10" x2="22" y2="10" stroke="%23999" stroke-width="2"/></svg>'),
        success: 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64" fill="none"><circle cx="32" cy="32" r="32" fill="%23ff6b00"/><path d="M18 32L27 41L46 22" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>'),
      };
      e.target.src = fallbacks[type] || '';
    },
  },

  mounted() {
    this.loadCart();
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

/* Модальное окно */
.order-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(5px);
}
.order-modal {
  background: #fff;
  border-radius: 24px;
  width: 90%;
  max-width: 480px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  overflow: hidden;
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 25px;
  background: #ff6b00;
  color: #fff;
}
.modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 700;
}
.close-modal-btn {
  background: none;
  border: none;
  color: #fff;
  font-size: 28px;
  cursor: pointer;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.modal-content {
  padding: 30px;
  text-align: center;
}
.success-icon {
  width: 64px;
  height: 64px;
  object-fit: contain;
  margin: 0 auto 20px;
  display: block;
}
.modal-message {
  font-size: 18px;
  color: #1e1e1e;
  margin-bottom: 25px;
  font-weight: 500;
}
.order-info {
  background: #fefaf5;
  border-radius: 14px;
  padding: 20px;
  margin-bottom: 25px;
  text-align: left;
}
.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid #f0f0f0;
}
.info-row:last-child {
  border-bottom: none;
}
.info-label {
  font-size: 14px;
  color: #888;
  font-weight: 500;
}
.info-value {
  font-size: 16px;
  color: #1e1e1e;
  font-weight: 700;
}
.status-badge {
  display: inline-block;
  padding: 6px 14px;
  background: #fff3e0;
  color: #e65100;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
}
.modal-note {
  font-size: 14px;
  color: #888;
  margin: 20px 0;
}
.back-to-home-button {
  width: 100%;
  padding: 15px;
  background: #ff6b00;
  color: #fff;
  border: none;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: 0.2s;
}
.back-to-home-button:hover {
  background: #e05e00;
  transform: translateY(-2px);
}

@media (max-width: 768px) {
  .checkout-layout {
    grid-template-columns: 1fr;
  }
}
</style>