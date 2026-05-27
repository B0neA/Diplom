const STORAGE_KEY = 'lopat_podano_site_settings';

let memoryCache = null;

export async function loadSiteSettings() {
  if (memoryCache) {
    return memoryCache;
  }

  try {
    const raw = sessionStorage.getItem(STORAGE_KEY);
    if (raw) {
      memoryCache = JSON.parse(raw);
      window.axios.get('/api/site-settings').then(({ data }) => {
        memoryCache = data || {};
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(memoryCache));
      }).catch(() => {});
      return memoryCache;
    }
  } catch {
    /* ignore */
  }

  const { data } = await window.axios.get('/api/site-settings');
  memoryCache = data || {};
  try {
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(memoryCache));
  } catch {
    /* ignore */
  }
  return memoryCache;
}

export function applySiteSettings(target, data) {
  if (!data) return;
  Object.keys(data).forEach((key) => {
    if (data[key] != null && data[key] !== '') {
      target[key] = data[key];
    }
  });
}
