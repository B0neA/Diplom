<template>
  <div class="checkout-page">
    <div class="container">
      <div class="checkout-layout">
        <!-- Левая колонка - Состав заказа -->
        <div class="order-details-section">
          <h1 class="restaurant-title">Оформление заказа</h1>
          
          <!-- Состав заказа по ресторанам -->
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
              
              <div class="restaurant-total">
                Итого по ресторану: {{ group.total }} ₽
              </div>
            </div>
          </div>
          
          <div v-else class="empty-cart">
            <p>Корзина пуста</p>
            <router-link to="/" class="back-to-menu">Вернуться к меню</router-link>
          </div>
        </div>

        <!-- Правая колонка - Форма оплаты -->
        <div class="payment-form-section">
          <div class="order-summary-card">
            <h2 class="summary-title">Оплата</h2>
            
            <!-- Способ оплаты -->
            <div class="payment-section">
              <h3 class="payment-title">Способ оплаты</h3>
              
              <div class="card-input-group">
                <div class="card-input-wrapper">
                  <span class="card-icon">💳</span>
                  <input 
                    type="text" 
                    class="card-input" 
                    v-model="cardNumber" 
                    placeholder="Номер карты"
                    maxlength="19"
                    @input="formatCardNumber"
                  >
                </div>
                <div class="card-details">
                  <input 
                    type="text" 
                    class="card-expiry" 
                    v-model="cardExpiry" 
                    placeholder="ММ/ГГ"
                    maxlength="5"
                    @input="formatExpiry"
                  >
                  <input 
                    type="password" 
                    class="card-cvc" 
                    v-model="cardCvc" 
                    placeholder="CVC"
                    maxlength="3"
                  >
                </div>
              </div>
            </div>

            <div class="summary-divider"></div>

            <!-- Контактные данные -->
            <div class="contact-section">
              <h3 class="contact-title">Контактные данные</h3>
              
              <div class="form-group">
                <label class="form-label">Имя</label>
                <input type="text" class="form-input" v-model="customerName" placeholder="Ваше имя">
              </div>
              
              <div class="form-group">
                <label class="form-label">Телефон</label>
                <input type="num" class="form-input" v-model="customerPhone" placeholder="+7 (___) ___-__-__" @input="formatPhone">
              </div>
              
              <div class="form-group">
                <label class="form-label">Адрес доставки</label>
                <input type="text" class="form-input" v-model="deliveryAddress" placeholder="Улица, дом, квартира">
              </div>
              
              <div class="form-group">
                <label class="form-label">Комментарий к заказу</label>
                <textarea class="form-textarea" v-model="orderComment" placeholder="Дополнительные пожелания..." rows="3"></textarea>
              </div>
            </div>

            <div class="summary-divider"></div>

            <!-- Что в цене -->
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
                <input type="text" class="promo-input" v-model="promoCode" placeholder="Промокод">
                <button class="apply-promo-button" @click="applyPromo">Применить</button>
              </div>
              
              <div v-if="discount > 0" class="discount-row">
                <span class="discount-label">Скидка</span>
                <span class="discount-value">-{{ discount }} ₽</span>
              </div>
            </div>

            <div class="summary-divider"></div>

            <!-- Итоговая сумма -->
            <div class="final-summary">
              <div class="total-row">
                <span class="total-label">К оплате</span>
                <span class="total-value">{{ finalTotal }} ₽</span>
              </div>
              
              <div class="action-buttons">
                <button class="clear-cart-button" @click="clearCartAndRedirect">
                  Очистить корзину
                </button>
                
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

    <!-- Модальное окно подтверждения заказа -->
    <div v-if="showOrderModal" class="order-modal-overlay" @click="closeModal">
      <div class="order-modal" @click.stop>
        <div class="modal-header">
          <h3>Заказ оформлен!</h3>
          <button class="close-modal-btn" @click="closeModal">×</button>
        </div>
        
        <div class="modal-content">
          <div class="success-icon">✓</div>
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
          
          <p class="modal-note">
            Мы свяжемся с вами для подтверждения заказа в течение 15 минут
          </p>
          
          <button class="back-to-home-button" @click="goToHome">
            Вернуться на главную
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CheckoutPage',
  data() {
    return {
      cartItems: [],
      restaurants: [
        { id: 1, title: 'KFC', rating: 4 },
        { id: 2, title: 'Велопицца', rating: 5 },
        { id: 3, title: 'Вкусно и точка', rating: 3 },
        { id: 4, title: 'На лаваше', rating: 4 },
        { id: 5, title: 'Pizzaiolo', rating: 4 },
        { id: 6, title: 'Burger King', rating: 4 },
        { id: 7, title: 'Subway', rating: 4 },
        { id: 8, title: 'Starbucks', rating: 5 },
        
        { id: 10, title: 'Теремок', rating: 4 },
        { id: 11, title: 'Шаурма №1', rating: 3 },
        { id: 12, title: 'Dominos Pizza', rating: 5 },
        { id: 13, title: 'Суши Wok', rating: 4 },
        { id: 14, title: 'Kebab House', rating: 4 },
        { id: 15, title: 'Кофемания', rating: 5 },
        { id: 16, title: 'Чайхона №1', rating: 4 },
        { id: 17, title: 'Папа Джонс', rating: 4 },
        { id: 18, title: 'Вареничная №1', rating: 5 },
        { id: 19, title: 'Ванлав', rating: 4 },
        { id: 20, title: 'Арома', rating: 4 },
      ],
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
      orderNumber: ''
    }
  },
  computed: {
    // Группируем товары по ресторанам
    groupedCartItems() {
      const groups = {};
      
      this.cartItems.forEach(item => {
        if (!groups[item.restaurantId]) {
          const restaurant = this.restaurants.find(r => r.id === item.restaurantId);
          groups[item.restaurantId] = {
            id: item.restaurantId,
            name: restaurant ? restaurant.title : `Ресторан #${item.restaurantId}`,
            items: [],
            total: 0
          };
        }
        
        groups[item.restaurantId].items.push(item);
        groups[item.restaurantId].total += item.price * item.quantity;
      });
      
      return Object.values(groups);
    },
    
    // Общая сумма товаров в корзине
    cartTotal() {
      return this.cartItems.reduce((total, item) => {
        return total + (item.price * item.quantity);
      }, 0);
    },
    
    // Итоговая сумма с учетом доставки, сбора и скидки
    finalTotal() {
      const total = this.cartTotal + this.serviceFee;
      return Math.max(0, total - this.discount);
    },
    
    // Проверка валидности формы
    isFormValid() {
      return (
        this.customerName.trim() !== '' &&
        this.customerPhone.replace(/\D/g, '').length >= 11 &&
        this.deliveryAddress.trim() !== '' &&
        this.cardNumber.replace(/\D/g, '').length === 16 &&
        this.cardExpiry.length === 5 &&
        this.cardCvc.length === 3
      );
    }
  },
  methods: {
    // Загружаем корзину из localStorage
    loadCart() {
      const savedCart = localStorage.getItem('shoppingCart');
      if (savedCart) {
        this.cartItems = JSON.parse(savedCart);
      }
    },
    
    // Форматирование номера карты
    formatCardNumber() {
      let numbers = this.cardNumber.replace(/\D/g, '');
      numbers = numbers.substring(0, 16);
      
      // Добавляем пробелы через каждые 4 цифры
      let groups = [];
      for (let i = 0; i < numbers.length; i += 4) {
        groups.push(numbers.substring(i, i + 4));
      }
      this.cardNumber = groups.join(' ');
    },
    
    // Форматирование срока действия карты
    formatExpiry() {
      let numbers = this.cardExpiry.replace(/\D/g, '');
      if (numbers.length >= 2) {
        this.cardExpiry = numbers.substring(0, 2) + '/' + numbers.substring(2, 4);
      } else {
        this.cardExpiry = numbers;
      }
    },
    
    // Форматирование телефона
    formatPhone() {
      let numbers = this.customerPhone.replace(/\D/g, '');
  
  // Ограничиваем длину
  numbers = numbers.substring(0, 11);
  
  // Если начинается с 8, меняем на 7 (российский формат)
  if (numbers.startsWith('8') && numbers.length > 0) {
    numbers = '7' + numbers.substring(1);
  }
  
  // Если начинается с 9 и 10 цифр (без кода страны)
  if (numbers.startsWith('9') && numbers.length === 10) {
    numbers = '7' + numbers;
  }
  
  // Форматируем номер
  if (numbers.length > 0) {
    let formatted = '+7';
    if (numbers.length > 1) {
      formatted += ' (' + numbers.substring(1, 4);
    }
    if (numbers.length > 4) {
      formatted += ') ' + numbers.substring(4, 7);
    }
    if (numbers.length > 7) {
      formatted += '-' + numbers.substring(7, 9);
    }
    if (numbers.length > 9) {
      formatted += '-' + numbers.substring(9, 11);
    }
    this.customerPhone = formatted;
  } else {
    this.customerPhone = '';
  }
    },
    
    // Применение промокода
    applyPromo() {
      if (this.promoCode.toUpperCase() === 'PIZZA20') {
        this.discount = Math.floor(this.cartTotal * 0.2); // 20% скидка
        alert('Промокод применен! Скидка 20%');
      } else if (this.promoCode.toUpperCase() === 'FREE50') {
        this.discount = 50;
        alert('Промокод применен! Скидка 50 рублей');
      } else if (this.promoCode.trim() !== '') {
        alert('Промокод не найден');
      }
    },
    
    // Очистка корзины и переход на главную
    clearCartAndRedirect() {
      if (confirm('Вы уверены, что хотите очистить корзину?')) {
        localStorage.removeItem('shoppingCart');
        this.cartItems = [];
        this.$router.push('/');
      }
    },
    
    // Оформление заказа
    submitOrder() {
      if (!this.isFormValid) {
        alert('Пожалуйста, заполните все поля корректно');
        return;
      }
      
      // Генерируем номер заказа
      this.orderNumber = 'ORD' + Date.now().toString().slice(-8);
      
      // Показываем модальное окно
      this.showOrderModal = true;
      
      // Очищаем корзину
      localStorage.removeItem('shoppingCart');
      
      // Здесь можно добавить отправку данных на сервер
      console.log('Заказ оформлен:', {
        orderNumber: this.orderNumber,
        customerName: this.customerName,
        customerPhone: this.customerPhone,
        deliveryAddress: this.deliveryAddress,
        orderComment: this.orderComment,
        cardNumber: this.cardNumber,
        total: this.finalTotal,
        items: this.cartItems
      });
    },
    
    // Закрытие модального окна
    closeModal() {
      this.showOrderModal = false;
    },
    
    // Переход на главную
    goToHome() {
      this.closeModal();
      this.$router.push('/');
    }
  },
  mounted() {
    this.loadCart();
  }
}
</script>

