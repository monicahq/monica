const langFiles = import.meta.glob('../../lang/*.json');

function findLangFile(lang) {
  return langFiles[`../../lang/${lang}.json`];
}

export function resolveLang(lang) {
  const exact = findLangFile(lang);
  if (exact) return exact();

  const swapped = lang.replace(/[-_]/g, (ch) => (ch === '-' ? '_' : '-'));
  const alt = findLangFile(swapped);
  if (alt) return alt();

  const base = lang.split(/[-_]/)[0];
  if (base !== lang) {
    const baseLang = findLangFile(base);
    if (baseLang) return baseLang();
  }

  return findLangFile('en')();
}
