const STORAGE_KEY = 'lopat_podano_site_settings';

let memoryCache = null;
let pendingRequest = null;

export async function loadSiteSettings() {
  if (memoryCache) {
    return memoryCache;
  }
  if (pendingRequest) {
    return pendingRequest;
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

  pendingRequest = window.axios.get('/api/site-settings')
    .then(({ data }) => {
      memoryCache = data || {};
      try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(memoryCache));
      } catch {
        /* ignore */
      }
      return memoryCache;
    })
    .finally(() => {
      pendingRequest = null;
    });

  return pendingRequest;
}

export function applySiteSettings(target, data) {
  if (!data) return;
  Object.keys(data).forEach((key) => {
    if (data[key] != null && data[key] !== '') {
      target[key] = data[key];
    }
  });
}
