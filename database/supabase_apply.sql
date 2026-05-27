-- =============================================================================
-- Лопать Подано — миграция для Supabase SQL Editor
-- =============================================================================
-- 1. Откройте Supabase → SQL → New query
-- 2. Загрузите SVG в Storage → bucket «icons» (Public bucket)
-- 3. В блоке DO $$ ниже замените base_url на ваш публичный URL папки icons
-- 4. Выполните весь скрипт (Run)
-- =============================================================================

-- ─── 1. Колонки для отзывов, заказов, архива ───────────────────────────────

ALTER TABLE reviews ADD COLUMN IF NOT EXISTS updated_at timestamptz;
ALTER TABLE restaurant_reviews ADD COLUMN IF NOT EXISTS updated_at timestamptz;

ALTER TABLE orders ADD COLUMN IF NOT EXISTS deleted_at timestamptz;

ALTER TABLE restaurants ADD COLUMN IF NOT EXISTS deleted_at timestamptz;
ALTER TABLE restaurants ADD COLUMN IF NOT EXISTS deletion_batch_id uuid;
ALTER TABLE products ADD COLUMN IF NOT EXISTS deleted_at timestamptz;
ALTER TABLE products ADD COLUMN IF NOT EXISTS deletion_batch_id uuid;

-- ─── 2. Колонки site_settings (иконки, favicon) ───────────────────────────

ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS favicon_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS favicon_url text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS redact_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS redact_icon_url text;

ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS question_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS shoppingbasket_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS benefit_time_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS card_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS reset_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS lock_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS present_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS phone_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS star_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS time_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS save_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS alarm_icon text;

ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS location_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS delivery_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS success_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS list_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS grid_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS sort_desc_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS sort_asc_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS close_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS restaurant_placeholder text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS logo_icon text;
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS logo_url text;

-- Строка настроек (если ещё нет)
INSERT INTO site_settings (id)
VALUES (1)
ON CONFLICT (id) DO NOTHING;

-- ─── 3. URL иконок (замените base_url!) ─────────────────────────────────────

DO $$
DECLARE
  -- Публичный URL bucket «icons» БЕЗ слэша в конце, например:
  -- https://cuibxmcjdkgjffmmzwgd.supabase.co/storage/v1/object/public/icons
  base_url text := 'https://cuibxmcjdkgjffmmzwgd.supabase.co/storage/v1/object/public/icons';
BEGIN
  UPDATE site_settings SET
    favicon_icon       = base_url || '/Favicon.svg',
    redact_icon        = base_url || '/redactIcon.svg',
    question_icon      = base_url || '/question.svg',
    shoppingbasket_icon = base_url || '/shoppingbasket.svg',
    benefit_time_icon  = base_url || '/benefit-time.svg',
    card_icon          = base_url || '/iconCard.svg',
    reset_icon         = base_url || '/reset.svg',
    lock_icon          = base_url || '/lock.svg',
    present_icon       = base_url || '/present.svg',
    phone_icon         = base_url || '/iconPhone.svg',
    star_icon          = base_url || '/star-icon.svg',
    time_icon          = base_url || '/time-icon.svg',
    save_icon          = base_url || '/save.svg',
    alarm_icon         = base_url || '/alarm.svg',
    location_icon      = base_url || '/iconLocation.svg'
  WHERE id = 1;

  IF NOT FOUND THEN
    RAISE EXCEPTION 'Нет строки site_settings с id = 1. Создайте её вручную или проверьте таблицу.';
  END IF;
END $$;

-- ─── 4. FAQ (актуальный текст + ключи иконок для главной) ───────────────────

ALTER TABLE faq ADD COLUMN IF NOT EXISTS icon_key text;

-- Перезапись FAQ (если нужно сохранить старые — закомментируйте DELETE)
DELETE FROM faq;

INSERT INTO faq (id, question, answer, sort_order, is_active, icon_key) VALUES
  (1, 'Как оформить заказ?',
   'Выберите ресторан, добавьте блюда в корзину. Для оформления нужна регистрация.', 1, true, 'shoppingbasket_icon'),
  (2, 'Сколько ждать доставку?',
   'Обычно 30–45 минут. В редких случаях (пробки, погода) — до 60 минут.', 2, true, 'benefit_time_icon'),
  (3, 'Можно ли оплатить картой?',
   'Да, на этапе оформления заказа (демо-режим).', 3, true, 'card_icon'),
  (4, 'Можно ли изменить или отменить заказ?',
   'Да, пока ресторан не начал готовить (первые 5 минут после оформления). В личном кабинете → Мои заказы → Отменить.', 4, true, 'reset_icon'),
  (5, 'Зачем нужна регистрация?',
   'Чтобы сохранять историю заказов, адреса доставки и получать персональные скидки.', 5, true, 'lock_icon'),
  (6, 'Есть ли у вас промокоды и скидки?',
   'Да! Промокоды можно ввести при оформлении заказа. Попробуйте PIZZA20 (скидка 20% на первый заказ). Также дарим скидку 10% в День Рождения — она автоматически применяется к сумме блюд в ваш праздник!', 6, true, 'present_icon'),
  (7, 'Как связаться с поддержкой?',
   E'Email: support@lopatpodano.ru\nТелефон: +7 (800) 123-45-67 (ежедневно с 9:00 до 23:00)', 7, true, 'phone_icon'),
  (8, 'Почему некоторых ресторанов нет в списке?',
   'Рестораны могут быть временно недоступны или не работать в ваш район.', 8, true, 'shoppingbasket_icon'),
  (9, 'Как оставить отзыв о ресторане?',
   'После получения заказа вам придёт ссылка на оценку в личном кабинете.', 9, true, 'star_icon'),
  (10, 'В какое время работает доставка?',
   'С 9:00 до 23:00. Ночные заказы временно не принимаются.', 10, true, 'time_icon'),
  (11, 'Мои данные в безопасности?',
   'Да, все данные хранятся в зашифрованном виде. Платёжные данные не сохраняются.', 11, true, 'save_icon'),
  (12, 'Что делать, если заказ не пришёл?',
   'Проверьте статус в личном кабинете или свяжитесь с поддержкой. Мы решим проблему за 15 минут.', 12, true, 'alarm_icon');

-- Сброс счётчика id для FAQ (чтобы следующие INSERT шли с 13)
SELECT setval(
  pg_get_serial_sequence('faq', 'id'),
  COALESCE((SELECT MAX(id) FROM faq), 1)
);

-- ─── 5. Заявки в поддержку (кнопка в шапке) ─────────────────────────────────

CREATE TABLE IF NOT EXISTS support_requests (
  id bigserial PRIMARY KEY,
  name text NOT NULL,
  phone text NOT NULL,
  message text NOT NULL,
  user_id uuid REFERENCES auth.users(id) ON DELETE SET NULL,
  created_at timestamptz DEFAULT now()
);

-- ─── 6. Администратор (раскомментируйте и подставьте UUID) ──────────────────

-- UPDATE profiles SET is_admin = true WHERE id = '00000000-0000-0000-0000-000000000000';

-- ─── 6. Проверка ───────────────────────────────────────────────────────────

SELECT id, favicon_icon, redact_icon, question_icon, phone_icon
FROM site_settings
WHERE id = 1;

SELECT id, sort_order, icon_key, left(question, 40) AS question
FROM faq
ORDER BY sort_order;
