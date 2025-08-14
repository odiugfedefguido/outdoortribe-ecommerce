// public/javascript/theme.js
(function(){
  const root = document.documentElement;
  const btns = document.querySelectorAll('[data-theme-toggle]');
  // Leggi preferenza salvata
  const saved = localStorage.getItem('theme');
  if (saved === 'dark' || saved === 'light') {
    root.setAttribute('data-theme', saved);
  }
  // Inizializza icona
  function setIcon(el){
    const current = root.getAttribute('data-theme');
    const dark = current === 'dark' || (!current && window.matchMedia('(prefers-color-scheme: dark)').matches);
    el.textContent = dark ? '☀️' : '🌙';
    el.setAttribute('aria-label', dark ? 'Tema chiaro' : 'Tema scuro');
    el.title = dark ? 'Tema chiaro' : 'Tema scuro';
  }
  btns.forEach(btn => setIcon(btn));

  // Toggle
  btns.forEach(btn => {
    btn.addEventListener('click', () => {
      const current = root.getAttribute('data-theme');
      const next = current === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      localStorage.setItem('theme', next);
      btns.forEach(setIcon);
    });
  });
})();