<style scoped>
.checkout-page {
  background-color: #f8f9fa;
  min-height: 100vh;
  padding: 40px 0;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
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
}

/* Левая колонка - Состав заказа */
.order-details-section {
  background: white;
  border-radius: 12px;
  padding: 30px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.restaurant-title {
  font-size: 24px;
  font-weight: bold;
  color: #333;
  margin-bottom: 30px;
  padding-bottom: 15px;
  border-bottom: 2px solid #e0e0e0;
}

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
  padding: 10px 20px;
  background: #4CAF50;
  color: white;
  text-decoration: none;
  border-radius: 6px;
  font-weight: 500;
}

/* Группа ресторана */
.restaurant-order-group {
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 1px solid #eee;
}

.restaurant-order-group:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.restaurant-name {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid #f0f0f0;
}

.restaurant-items {
  margin-bottom: 15px;
}

.order-item-detail {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 12px 0;
  border-bottom: 1px solid #f8f8f8;
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
  font-weight: 500;
  color: #333;
}

.item-description {
  font-size: 14px;
  color: #666;
  line-height: 1.4;
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
  color: #666;
}

.item-total {
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.restaurant-total {
  text-align: right;
  font-size: 16px;
  font-weight: 600;
  color: #333;
  padding: 10px 0;
  border-top: 1px solid #f0f0f0;
}

/* Правая колонка - Форма оплаты */
.payment-form-section {
  align-self: start;
  width: auto;
}

.order-summary-card {
  background: white;
  border-radius: 12px;
  padding: 25px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.summary-title {
  font-size: 20px;
  font-weight: 600;
  color: #333;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid #e0e0e0;
}

.summary-divider {
  height: 1px;
  background: #e0e0e0;
  margin: 20px 0;
}

/* Способ оплаты */
.payment-section {
  margin: 20px 0;
}

.payment-title {
  font-size: 16px;
  font-weight: 600;
  color: #333;
  margin-bottom: 15px;
}

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
  left: 15px;
  font-size: 20px;
}

.card-input {
  width: 86%;
  padding: 12px 16px 12px 45px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 16px;
  letter-spacing: 1px;
}

.card-input:focus {
  outline: none;
  border-color: #4CAF50;
}

.card-details {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
}

.card-expiry, .card-cvc {
  width: 83%;
  padding: 12px 16px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 16px;
}

/* Контактные данные */
.contact-section {
  margin: 20px 0;
}

.contact-title {
  font-size: 16px;
  font-weight: 600;
  color: #333;
  margin-bottom: 15px;
}

.form-group {
  margin-bottom: 15px;
}

.form-label {
  display: block;
  font-size: 14px;
  color: #666;
  margin-bottom: 8px;
  font-weight: 500;
}

.form-input {
  width: 92%;
  padding: 12px 16px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 16px;
  transition: border-color 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #4CAF50;
}

.form-textarea {
  width: 92%;
  padding: 12px 16px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 16px;
  resize: vertical;
  min-height: 80px;
  transition: border-color 0.2s;
}

.form-textarea:focus {
  outline: none;
  border-color: #4CAF50;
}

/* Что в цене */
.price-breakdown {
  margin: 20px 0;
}

.price-title {
  font-size: 16px;
  font-weight: 600;
  color: #333;
  margin-bottom: 15px;
}

.price-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
}

