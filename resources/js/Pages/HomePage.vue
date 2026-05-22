<script setup>
import { ref, reactive, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const api = axios.create({
  baseURL: 'https://cuibxmcjdkgjffmmzwgd.supabase.co/rest/v1',
  headers: {
    apikey: 'sb_publishable_5BPLZkbZvQXw7ZfrMWufFA_K77_nZxh',
    Authorization: 'Bearer sb_publishable_5BPLZkbZvQXw7ZfrMWufFA_K77_nZxh',
  },
});

const settings = reactive({
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

const loadSettings = async () => {
  try {
    const { data } = await api.get('/site_settings', { params: { id: 'eq.1', select: '*' } });
    if (data?.[0]) {
      Object.keys(data[0]).forEach(key => {
        if (data[0][key] !== null && data[0][key] !== undefined) {
          settings[key] = data[0][key];
        }
      });
    }
  } catch (err) {
    console.error('Ошибка загрузки настроек:', err);
  }
};

const goToRestaurants = () => router.visit('/restaurans');

const onImageError = (e) => {
  e.target.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="%23f0f0f0"><rect width="64" height="64" rx="12"/></svg>';
};

onMounted(loadSettings);
</script>

<template>
  <div class="landing">
    <!-- HERO -->
    <section class="hero" :style="{ backgroundImage: `url(${settings.hero_bg_image})` }">
      <div class="hero__overlay"></div>
      <div class="hero__content">
        <h1 class="hero__title">{{ settings.hero_title }}</h1>
        <p class="hero__subtitle">{{ settings.hero_subtitle }}</p>
        <button @click="goToRestaurants" class="hero__btn">Выбрать ресторан</button>
      </div>
      <div class="hero__scroll-hint"><span></span><span></span><span></span></div>
    </section>

    <!-- ПРЕИМУЩЕСТВА (с иконками вместо эмодзи) -->
    <section class="benefits">
      <h2 class="benefits__title">Почему FastBite</h2>
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

    <!-- CTA -->
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

/* HERO */
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
.hero__btn:hover { background: #e05e00; transform: translateY(-3px); box-shadow: 0 10px 32px rgba(255,107,0,0.5); }

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

/* ПРЕИМУЩЕСТВА */
.benefits { padding: 5rem 1.5rem; max-width: 1200px; margin: 0 auto; }
.benefits__title { font-size: 2.2rem; text-align: center; margin-bottom: 3rem; font-weight: 700; }
.benefits__grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; }
.benefit-card { background: #fff; border-radius: 24px; padding: 2.5rem 2rem; text-align: center; box-shadow: 0 4px 16px rgba(0,0,0,0.05); transition: .3s; }
.benefit-card:hover { transform: translateY(-8px); box-shadow: 0 12px 32px rgba(0,0,0,0.1); }
.benefit-card h3 { font-size: 1.2rem; margin-bottom: 0.6rem; font-weight: 700; }
.benefit-card p { color: #888; font-size: 0.95rem; }

/* Иконка преимущества */
.benefit-icon {
  width: 64px;
  height: 64px;
  margin-bottom: 1.2rem;
  object-fit: contain;
  display: block;
  margin-left: auto;
  margin-right: auto;
}

/* CTA */
.cta { background: linear-gradient(135deg, #fff5eb, #ffe6d5); padding: 5rem 1.5rem; text-align: center; }
.cta__content h2 { font-size: 2.2rem; font-weight: 700; margin-bottom: 0.8rem; }
.cta__content p { font-size: 1.1rem; color: #888; margin-bottom: 2rem; }

@media (max-width: 768px) {
  .hero__title { font-size: 2.2rem; }
  .hero__subtitle { font-size: 1rem; }
  .benefits__title, .cta__content h2 { font-size: 1.6rem; }
  .hero { background-attachment: scroll; }
}
</style>