<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { loadSiteSettings, applySiteSettings } from '../settingsCache.js';
import { usePageSeo } from '../usePageSeo.js';

const seo = usePageSeo(
  'Лопать Подано — доставка еды за 30 минут',
  'Закажите еду из лучших ресторанов с доставкой. Бесплатная доставка от 800 ₽, скидки и быстрый сервис Лопать Подано.'
);

const settings = reactive({
  question_icon: '',
  shoppingbasket_icon: '',
  benefit_time_icon: '',
  card_icon: '',
  reset_icon: '',
  lock_icon: '',
  present_icon: '',
  phone_icon: '',
  star_icon: '',
  time_icon: '',
  save_icon: '',
  alarm_icon: '',
  hero_bg_image: '',
  hero_title: 'Еда, которую вы любите, за 30 минут',
  hero_subtitle: 'Лучшие рестораны и блюда с доставкой до двери',
  benefit_1_icon: '',
  benefit_1_title: 'Бесплатная доставка от 800 ₽',
  benefit_1_desc: 'Без скрытых комиссий и переплат',
  benefit_2_icon: '',
  benefit_2_title: '30 минут или скидка 50%',
  benefit_2_desc: 'Если задержим — ваша выгода',
  benefit_3_icon: '',
  benefit_3_title: 'Кэшбек 10% на следующий заказ',
  benefit_3_desc: 'С каждого заказа на ваш счёт',
  cta_title: 'Готовы заказать?',
  cta_subtitle: 'Более 200 блюд из 20 ресторанов ждут вас',
  cta_button_text: 'Смотреть меню',
});

const faqList = ref([]);
const openFaqIds = ref(new Set());
const faqExpandAll = ref(false);
const feedback = reactive({ name: '', email: '', message: '' });
const feedbackSending = ref(false);
const feedbackSuccess = ref('');
const feedbackError = ref('');

const defaultFaq = [
  { id: 1, icon_key: 'shoppingbasket_icon', question: 'Как оформить заказ?', answer: 'Выберите ресторан, добавьте блюда в корзину. Для оформления нужна регистрация.' },
  { id: 2, icon_key: 'benefit_time_icon', question: 'Сколько ждать доставку?', answer: 'Обычно 30–45 минут. В редких случаях (пробки, погода) — до 60 минут.' },
  { id: 3, icon_key: 'card_icon', question: 'Можно ли оплатить картой?', answer: 'Да, на этапе оформления заказа (демо-режим).' },
  { id: 4, icon_key: 'reset_icon', question: 'Можно ли изменить или отменить заказ?', answer: 'Да, пока ресторан не начал готовить (первые 5 минут после оформления). В личном кабинете → Мои заказы → Отменить.' },
  { id: 5, icon_key: 'lock_icon', question: 'Зачем нужна регистрация?', answer: 'Чтобы сохранять историю заказов, адреса доставки и получать персональные скидки.' },
  { id: 6, icon_key: 'present_icon', question: 'Есть ли у вас промокоды и скидки?', answer: 'Да! Промокоды можно ввести при оформлении заказа. Попробуйте PIZZA20 (скидка 20% на первый заказ). Также дарим скидку 10% в День Рождения — она автоматически применяется к сумме блюд в ваш праздник!' },
  { id: 7, icon_key: 'phone_icon', question: 'Как связаться с поддержкой?', answer: 'Email: support@lopatpodano.ru\nТелефон: +7 (800) 123-45-67 (ежедневно с 9:00 до 23:00)' },
  { id: 8, icon_key: 'shoppingbasket_icon', question: 'Почему некоторых ресторанов нет в списке?', answer: 'Рестораны могут быть временно недоступны или не работать в ваш район.' },
  { id: 9, icon_key: 'star_icon', question: 'Как оставить отзыв о ресторане?', answer: 'После получения заказа вам придёт ссылка на оценку в личном кабинете.' },
  { id: 10, icon_key: 'time_icon', question: 'В какое время работает доставка?', answer: 'С 9:00 до 23:00. Ночные заказы временно не принимаются.' },
  { id: 11, icon_key: 'save_icon', question: 'Мои данные в безопасности?', answer: 'Да, все данные хранятся в зашифрованном виде. Платёжные данные не сохраняются.' },
  { id: 12, icon_key: 'alarm_icon', question: 'Что делать, если заказ не пришёл?', answer: 'Проверьте статус в личном кабинете или свяжитесь с поддержкой. Мы решим проблему за 15 минут.' },
];