.price-label {
  font-size: 14px;
  color: #666;
}

.price-value {
  font-size: 14px;
  color: #333;
  font-weight: 500;
}

.discount-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  color: #4CAF50;
}

.discount-label {
  font-size: 14px;
  font-weight: 500;
}

.discount-value {
  font-size: 14px;
  font-weight: 600;
}

.promo-section {
  display: flex;
  gap: 10px;
  margin-top: 15px;
}

.promo-input {
  flex: 1;
  padding: 10px 12px;
  border: 2px solid #e0e0e0;
  border-radius: 6px;
  font-size: 14px;
}

.apply-promo-button {
  padding: 10px 20px;
  background: #f8f9fa;
  border: 2px solid #e0e0e0;
  border-radius: 6px;
  font-size: 14px;
  color: #333;
  cursor: pointer;
  white-space: nowrap;
  font-weight: 500;
}

.apply-promo-button:hover {
  background: #f0f0f0;
}

/* Итоговая сумма */
.final-summary {
  text-align: center;
}

.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding: 15px 0;
  border-top: 2px solid #e0e0e0;
}

.total-label {
  font-size: 18px;
  font-weight: 600;
  color: #333;
}

.total-value {
  font-size: 24px;
  font-weight: bold;
  color: #333;
}

.action-buttons {
  display: flex;
  gap: 15px;
  margin-bottom: 15px;
}

