<template>
  <div>
    <HeaderComponent />
    <div class="profile-page">
      <div class="container">
        <h1>Личный кабинет</h1>

        <div class="profile-card">
          <div class="profile-info">
            <h2>{{ fullName || 'Пользователь' }}</h2>
            <p>{{ email }}</p>
            <p>{{ phone || 'Телефон не указан' }}</p>
            <p>{{ address || 'Адрес не указан' }}</p>
          </div>
          <button @click="editMode = !editMode" class="edit-btn">
            {{ editMode ? 'Отмена' : 'Редактировать' }}
          </button>
        </div>

        <div v-if="editMode" class="edit-form">
          <div class="form-group">
            <label>Имя</label>
            <input v-model="fullName" type="text" placeholder="Ваше имя" />
          </div>
          <div class="form-group">
            <label>Телефон</label>
            <input v-model="phone" type="tel" placeholder="+7 (___) ___-__-__" @input="formatPhone" />
          </div>
          <div class="form-group">
            <label>Адрес</label>
            <input v-model="address" type="text" placeholder="Улица, дом, квартира" />
          </div>
          <button @click="saveProfile" class="save-btn">Сохранить</button>
        </div>

        <h2>Мои заказы</h2>
        <div v-if="orders.length === 0" class="empty-orders">
          <p>У вас пока нет заказов</p>
          <a href="/restaurans" class="order-link">Перейти к ресторанам</a>
        </div>
        <div v-else class="orders-list">
          <div v-for="order in orders" :key="order.id" class="order-card">
            <div class="order-header">
              <span>Заказ #{{ order.id }}</span>
              <span :class="['order-status', statusClass(order.status)]">{{ statusText(order.status) }}</span>
            </div>
            <div class="order-total">{{ order.total_amount }} ₽</div>
            <div class="order-date">{{ formatDate(order.created_at) }}</div>
          </div>
        </div>

        <button @click="logout" class="logout-btn">Выйти</button>
      </div>
    </div>
  </div>
</template>

<script>
import HeaderComponent from '../../../Components/HeaderComponent.vue';
import { supabase, api } from '../../../js/supabase.js';

export default {
  name: 'ProfilePage',
  components: { HeaderComponent },
  data() {
    return {
      fullName: '', email: '', phone: '', address: '',
      editMode: false, orders: [],
    };
  },
  methods: {
    async loadProfile() {
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) {
        window.location.href = '/auth';
        return;
      }
      this.email = user.email;
      const { data } = await api.get('/profiles', { params: { id: `eq.${user.id}`, select: '*' } });
      if (data?.[0]) {
        this.fullName = data[0].full_name || '';
        this.phone = this.formatPhoneNumber(data[0].phone) || '';
        this.address = data[0].address || '';
      }
    },
    async loadOrders() {
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) return;
      const { data } = await api.get('/orders', {
        params: { user_id: `eq.${user.id}`, select: '*', order: 'created_at.desc' },
      });
      this.orders = data || [];
    },
    async saveProfile() {
      const { data: { user } } = await supabase.auth.getUser();
      await api.patch('/profiles', {
        full_name: this.fullName,
        phone: this.phone.replace(/\D/g, ''),
        address: this.address,
      }, { params: { id: `eq.${user.id}` } });
      this.editMode = false;
      alert('Профиль обновлён!');
    },
    async logout() {
      await supabase.auth.signOut();
      window.location.href = '/';
    },
    formatPhone() {
      let numbers = this.phone.replace(/\D/g, '').slice(0, 11);
      if (numbers.startsWith('8')) numbers = '7' + numbers.slice(1);
      if (numbers.startsWith('9') && numbers.length === 10) numbers = '7' + numbers;
      this.phone = numbers.length
        ? `+7 (${numbers.slice(1,4)}) ${numbers.slice(4,7)}-${numbers.slice(7,9)}-${numbers.slice(9,11)}`
        : '';
    },
    formatPhoneNumber(phone) {
      if (!phone) return '';
      const numbers = phone.replace(/\D/g, '');
      if (numbers.length >= 11) {
        return `+7 (${numbers.slice(1,4)}) ${numbers.slice(4,7)}-${numbers.slice(7,9)}-${numbers.slice(9,11)}`;
      }
      return phone;
    },
    statusClass(s) {
      return { new: 'status-new', processing: 'status-new', delivering: 'status-new', delivered: 'status-done', cancelled: 'status-cancel' }[s] || '';
    },
    statusText(s) {
      return { new: 'Новый', processing: 'Готовится', delivering: 'В пути', delivered: 'Доставлен', cancelled: 'Отменён' }[s] || s;
    },
    formatDate(d) {
      return new Date(d).toLocaleString('ru-RU');
    },
  },
  mounted() {
    this.loadProfile();
    this.loadOrders();
  },
};
</script>

<style scoped>
.profile-page { background: #fefaf5; min-height: 100vh; padding: 2rem 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.container { max-width: 700px; margin: 0 auto; padding: 0 1.5rem; }
h1 { font-size: 28px; font-weight: 700; color: #1e1e1e; margin-bottom: 2rem; }
h2 { font-size: 22px; font-weight: 700; margin: 2rem 0 1rem; }
.profile-card { background: #fff; border-radius: 20px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 20px; margin-bottom: 1.5rem; }
.profile-info { flex: 1; }
.profile-info h2 { margin: 0 0 4px; font-size: 20px; font-weight: 600; }
.profile-info p { margin: 2px 0; font-size: 14px; color: #888; }
.edit-btn { padding: 10px 20px; background: #fff; border: 2px solid #eee; border-radius: 12px; cursor: pointer; font-weight: 600; }
.edit-btn:hover { border-color: #ff6b00; color: #ff6b00; }
.edit-form { background: #fff; border-radius: 20px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 16px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 14px; font-weight: 500; color: #666; }
.form-group input { padding: 12px 16px; border: 2px solid #eee; border-radius: 14px; font-size: 15px; background: #fafafa; }
.form-group input:focus { outline: none; border-color: #ff6b00; background: #fff; }
.save-btn { padding: 14px; background: #ff6b00; color: #fff; border: none; border-radius: 14px; font-size: 16px; font-weight: 700; cursor: pointer; }
.save-btn:hover { background: #e05e00; }
.empty-orders { text-align: center; padding: 3rem; background: #fff; border-radius: 20px; }
.empty-orders p { color: #888; margin-bottom: 12px; }
.order-link { color: #ff6b00; font-weight: 600; text-decoration: none; }
.orders-list { display: flex; flex-direction: column; gap: 12px; }
.order-card { background: #fff; border-radius: 16px; padding: 1.25rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-weight: 600; }
.order-status { padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }
.status-new { background: #fff3e0; color: #e65100; }
.status-done { background: #e8f5e9; color: #2e7d32; }
.status-cancel { background: #ffebee; color: #c62828; }
.order-total { font-size: 20px; font-weight: 700; color: #ff6b00; }
.order-date { font-size: 13px; color: #999; margin-top: 4px; }
.logout-btn { width: 100%; padding: 14px; background: #fff; color: #e74c3c; border: 2px solid #e74c3c; border-radius: 14px; font-size: 16px; font-weight: 700; cursor: pointer; margin-top: 2rem; }
.logout-btn:hover { background: #fff5f5; }
</style>