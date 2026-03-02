(() => {
  const updateCount = (textarea) => {
    const targetSelector = textarea.dataset.charCountTarget;
    if (!targetSelector) return;
    const target = document.querySelector(targetSelector);
    if (!target) return;
    target.textContent = String(textarea.value.length);
  };

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('textarea[data-char-counter="1"]').forEach((textarea) => {
      updateCount(textarea);
      textarea.addEventListener('input', () => updateCount(textarea));
    });

    const form = document.getElementById('resign-submit-form');
    const btn = document.getElementById('resign-submit-btn');
    if (form && btn) {
      const submitText = btn.dataset.submitText || 'Kirim Pengajuan';
      const loadingText = btn.dataset.loadingText || 'Mengirim...';
      form.addEventListener('submit', () => {
        if (btn.disabled) return;
        btn.disabled = true;
        btn.textContent = loadingText;
      });
    }
  });
})();