const faqIcon = (item) => {
  const key = item.icon_key || item.iconKey;
  return key && settings[key] ? settings[key] : '';
};

const isFaqOpen = (id) => faqExpandAll.value || openFaqIds.value.has(id);

const loadSettings = async () => {
  try {
    const data = await loadSiteSettings();
    applySiteSettings(settings, data);
  } catch (err) {
    console.error('Ошибка загрузки настроек:', err);
  }
};

const loadFaq = async () => {
  try {
    const { data } = await window.axios.get('/api/faq');
    faqList.value = data?.length ? data : defaultFaq;
  } catch {
    faqList.value = defaultFaq;
  }
};

const toggleFaq = (id) => {
  if (faqExpandAll.value) {
    faqExpandAll.value = false;
    openFaqIds.value = new Set(faqList.value.map((i) => i.id).filter((x) => x !== id));
    return;
  }
  const next = new Set(openFaqIds.value);
  if (next.has(id)) next.delete(id);
  else next.add(id);
  openFaqIds.value = next;
};

const expandAllFaq = () => {
  faqExpandAll.value = true;
};

const collapseAllFaq = () => {
  faqExpandAll.value = false;
  openFaqIds.value = new Set();
};

const submitFeedback = async () => {
  feedbackError.value = '';
  feedbackSuccess.value = '';
  if (!feedback.name.trim() || !feedback.email.trim() || !feedback.message.trim()) {
    feedbackError.value = 'Заполните все поля';
    return;
  }
  feedbackSending.value = true;
  try {
    await window.axios.post('/api/feedback', { ...feedback });
    feedbackSuccess.value = 'Спасибо! Мы получили ваше сообщение.';
    feedback.name = '';
    feedback.email = '';
    feedback.message = '';
  } catch {
    feedbackError.value = 'Не удалось отправить сообщение';
  } finally {
    feedbackSending.value = false;
  }
};

const goToRestaurants = () => router.visit('/restaurans');

const onImageError = (e) => {
  e.target.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="%23f0f0f0"><rect width="64" height="64" rx="12"/></svg>';
};

onMounted(() => {
  loadSettings();
  loadFaq();
});
</script>