.clear-cart-button {
  flex: 1;
  padding: 15px;
  background: white;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 16px;
  color: #666;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.clear-cart-button:hover {
  background: #f8f9fa;
  border-color: #ff4444;
  color: #ff4444;
}

.pay-button {
  flex: 2;
  padding: 15px;
  background: linear-gradient(135deg, #4CAF50, #45a049);
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  color: white;
  cursor: pointer;
  transition: all 0.2s;
}

.pay-button:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

.pay-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.agreement-text {
  font-size: 12px;
  color: #666;
  line-height: 1.4;
  margin-top: 10px;
}

.agreement-link {
  color: #4CAF50;
  text-decoration: none;
}

.agreement-link:hover {
  text-decoration: underline;
}

/* Модальное окно подтверждения заказа */
.order-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(5px);
}

.order-modal {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  overflow: hidden;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 25px;
  background: #4CAF50;
  color: white;
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.close-modal-btn {
  background: none;
  border: none;
  color: white;
  font-size: 28px;
  cursor: pointer;
  padding: 0;
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
  width: 60px;
  height: 60px;
  background: #4CAF50;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 30px;
  font-weight: bold;
  margin: 0 auto 20px;
}

.modal-message {
  font-size: 18px;
  color: #333;
  margin-bottom: 25px;
  line-height: 1.5;
}

.order-info {
  background: #f8f9fa;
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 25px;
  text-align: left;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid #e0e0e0;
}

.info-row:last-child {
  border-bottom: none;
}

.info-label {
  font-size: 14px;
  color: #666;
  font-weight: 500;
}

.info-value {
  font-size: 16px;
  color: #333;
  font-weight: 600;
}

.status-badge {
  display: inline-block;
  padding: 6px 12px;
  background: #fff3cd;
  color: #856404;
  border-radius: 20px;
  font-size: 14px;
  font-weight: 500;
}

.modal-note {
  font-size: 14px;
  color: #666;
  margin: 20px 0;
  line-height: 1.5;
}

.back-to-home-button {
  width: 100%;
  padding: 15px;
  background: #4CAF50;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.back-to-home-button:hover {
  background: #45a049;
  transform: translateY(-2px);
}


</style>