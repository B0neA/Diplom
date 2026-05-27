<template>
  <div class="auth-page">
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
    </Head>
    <div class="auth-card auth-card--forgot">
      <div class="auth-header">
        <h1>Восстановление пароля</h1>
        <p>Введите телефон, указанный при регистрации</p>
      </div>

      <form v-if="step === 1" class="auth-form" @submit.prevent="checkPhone">
        <div class="form-group">
          <label>Телефон</label>
          <input v-model="phone" type="tel" placeholder="+7 (___) ___-__-__" required @input="formatPhone" />
        </div>
        <div v-if="error" class="auth-error">{{ error }}</div>
        <button type="submit" class="auth-btn" :disabled="loading">{{ loading ? 'Проверка...' : 'Продолжить' }}</button>
      </form>

      <form v-else class="auth-form" @submit.prevent="resetPassword">
        <div class="form-group">
          <label>Новый пароль</label>
          <input v-model="password" type="password" minlength="6" required />
        </div>
        <div class="form-group">
          <label>Повторите пароль</label>
          <input v-model="passwordConfirm" type="password" minlength="6" required />
        </div>
        <div v-if="error" class="auth-error">{{ error }}</div>
        <div v-if="success" class="auth-success">{{ success }}</div>
        <button type="submit" class="auth-btn" :disabled="loading">{{ loading ? 'Сохранение...' : 'Сменить пароль' }}</button>
      </form>

      <a href="/auth" class="back-link">← К входу</a>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3';
import { formatPhoneInput } from '../../supabase.js';
import { usePageSeo } from '../../usePageSeo.js';

export default {
  name: 'ForgotPasswordPage',
  components: { Head },
  setup() {
    const seo = usePageSeo('Забыли пароль — Лопать Подано', 'Восстановите доступ к аккаунту Лопать Подано по номеру телефона, указанному при регистрации.');
    return { seo };
  },
  data() {
    return {
      step: 1,
      phone: '',
      password: '',
      passwordConfirm: '',
      error: '',
      success: '',
      loading: false,
    };
  },
  methods: {
    formatPhone() {
      this.phone = formatPhoneInput(this.phone);
    },
    async checkPhone() {
      this.loading = true;
      this.error = '';
      try {
        await window.axios.post('/api/auth/forgot-password/check', {
          phone: this.phone.replace(/\D/g, ''),
        });
        this.step = 2;
      } catch (e) {
        this.error = e?.response?.data?.error || 'Пользователь не найден';
      } finally {
        this.loading = false;
      }
    },
    async resetPassword() {
      this.loading = true;
      this.error = '';
      this.success = '';
      try {
        await window.axios.post('/api/auth/forgot-password/reset', {
          phone: this.phone.replace(/\D/g, ''),
          password: this.password,
          password_confirmation: this.passwordConfirm,
        });
        this.success = 'Пароль изменён. Сейчас вы будете перенаправлены на вход.';
        setTimeout(() => { window.location.href = '/auth'; }, 2000);
      } catch (e) {
        this.error = e?.response?.data?.error || 'Не удалось сменить пароль';
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
.auth-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #fefaf5; padding: 1rem; }
.auth-card { background: #fff; border-radius: 20px; padding: 2rem; width: 100%; max-width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
.auth-header h1 { margin: 0 0 8px; font-size: 1.5rem; }
.auth-header p { margin: 0; color: #888; font-size: 14px; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; font-size: 13px; color: #666; margin-bottom: 4px; }
.form-group input { width: 100%; padding: 12px; border: 2px solid #eee; border-radius: 10px; box-sizing: border-box; }
.auth-btn { width: 100%; padding: 12px; background: #ff6b00; color: #fff; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }
.auth-error { color: #e74c3c; font-size: 14px; margin-bottom: 10px; }
.auth-success { color: #2e7d32; font-size: 14px; margin-bottom: 10px; }
.auth-card--forgot { text-align: center; }
.auth-form { text-align: left; }
.back-link {
  display: inline-block;
  margin-top: 1.25rem;
  padding-bottom: 2px;
  color: #ff6b00;
  font-weight: 600;
  font-size: 14px;
  text-decoration: none;
  border-bottom: 2px solid #ff6b00;
  transition: color 0.2s, border-color 0.2s;
}
.back-link:hover {
  color: #e05e00;
  border-bottom-color: #e05e00;
}
</style>