<template>
  <Head>
    <title>{{ seo.title }}</title>
    <meta name="description" :content="seo.description" />
  </Head>
  <div class="landing">
    <section class="hero" :style="{ backgroundImage: `url(${settings.hero_bg_image})` }">
      <div class="hero__overlay"></div>
      <div class="hero__content">
        <h1 class="hero__title">{{ settings.hero_title }}</h1>
        <p class="hero__subtitle">{{ settings.hero_subtitle }}</p>
        <button @click="goToRestaurants" class="hero__btn">Выбрать ресторан</button>
      </div>
      <div class="hero__scroll-hint"><span></span><span></span><span></span></div>
    </section>

    <section class="benefits">
      <h2 class="benefits__title">Почему Лопать Подано</h2>
      <div class="benefits__grid">
        <div class="benefit-card">
          <img :src="settings.benefit_1_icon" alt="" class="benefit-icon" @error="onImageError" />
          <h3>{{ settings.benefit_1_title }}</h3>
          <p>{{ settings.benefit_1_desc }}</p>
        </div>
        <div class="benefit-card">
          <img :src="settings.benefit_2_icon" alt="" class="benefit-icon" @error="onImageError" />
          <h3>{{ settings.benefit_2_title }}</h3>
          <p>{{ settings.benefit_2_desc }}</p>
        </div>
        <div class="benefit-card">
          <img :src="settings.benefit_3_icon" alt="" class="benefit-icon" @error="onImageError" />
          <h3>{{ settings.benefit_3_title }}</h3>
          <p>{{ settings.benefit_3_desc }}</p>
        </div>
      </div>
    </section>

    <section class="faq">
      <h2 class="section-title">Часто задаваемые вопросы</h2>
      <div class="faq-toolbar">
        <button type="button" class="faq-toolbar-btn" @click="expandAllFaq">Открыть все</button>
        <button type="button" class="faq-toolbar-btn faq-toolbar-btn--ghost" @click="collapseAllFaq">Свернуть все</button>
      </div>
      <div class="faq-list">
        <div v-for="item in faqList" :key="item.id" class="faq-item">
          <button class="faq-question" @click="toggleFaq(item.id)">
            <span class="faq-question-text">
              <img v-if="faqIcon(item)" :src="faqIcon(item)" alt="" class="faq-q-icon" @error="onImageError" />
              {{ item.question }}
            </span>
            <span class="faq-arrow">{{ isFaqOpen(item.id) ? '−' : '+' }}</span>
          </button>
          <div v-show="isFaqOpen(item.id)" class="faq-answer">{{ item.answer }}</div>
        </div>
      </div>
    </section>

    <section class="feedback">
      <h2 class="section-title">Обратная связь</h2>
      <p class="feedback-sub">Есть вопросы или предложения? Напишите нам</p>
      <form class="feedback-form" @submit.prevent="submitFeedback">
        <input v-model="feedback.name" type="text" placeholder="Ваше имя" required />
        <input v-model="feedback.email" type="email" placeholder="Email" required />
        <textarea v-model="feedback.message" placeholder="Сообщение" rows="4" required></textarea>
        <p v-if="feedbackError" class="msg error">{{ feedbackError }}</p>
        <p v-if="feedbackSuccess" class="msg success">{{ feedbackSuccess }}</p>
        <button type="submit" class="hero__btn" :disabled="feedbackSending">
          {{ feedbackSending ? 'Отправка...' : 'Отправить' }}
        </button>
      </form>
    </section>

    <section class="cta">
      <div class="cta__content">
        <h2>{{ settings.cta_title }}</h2>
        <p>{{ settings.cta_subtitle }}</p>
        <button @click="goToRestaurants" class="hero__btn">{{ settings.cta_button_text }}</button>
      </div>
    </section>
  </div>
</template>

