<template>
  <div v-if="show" class="order-modal-overlay" @click="$emit('close')">
    <div class="order-modal" @click.stop>
      <div class="modal-header">
        <h3>Заказ оформлен!</h3>
        <button type="button" class="close-modal-btn" @click="$emit('close')">×</button>
      </div>
      <div class="modal-content">
        <img
          v-if="successIcon"
          :src="successIcon"
          alt="Success"
          class="success-icon"
          @error="onSuccessIconError"
        />
        <p class="modal-message">Ваш заказ успешно оформлен и ожидает подтверждения</p>
        <div class="order-info">
          <div class="info-row">
            <span class="info-label">Номер заказа:</span>
            <span class="info-value">#{{ orderNumber }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Сумма:</span>
            <span class="info-value">{{ formattedTotal }} ₽</span>
          </div>
          <div class="info-row">
            <span class="info-label">Статус:</span>
            <span class="status-badge">Ожидает подтверждения</span>
          </div>
        </div>
        <p class="modal-note">Мы свяжемся с вами для подтверждения заказа в течение 15 минут</p>
        <button type="button" class="back-to-home-button" @click="$emit('go-home')">Вернуться на главную</button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'OrderSuccessModal',
  props: {
    show: { type: Boolean, default: false },
    orderNumber: { type: [String, Number], default: '' },
    total: { type: [String, Number], default: 0 },
    successIcon: { type: String, default: '' },
  },
  emits: ['close', 'go-home'],
  computed: {
    formattedTotal() {
      const n = Number(this.total);
      return Number.isFinite(n) ? n : 0;
    },
  },
  methods: {
    onSuccessIconError(e) {
      e.target.src = 'data:image/svg+xml,' + encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64" fill="none"><circle cx="32" cy="32" r="32" fill="%23ff6b00"/><path d="M18 32L27 41L46 22" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>'
      );
    },
  },
};
</script>

<style scoped>
.order-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
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
</style>
