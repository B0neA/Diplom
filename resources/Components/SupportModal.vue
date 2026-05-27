<template>
  <div v-if="show" class="support-overlay" @click.self="$emit('close')">
    <div v-if="!success" class="support-modal" @click.stop>
      <div class="support-modal-header">
        <h3>Обращение в поддержку</h3>
        <button type="button" class="close-btn" aria-label="Закрыть" @click="$emit('close')">×</button>
      </div>
      <p class="support-hint">Опишите проблему — мы ответим как можно скорее</p>
      <form class="support-form" @submit.prevent="submit">
        <div class="form-group">
          <label>Имя</label>
          <input v-model="form.name" type="text" placeholder="Ваше имя" required />
        </div>
        <div class="form-group">
          <label>Номер телефона</label>
          <input v-model="form.phone" type="tel" placeholder="+7 (___) ___-__-__" required @input="onPhoneInput" />
        </div>
        <div class="form-group">
          <label>Ваша проблема</label>
          <textarea v-model="form.message" rows="4" placeholder="Опишите ситуацию..." required></textarea>
        </div>
        <p v-if="error" class="support-error">{{ error }}</p>
        <button type="submit" class="submit-btn" :disabled="sending">
          {{ sending ? 'Отправка...' : 'Отправить' }}
        </button>
      </form>
    </div>

    <div v-else class="support-modal support-modal--success" @click.stop>
      <div class="success-icon-wrap">✓</div>
      <h3>Заявка отправлена</h3>
      <p>Мы получили ваше обращение. Поддержка свяжется с вами в ближайшее время.</p>
      <button type="button" class="submit-btn" @click="closeAfterSuccess">Понятно</button>
    </div>
  </div>
</template>

<script>
import { formatPhoneInput } from '../js/supabase.js';

export default {
  name: 'SupportModal',
  props: {
    show: { type: Boolean, default: false },
    initialName: { type: String, default: '' },
    initialPhone: { type: String, default: '' },
    userId: { type: String, default: null },
  },
  emits: ['close'],
  data() {
    return {
      form: { name: '', phone: '', message: '' },
      sending: false,
      error: '',
      success: false,
    };
  },
  watch: {
    show(val) {
      if (val) this.resetForm();
    },
    initialName() {
      if (this.show && !this.success) this.form.name = this.initialName;
    },
    initialPhone() {
      if (this.show && !this.success) this.form.phone = this.initialPhone;
    },
  },
  methods: {
    resetForm() {
      this.form = {
        name: this.initialName,
        phone: this.initialPhone,
        message: '',
      };
      this.error = '';
      this.success = false;
      this.sending = false;
    },
    onPhoneInput() {
      this.form.phone = formatPhoneInput(this.form.phone);
    },
    async submit() {
      this.error = '';
      const name = this.form.name.trim();
      const phone = this.form.phone.replace(/\D/g, '');
      const message = this.form.message.trim();
      if (!name || phone.length < 11 || !message) {
        this.error = 'Заполните все поля';
        return;
      }
      this.sending = true;
      try {
        await window.axios.post('/api/support', {
          name,
          phone,
          message,
          userId: this.userId || null,
        });
        this.success = true;
      } catch (e) {
        this.error = e?.response?.data?.error || 'Не удалось отправить заявку';
      } finally {
        this.sending = false;
      }
    },
    closeAfterSuccess() {
      this.$emit('close');
    },
  },
};
</script>

<style scoped>
.support-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 1rem;
  backdrop-filter: blur(3px);
}
.support-modal {
  background: #fff;
  border-radius: 20px;
  padding: 1.5rem 1.75rem;
  width: 100%;
  max-width: 440px;
  box-shadow: 0 16px 48px rgba(0, 0, 0, 0.18);
}
.support-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}
.support-modal-header h3 { margin: 0; font-size: 20px; font-weight: 700; color: #1e1e1e; }
.close-btn {
  background: none;
  border: none;
  font-size: 26px;
  line-height: 1;
  cursor: pointer;
  color: #aaa;
  padding: 0 4px;
}
.close-btn:hover { color: #333; }
.support-hint { margin: 0 0 16px; font-size: 14px; color: #888; }
.support-form { display: flex; flex-direction: column; gap: 12px; }
.form-group label { display: block; font-size: 13px; color: #666; margin-bottom: 4px; font-weight: 500; }
.form-group input,
.form-group textarea {
  width: 100%;
  padding: 12px 14px;
  border: 2px solid #eee;
  border-radius: 12px;
  font-size: 15px;
  box-sizing: border-box;
  font-family: inherit;
}
.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #ff6b00;
}
.support-error { color: #e74c3c; font-size: 14px; margin: 0; }
.submit-btn {
  width: 100%;
  padding: 14px;
  background: #ff6b00;
  color: #fff;
  border: none;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  margin-top: 4px;
}
.submit-btn:hover:not(:disabled) { background: #e05e00; }
.submit-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.support-modal--success { text-align: center; padding: 2rem 1.75rem; }
.success-icon-wrap {
  width: 56px;
  height: 56px;
  margin: 0 auto 16px;
  background: #e8f5e9;
  color: #2e7d32;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  font-weight: 700;
}
.support-modal--success h3 { margin: 0 0 10px; font-size: 20px; }
.support-modal--success p { margin: 0 0 20px; color: #666; font-size: 15px; line-height: 1.5; }
</style>