<style scoped>
.landing {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  color: #1e1e1e;
  background: #fefaf5;
}
.hero {
  position: relative;
  height: 100vh;
  min-height: 600px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  background: center/cover no-repeat;
  background-attachment: fixed;
  overflow: hidden;
}
.hero__overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(0,0,0,0.65) 0%, rgba(0,0,0,0.35) 50%, rgba(0,0,0,0.55) 100%);
  z-index: 1;
}
.hero__content { position: relative; z-index: 2; max-width: 700px; padding: 0 1.5rem; }
.hero__title { font-size: 3.5rem; font-weight: 800; color: #fff; margin-bottom: 1.5rem; line-height: 1.2; text-shadow: 0 2px 12px rgba(0,0,0,0.3); }
.hero__subtitle { font-size: 1.3rem; color: rgba(255,255,255,0.9); margin-bottom: 2.5rem; }
.hero__btn {
  display: inline-block; padding: 16px 40px; background: #ff6b00; color: #fff; border: none;
  border-radius: 50px; font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: .3s;
  text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 6px 24px rgba(255,107,0,0.4);
}
.hero__btn:hover:not(:disabled) { background: #e05e00; transform: translateY(-3px); }
.hero__btn:disabled { opacity: 0.6; cursor: not-allowed; }
.hero__scroll-hint { position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); z-index: 2; display: flex; flex-direction: column; align-items: center; gap: 6px; }
.hero__scroll-hint span { display: block; width: 8px; height: 8px; border-right: 2px solid #fff; border-bottom: 2px solid #fff; transform: rotate(45deg); animation: scroll-hint 1.5s infinite; opacity: 0; }
.hero__scroll-hint span:nth-child(1) { animation-delay: 0s; }
.hero__scroll-hint span:nth-child(2) { animation-delay: 0.2s; }
.hero__scroll-hint span:nth-child(3) { animation-delay: 0.4s; }
@keyframes scroll-hint {
  0% { opacity: 0; transform: rotate(45deg) translate(-4px, -4px); }
  50% { opacity: 1; }
  100% { opacity: 0; transform: rotate(45deg) translate(4px, 4px); }
}
.benefits { padding: 5rem 1.5rem; max-width: 1200px; margin: 0 auto; }
.benefits__title { font-size: 2.2rem; text-align: center; margin-bottom: 3rem; font-weight: 700; }
.benefits__grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; }
.benefit-card { background: #fff; border-radius: 24px; padding: 2.5rem 2rem; text-align: center; box-shadow: 0 4px 16px rgba(0,0,0,0.05); transition: .3s; }
.benefit-card:hover { transform: translateY(-8px); box-shadow: 0 12px 32px rgba(0,0,0,0.1); }
.benefit-card h3 { font-size: 1.2rem; margin-bottom: 0.6rem; font-weight: 700; }
.benefit-card p { color: #888; font-size: 0.95rem; }
.benefit-icon { width: 64px; height: 64px; margin-bottom: 1.2rem; object-fit: contain; display: block; margin-left: auto; margin-right: auto; }
.section-title { font-size: 2rem; text-align: center; margin-bottom: 2rem; font-weight: 700; }
.faq { padding: 4rem 1.5rem; max-width: 800px; margin: 0 auto; }
.faq-toolbar { display: flex; gap: 10px; justify-content: center; margin-bottom: 1rem; flex-wrap: wrap; }
.faq-toolbar-btn { padding: 8px 16px; border: none; background: #ff6b00; color: #fff; border-radius: 20px; font-weight: 600; cursor: pointer; font-size: 14px; }
.faq-toolbar-btn--ghost { background: #fff; color: #666; border: 2px solid #eee; }
.faq-item { background: #fff; border-radius: 16px; margin-bottom: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
.faq-question { width: 100%; padding: 18px 20px; background: none; border: none; text-align: left; font-size: 16px; font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
.faq-question-text { display: flex; align-items: center; gap: 10px; flex: 1; }
.faq-q-icon { width: 22px; height: 22px; object-fit: contain; flex-shrink: 0; }
.faq-question:hover { color: #ff6b00; }
.faq-arrow { color: #ff6b00; font-size: 20px; flex-shrink: 0; }
.faq-answer { padding: 0 20px 18px; color: #666; line-height: 1.5; white-space: pre-line; }
.feedback { padding: 4rem 1.5rem; max-width: 560px; margin: 0 auto; text-align: center; }
.feedback-sub { color: #888; margin: -1rem 0 1.5rem; }
.feedback-form { display: flex; flex-direction: column; gap: 12px; text-align: left; }
.feedback-form input, .feedback-form textarea {
  padding: 14px 16px; border: 2px solid #eee; border-radius: 14px; font-size: 15px; background: #fff;
}
.msg { font-size: 14px; margin: 0; }
.msg.error { color: #e74c3c; }
.msg.success { color: #2e7d32; }
.cta { background: linear-gradient(135deg, #fff5eb, #ffe6d5); padding: 5rem 1.5rem; text-align: center; }
.cta__content h2 { font-size: 2.2rem; font-weight: 700; margin-bottom: 0.8rem; }
.cta__content p { font-size: 1.1rem; color: #888; margin-bottom: 2rem; }
@media (max-width: 768px) {
  .hero__title { font-size: 2.2rem; }
  .hero__subtitle { font-size: 1rem; }
  .benefits__title, .cta__content h2, .section-title { font-size: 1.6rem; }
  .hero { background-attachment: scroll; }
}
</style>
