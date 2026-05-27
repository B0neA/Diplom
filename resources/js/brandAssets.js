/** Оранжевый градиент бренда «Лопать Подано» */
export const BRAND_GRADIENT_CSS =
  'linear-gradient(135deg, #ffc46b 0%, #ff9f2f 35%, #ff6b00 65%, #e65100 100%)';

const BRAND_ICON_PATH =
  'M32 8c-2 0-3.5 1.2-4.2 3.1L22 28h-4.5c-1.9 0-3.5 1.6-3.5 3.5v3c0 1.9 1.6 3.5 3.5 3.5H20l-1.2 8.2c-.2 1.4.9 2.8 2.4 2.8h21.6c1.5 0 2.6-1.4 2.4-2.8L44 38h2.5c1.9 0 3.5-1.6 3.5-3.5v-3c0-1.9-1.6-3.5-3.5-3.5H42l-5.8-16.9C35.5 9.2 34 8 32 8zm0 6.2 4.6 13.3H27.4L32 14.2zM24 33h16v3H24v-3z';

const GRADIENT_STOPS = `
      <stop offset="0%" stop-color="#ffc46b"/>
      <stop offset="40%" stop-color="#ff9f2f"/>
      <stop offset="70%" stop-color="#ff6b00"/>
      <stop offset="100%" stop-color="#e65100"/>`;

/** URL логотипа из настроек (тот же, что в хедере) */
export function getBrandLogoUrl(settings = {}) {
  return String(settings.logo_url || settings.logo_icon || '').trim();
}

/** SVG favicon: градиент + маска (как в хедере) */
export function buildMaskedGradientFaviconSvg(imageHref = null) {
  const maskInner = imageHref
    ? `<image href="${imageHref}" width="64" height="64" preserveAspectRatio="xMidYMid meet"/>`
    : `<path fill="white" d="${BRAND_ICON_PATH}"/>`;

  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
  <defs>
    <linearGradient id="brandGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      ${GRADIENT_STOPS}
    </linearGradient>
    <mask id="logoMask">${maskInner}</mask>
  </defs>
  <rect width="64" height="64" fill="url(#brandGrad)" mask="url(#logoMask)"/>
</svg>`;

  return `data:image/svg+xml,${encodeURIComponent(svg)}`;
}

function buildBrandSvg({ filled = true } = {}) {
  const gradient = filled
    ? `<defs>
        <linearGradient id="brandGrad" x1="0%" y1="0%" x2="100%" y2="100%">
          ${GRADIENT_STOPS}
        </linearGradient>
      </defs>
      <rect width="64" height="64" rx="14" fill="url(#brandGrad)"/>
      <path fill="#fff" fill-opacity="0.95" d="${BRAND_ICON_PATH}"/>`
    : `<path fill="#000" d="${BRAND_ICON_PATH}"/>`;

  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">${gradient}</svg>`;
}

export function brandIconSvgDataUrl(filled = true) {
  return `data:image/svg+xml,${encodeURIComponent(buildBrandSvg({ filled }))}`;
}

/** Маска (силуэт) для CSS mask + градиентный фон в хедере */
export function brandIconMaskDataUrl() {
  return `data:image/svg+xml,${encodeURIComponent(buildBrandSvg({ filled: false }))}`;
}

export function brandFaviconSvgDataUrl() {
  return buildMaskedGradientFaviconSvg(null);
}

/** Экранирование URL для CSS mask-image */
export function cssMaskUrl(url) {
  if (!url) return '';
  const safe = String(url).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
  return `url("${safe}")`;
}

function blobToDataUri(blob) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = reject;
    reader.readAsDataURL(blob);
  });
}

function loadImageDataUri(url) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = () => {
      const canvas = document.createElement('canvas');
      canvas.width = 64;
      canvas.height = 64;
      const ctx = canvas.getContext('2d');
      if (!ctx) {
        reject(new Error('no canvas'));
        return;
      }
      ctx.drawImage(img, 0, 0, 64, 64);
      resolve(canvas.toDataURL('image/png'));
    };
    img.onerror = reject;
    img.src = url;
  });
}

/** Favicon из того же logo_icon, что и в шапке */
export async function resolveBrandFaviconHref(settings = {}) {
  const logoUrl = getBrandLogoUrl(settings);
  if (!logoUrl) {
    return brandFaviconSvgDataUrl();
  }

  try {
    const res = await fetch(logoUrl, { mode: 'cors' });
    if (res.ok) {
      const blob = await res.blob();
      const dataUri = await blobToDataUri(blob);
      return buildMaskedGradientFaviconSvg(dataUri);
    }
  } catch {
    /* fetch/CORS — пробуем через canvas */
  }

  try {
    const dataUri = await loadImageDataUri(logoUrl);
    return buildMaskedGradientFaviconSvg(dataUri);
  } catch {
    return brandFaviconSvgDataUrl();
  }
}
