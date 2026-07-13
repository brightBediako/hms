(() => {
  const sidebar = document.getElementById('app-sidebar');
  const openBtn = document.getElementById('sidebar-open');
  const closeBtn = document.getElementById('sidebar-close');
  const backdrop = document.getElementById('sidebar-backdrop');

  if (!sidebar) return;

  const open = () => {
    sidebar.classList.remove('-translate-x-full');
    if (backdrop) {
      backdrop.hidden = false;
      backdrop.classList.remove('hidden');
    }
  };

  const close = () => {
    sidebar.classList.add('-translate-x-full');
    if (backdrop) {
      backdrop.classList.add('hidden');
      backdrop.hidden = true;
    }
  };

  openBtn?.addEventListener('click', open);
  closeBtn?.addEventListener('click', close);
  backdrop?.addEventListener('click', close);
})();
