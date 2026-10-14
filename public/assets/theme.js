(() => {
  const root = document.documentElement;
  const stored = localStorage.getItem('phonebook-theme');
  if (stored === 'light' || stored === 'dark') root.dataset.theme = stored;
  else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) root.dataset.theme = 'light';

  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-theme-toggle]');
    if (!button) return;
    const next = root.dataset.theme === 'light' ? 'dark' : 'light';
    root.dataset.theme = next;
    localStorage.setItem('phonebook-theme', next);
  });
})();
