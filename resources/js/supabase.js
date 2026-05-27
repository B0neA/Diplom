import { createClient } from '@supabase/supabase-js';
import axios from 'axios';

const supabaseUrl = 'https://cuibxmcjdkgjffmmzwgd.supabase.co';
const supabaseKey = 'sb_publishable_5BPLZkbZvQXw7ZfrMWufFA_K77_nZxh';

export const supabase = createClient(supabaseUrl, supabaseKey, {
  auth: {
    persistSession: true,
    autoRefreshToken: true,
    detectSessionInUrl: true,
    storage: typeof window !== 'undefined' ? window.localStorage : undefined,
  },
});

let authInitialized = false;
let cachedSession = null;
/** @type {Array<(session: import('@supabase/supabase-js').Session | null) => void>} */
const authWaiters = [];

function finishAuthInit(session) {
  cachedSession = session;
  if (authInitialized) {
    return;
  }
  authInitialized = true;
  const waiters = authWaiters.splice(0, authWaiters.length);
  waiters.forEach((resolve) => resolve(session));
}

if (typeof window !== 'undefined') {
  supabase.auth.onAuthStateChange((event, session) => {
    cachedSession = session;
    if (event === 'INITIAL_SESSION') {
      finishAuthInit(session);
      return;
    }
    if (authInitialized) {
      cachedSession = session;
    } else if (['SIGNED_IN', 'SIGNED_OUT'].includes(event)) {
      finishAuthInit(session);
    }
  });

  supabase.auth.getSession().then(({ data: { session } }) => {
    cachedSession = session;
    if (session && !authInitialized) {
      finishAuthInit(session);
    }
  });
}

/** Дождаться восстановления сессии из storage (важно при F5 / перезагрузке). */
export function waitForAuthInit(timeoutMs = 5000) {
  if (authInitialized) {
    return Promise.resolve(cachedSession);
  }

  return new Promise((resolve) => {
    const done = (session) => {
      clearTimeout(timer);
      resolve(session);
    };

    authWaiters.push(done);

    const timer = setTimeout(async () => {
      if (authInitialized) {
        resolve(cachedSession);
        return;
      }
      const { data: { session } } = await supabase.auth.getSession();
      finishAuthInit(session);
      resolve(session);
    }, timeoutMs);
  });
}

export const api = axios.create({
  baseURL: `${supabaseUrl}/rest/v1`,
  headers: {
    apikey: supabaseKey,
    Authorization: `Bearer ${supabaseKey}`,
    'Content-Type': 'application/json',
    Prefer: 'return=representation',
  },
});

/** REST-клиент с JWT текущего пользователя (для profiles, orders и т.д.) */
export async function getAuthApi() {
  const session = await waitForAuthInit();
  const token = session?.access_token || supabaseKey;
  return axios.create({
    baseURL: `${supabaseUrl}/rest/v1`,
    headers: {
      apikey: supabaseKey,
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
      Prefer: 'return=representation',
    },
  });
}

export async function getCurrentSession() {
  await waitForAuthInit();
  return cachedSession;
}

export async function getCurrentUser() {
  const session = await waitForAuthInit();
  if (session?.user) {
    return session.user;
  }
  try {
    const { data: { user } } = await supabase.auth.getUser();
    return user ?? null;
  } catch {
    return null;
  }
}

export async function requireAuth(redirectPath = '/auth') {
  const user = await getCurrentUser();
  if (!user) {
    const next = typeof window !== 'undefined' ? window.location.pathname : '/';
    window.location.href = `${redirectPath}?redirect=${encodeURIComponent(next)}`;
    return null;
  }
  return user;
}

export async function loadProfile(userId) {
  const authApi = await getAuthApi();
  const { data } = await authApi.get('/profiles', {
    params: { id: `eq.${userId}`, select: '*' },
  });
  return data?.[0] || null;
}

/** Создаёт профиль только если его ещё нет (не перезаписывает адрес и оплату). */
export async function ensureProfile(user, extra = {}) {
  const existing = await loadProfile(user.id);
  if (existing) return existing;

  const authApi = await getAuthApi();
  const payload = {
    id: user.id,
    full_name: extra.full_name || user.user_metadata?.full_name || '',
    phone: extra.phone || user.user_metadata?.phone || '',
    address: extra.address || '',
    payment_card: extra.payment_card || '',
    payment_expiry: extra.payment_expiry || '',
    payment_cvc: extra.payment_cvc || '',
    birth_date: extra.birth_date || null,
  };
  await authApi.post('/profiles', payload);
  return payload;
}

export async function saveProfile(userId, fields) {
  const authApi = await getAuthApi();
  const { data } = await authApi.patch('/profiles', fields, {
    params: { id: `eq.${userId}` },
  });
  return Array.isArray(data) ? data[0] : data;
}

export async function isAdmin(userId) {
  const profile = await loadProfile(userId);
  return Boolean(profile?.is_admin);
}

export function formatPhoneDisplay(phone) {
  if (!phone) return '';
  const numbers = String(phone).replace(/\D/g, '');
  if (numbers.length >= 11) {
    const n = numbers.startsWith('7') ? numbers : '7' + numbers.slice(-10);
    return `+7 (${n.slice(1, 4)}) ${n.slice(4, 7)}-${n.slice(7, 9)}-${n.slice(9, 11)}`;
  }
  if (numbers.length === 10) {
    return `+7 (${numbers.slice(0, 3)}) ${numbers.slice(3, 6)}-${numbers.slice(6, 8)}-${numbers.slice(8, 10)}`;
  }
  return phone;
}

export async function getLaravelApi() {
  const session = await waitForAuthInit();
  return axios.create({
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      ...(session?.access_token ? { Authorization: `Bearer ${session.access_token}` } : {}),
    },
  });
}

export function formatPhoneInput(value) {
  let numbers = String(value).replace(/\D/g, '').slice(0, 11);
  if (numbers.startsWith('8')) numbers = '7' + numbers.slice(1);
  if (numbers.startsWith('9') && numbers.length === 10) numbers = '7' + numbers;
  return numbers.length
    ? `+7 (${numbers.slice(1, 4)}) ${numbers.slice(4, 7)}-${numbers.slice(7, 9)}-${numbers.slice(9, 11)}`
    : '';
}
