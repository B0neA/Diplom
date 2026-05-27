<template>
  <div class="auth-page">
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
    </Head>
    <div class="auth-card">
      <div class="auth-header">
        <h1>{{ isLogin ? 'Вход' : 'Регистрация' }}</h1>
        <p>{{ isLogin ? 'Войдите в аккаунт' : 'Создайте новый аккаунт' }}</p>
      </div>

      <form @submit.prevent="handleSubmit" class="auth-form">
        <div v-if="!isLogin" class="form-group">
          <label>Имя</label>
          <input v-model="fullName" type="text" placeholder="Ваше имя" required />
        </div>

        <div class="form-group">
          <label>Email</label>
          <input v-model="email" type="email" placeholder="example@mail.ru" required />
        </div>

        <div v-if="!isLogin" class="form-group">
          <label>Телефон</label>
          <input v-model="phone" type="tel" placeholder="+7 (___) ___-__-__" @input="formatPhone" />
        </div>

        <div class="form-group">
          <label>Пароль</label>
          <input v-model="password" type="password" placeholder="Минимум 6 символов" required minlength="6" />
        </div>

        <p v-if="isLogin" class="forgot-row">
          <a href="/forgot-password">Забыли пароль?</a>
        </p>

        <div v-if="error" class="auth-error">{{ error }}</div>
        <div v-if="success" class="auth-success">{{ success }}</div>

        <button type="submit" class="auth-btn" :disabled="loading">
          {{ loading ? 'Загрузка...' : (isLogin ? 'Войти' : 'Зарегистрироваться') }}
        </button>
      </form>

      <div class="auth-switch">
        {{ isLogin ? 'Нет аккаунта?' : 'Уже есть аккаунт?' }}
        <button @click="isLogin = !isLogin; error = ''; success = ''">
          {{ isLogin ? 'Зарегистрироваться' : 'Войти' }}
        </button>
      </div>

      <a href="/" class="back-link">← На главную</a>
    </div>
  </div>
</template>

<script>
import { router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { supabase, ensureProfile, formatPhoneInput } from '../../supabase.js';
import { syncCartWithAuth } from '../../cart.js';
import { usePageSeo } from '../../usePageSeo.js';

export default {
  name: 'AuthPage',
  components: { Head },
  computed: {
    seo() {
      return this.isLogin
        ? usePageSeo('Вход — FastBite', 'Войдите в аккаунт FastBite, чтобы оформлять заказы и видеть историю доставок.')
        : usePageSeo('Регистрация — FastBite', 'Создайте аккаунт FastBite: сохраняйте адреса, получайте скидки и отслеживайте заказы.');
    },
  },
  data() {
    return {
      isLogin: true,
      email: '',
      password: '',
      fullName: '',
      phone: '',
      error: '',
      success: '',
      loading: false,
    };
  },
  methods: {
    formatPhone() {
      this.phone = formatPhoneInput(this.phone);
    },

    async handleSubmit() {
      this.loading = true;
      this.error = '';
      this.success = '';

      try {
        if (this.isLogin) {
          const { error } = await supabase.auth.signInWithPassword({
            email: this.email,
            password: this.password,
          });
          if (error) throw error;
          await syncCartWithAuth();
          const redirect = new URLSearchParams(window.location.search).get('redirect') || '/restaurans';
          router.visit(redirect);
        } else {
          // Регистрация с авто-подтверждением (без проверки почты)
          const { data, error } = await supabase.auth.signUp({
            email: this.email,
            password: this.password,
            options: {
              data: {
                full_name: this.fullName,
                phone: this.phone.replace(/\D/g, ''),
              },
              // Отключаем подтверждение почты (фиктивное)
              emailRedirectTo: window.location.origin + '/auth',
            },
          });

          if (error) throw error;

          // Авто-вход после регистрации
          if (data?.user) {
            await ensureProfile(data.user, {
              full_name: this.fullName,
              phone: this.phone.replace(/\D/g, ''),
            });
            const { error: signInError } = await supabase.auth.signInWithPassword({
              email: this.email,
              password: this.password,
            });
            if (!signInError) {
              await syncCartWithAuth();
              const redirect = new URLSearchParams(window.location.search).get('redirect') || '/restaurans';
              router.visit(redirect);
              return;
            }
          }

          this.success = 'Регистрация успешна! Сейчас вы будете перенаправлены...';
          const redirect = new URLSearchParams(window.location.search).get('redirect') || '/restaurans';
          setTimeout(() => router.visit(redirect), 1500);
        }
      } catch (err) {
        this.error = err.message === 'Invalid login credentials' 
          ? 'Неверный email или пароль' 
          : err.message;
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fefaf5;
  padding: 2rem;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
.auth-card {
  background: #fff;
  border-radius: 24px;
  padding: 3rem 2.5rem;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.06);
}
.auth-header { text-align: center; margin-bottom: 2rem; }
.auth-header h1 { font-size: 24px; font-weight: 700; color: #1e1e1e; margin: 0 0 6px; }
.forgot-row { text-align: right; margin: -8px 0 0; }
.forgot-row a { color: #ff6b00; font-size: 14px; font-weight: 600; text-decoration: none; }
.forgot-row a:hover { text-decoration: underline; }
.auth-header p { color: #888; font-size: 15px; margin: 0; }
.auth-form { display: flex; flex-direction: column; gap: 16px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 14px; font-weight: 500; color: #666; }
.form-group input {
  padding: 12px 16px; border: 2px solid #eee; border-radius: 14px;
  font-size: 15px; transition: 0.2s; background: #fafafa;
}
.form-group input:focus { outline: none; border-color: #ff6b00; background: #fff; }
.auth-error { background: #fff5f5; color: #e74c3c; padding: 10px 14px; border-radius: 10px; font-size: 14px; }
.auth-success { background: #e8f5e9; color: #2e7d32; padding: 10px 14px; border-radius: 10px; font-size: 14px; }
.auth-btn {
  padding: 14px; background: #ff6b00; color: #fff; border: none;
  border-radius: 14px; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.2s;
}
.auth-btn:hover:not(:disabled) { background: #e05e00; }
.auth-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.auth-switch { text-align: center; margin-top: 20px; font-size: 14px; color: #888; }
.auth-switch button { background: none; border: none; color: #ff6b00; font-weight: 600; cursor: pointer; font-size: 14px; }
.back-link { display: block; text-align: center; margin-top: 20px; color: #888; text-decoration: none; font-size: 14px; }
.back-link:hover { color: #ff6b00; }
</style>