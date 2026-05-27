-- Выполните в Supabase SQL Editor (один раз)

-- Профиль: флаг администратора, адрес, оплата
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS is_admin boolean DEFAULT false;
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS address text;
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS payment_card text;
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS payment_expiry text;
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS payment_cvc text;
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS birth_date date;

-- Блюда: состав, калории, БЖУ
ALTER TABLE products ADD COLUMN IF NOT EXISTS composition text;
ALTER TABLE products ADD COLUMN IF NOT EXISTS calories integer;
ALTER TABLE products ADD COLUMN IF NOT EXISTS proteins numeric(6,1);
ALTER TABLE products ADD COLUMN IF NOT EXISTS fats numeric(6,1);
ALTER TABLE products ADD COLUMN IF NOT EXISTS carbs numeric(6,1);
ALTER TABLE products ADD COLUMN IF NOT EXISTS rating numeric(3,1) DEFAULT 0;

-- Рестораны: время доставки (минуты, для сортировки)
ALTER TABLE restaurants ADD COLUMN IF NOT EXISTS delivery_time integer DEFAULT 35;
ALTER TABLE restaurants ADD COLUMN IF NOT EXISTS deleted_at timestamptz;
ALTER TABLE restaurants ADD COLUMN IF NOT EXISTS deletion_batch_id uuid;
ALTER TABLE products ADD COLUMN IF NOT EXISTS deleted_at timestamptz;
ALTER TABLE products ADD COLUMN IF NOT EXISTS deletion_batch_id uuid;

-- Отзывы
CREATE TABLE IF NOT EXISTS reviews (
  id bigserial PRIMARY KEY,
  product_id bigint REFERENCES products(id) ON DELETE CASCADE,
  user_id uuid REFERENCES auth.users(id) ON DELETE SET NULL,
  author_name text NOT NULL,
  rating smallint NOT NULL CHECK (rating >= 1 AND rating <= 5),
  comment text,
  created_at timestamptz DEFAULT now()
);

-- Отзывы о ресторанах
CREATE TABLE IF NOT EXISTS restaurant_reviews (
  id bigserial PRIMARY KEY,
  restaurant_id bigint REFERENCES restaurants(id) ON DELETE CASCADE,
  user_id uuid REFERENCES auth.users(id) ON DELETE SET NULL,
  author_name text NOT NULL,
  rating smallint NOT NULL CHECK (rating >= 1 AND rating <= 5),
  comment text,
  created_at timestamptz DEFAULT now()
);

-- FAQ
CREATE TABLE IF NOT EXISTS faq (
  id bigserial PRIMARY KEY,
  question text NOT NULL,
  answer text NOT NULL,
  sort_order integer DEFAULT 0,
  is_active boolean DEFAULT true
);

-- Заявки в поддержку (кнопка в шапке сайта)
CREATE TABLE IF NOT EXISTS support_requests (
  id bigserial PRIMARY KEY,
  name text NOT NULL,
  phone text NOT NULL,
  message text NOT NULL,
  user_id uuid REFERENCES auth.users(id) ON DELETE SET NULL,
  created_at timestamptz DEFAULT now()
);

-- Обратная связь
CREATE TABLE IF NOT EXISTS feedback_messages (
  id bigserial PRIMARY KEY,
  name text NOT NULL,
  email text NOT NULL,
  message text NOT NULL,
  created_at timestamptz DEFAULT now()
);

-- Заказы: привязка к пользователю и ресторану (если заказ из одного ресторана)
ALTER TABLE orders ADD COLUMN IF NOT EXISTS user_id uuid REFERENCES auth.users(id) ON DELETE SET NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS restaurant_id bigint REFERENCES restaurants(id) ON DELETE SET NULL;

-- Иконки сайта (таблица site_settings, строка id = 1)
-- ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS location_icon text;
-- 1) Загрузите iconLocation.svg в Supabase Storage (публичный bucket, например «icons»)
-- 2) Скопируйте публичный URL файла
-- 3) UPDATE site_settings SET location_icon = 'https://....supabase.co/storage/v1/object/public/icons/iconLocation.svg' WHERE id = 1;
-- ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS phone_icon text;
-- UPDATE site_settings SET phone_icon = 'https://....supabase.co/storage/v1/object/public/icons/iconPhone.svg' WHERE id = 1;

-- Примеры FAQ
-- INSERT INTO faq (question, answer, sort_order) VALUES
--   ('Как оформить заказ?', 'Выберите ресторан, добавьте блюда в корзину и оформите заказ. Для оформления нужна регистрация.', 1),
--   ('Сколько ждать доставку?', 'Обычно 30–45 минут в зависимости от ресторана и загрузки.', 2),
--   ('Можно ли оплатить картой?', 'Да, на этапе оформления заказа указываются данные карты (демо-режим).', 3);

-- Пример: назначить админа (замените UUID на id пользователя из auth.users)
-- UPDATE profiles SET is_admin = true WHERE id = 'ваш-uuid';

-- RLS (рекомендуется)
-- ALTER TABLE profiles ENABLE ROW LEVEL SECURITY;
-- CREATE POLICY profiles_select ON profiles FOR SELECT USING (auth.uid() = id);
-- CREATE POLICY profiles_update ON profiles FOR UPDATE USING (auth.uid() = id);
-- CREATE POLICY profiles_insert ON profiles FOR INSERT WITH CHECK (auth.uid() = id);

-- ALTER TABLE orders ENABLE ROW LEVEL SECURITY;
-- CREATE POLICY orders_select ON orders FOR SELECT USING (auth.uid() = user_id);

-- ALTER TABLE reviews ENABLE ROW LEVEL SECURITY;
-- CREATE POLICY reviews_select ON reviews FOR SELECT USING (true);
-- CREATE POLICY reviews_insert ON reviews FOR INSERT WITH CHECK (auth.uid() = user_id);
-- ALTER TABLE restaurant_reviews ENABLE ROW LEVEL SECURITY;
-- CREATE POLICY restaurant_reviews_select ON restaurant_reviews FOR SELECT USING (true);
-- CREATE POLICY restaurant_reviews_insert ON restaurant_reviews FOR INSERT WITH CHECK (auth.uid() = user_id);

-- Отзывы: время редактирования
ALTER TABLE reviews ADD COLUMN IF NOT EXISTS updated_at timestamptz;
ALTER TABLE restaurant_reviews ADD COLUMN IF NOT EXISTS updated_at timestamptz;

-- Заказы: мягкое удаление (архив в админке)
ALTER TABLE orders ADD COLUMN IF NOT EXISTS deleted_at timestamptz;

-- Полный скрипт с иконками и FAQ: database/supabase_apply.sql
