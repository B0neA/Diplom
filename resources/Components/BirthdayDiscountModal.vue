<template>
  <div v-if="show" class="birthday-modal-overlay" @click="$emit('close')">
    <div class="birthday-modal" @click.stop>
      <div class="birthday-modal-header">
        <h3>С днём рождения!</h3>
        <button type="button" class="close-btn" @click="$emit('close')">×</button>
      </div>
      <div class="birthday-modal-body">
        <p class="birthday-lead">Поздравляем! Сегодня для вас действует персональная скидка.</p>
        <p class="birthday-discount">−{{ percent }}% на заказ</p>
        <p class="birthday-note">
          Скидка автоматически применится к сумме блюд из ресторана (без сервисного сбора) и действует только сегодня.
        </p>
        <button type="button" class="birthday-ok-btn" @click="$emit('close')">Отлично!</button>
      </div>
    </div>
  </div>
</template>

<script>
import { BIRTHDAY_DISCOUNT_PERCENT } from '../js/birthdayDiscount.js';

export default {
  name: 'BirthdayDiscountModal',
  props: {
    show: { type: Boolean, default: false },
  },
  emits: ['close'],
  computed: {
    percent() {
      return BIRTHDAY_DISCOUNT_PERCENT;
    },
  },
};
</script>

<style scoped>
.birthday-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2100;
  backdrop-filter: blur(4px);
}
.birthday-modal {
  background: #fff;
  border-radius: 24px;
  width: 90%;
  max-width: 420px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
}
.birthday-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 18px 22px;
  background: linear-gradient(135deg, #ff6b00, #ff9a44);
  color: #fff;
}
.birthday-modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 700;
}
.close-btn {
  background: none;
  border: none;
  color: #fff;
  font-size: 28px;
  cursor: pointer;
  line-height: 1;
}
.birthday-modal-body {
  padding: 24px 22px 28px;
  text-align: center;
}
.birthday-lead {
  margin: 0 0 12px;
  font-size: 16px;
  color: #444;
  line-height: 1.5;
}
.birthday-discount {
  margin: 0 0 14px;
  font-size: 32px;
  font-weight: 800;
  color: #ff6b00;
}
.birthday-note {
  margin: 0 0 20px;
  font-size: 14px;
  color: #888;
  line-height: 1.45;
}
.birthday-ok-btn {
  width: 100%;
  padding: 14px;
  border: none;
  border-radius: 14px;
  background: #ff6b00;
  color: #fff;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
}
.birthday-ok-btn:hover {
  background: #e05e00;
}
</style>
