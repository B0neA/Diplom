import { Head } from '@inertiajs/vue3';

export function truncate(str, max) {
  const s = String(str || '').trim();
  if (s.length <= max) return s;
  return s.slice(0, max - 1).trimEnd() + '…';
}

export function usePageSeo(title, description) {
  const t = truncate(title, 60);
  const d = truncate(description, 160);
  return { title: t, description: d };
}

export { Head };
