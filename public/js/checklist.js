(() => {
  const runtimeMap = {};
  const requiredMsg = 'Checklist wajib diisi Keterangan sebelum disimpan maupun dicentang.';

  const getRuntime = (resignId) => {
    if (!runtimeMap[resignId]) {
      runtimeMap[resignId] = { saving: false, pending: false, pendingShowToast: false };
    }
    return runtimeMap[resignId];
  };

  const setFormReadOnly = (resignId, readOnly) => {
    const form = document.getElementById(`form-${resignId}`);
    if (!form) return;
    form.querySelectorAll('input[type="checkbox"][name*="[done]"]').forEach((el) => { el.disabled = readOnly; });
    form.querySelectorAll('textarea[name*="[keterangan]"]').forEach((el) => { el.disabled = readOnly; });
    form.querySelectorAll('.checklist-edit-item-btn').forEach((el) => { el.style.display = readOnly ? '' : 'none'; });
  };

  const showSavedMessage = (resignId, show) => {
    const el = document.getElementById(`saved-msg-${resignId}`);
    if (el) el.style.display = show ? 'inline' : 'none';
  };

  const showChecklistError = (resignId, message) => {
    const el = document.getElementById(`checklist-error-${resignId}`);
    if (!el) return;
    el.textContent = message || 'Gagal menyimpan checklist.';
    el.style.display = 'block';
  };

  const hideChecklistError = (resignId) => {
    const el = document.getElementById(`checklist-error-${resignId}`);
    if (el) {
      el.textContent = '';
      el.style.display = 'none';
    }
  };

  const updateProgressUI = (resignId) => {
    const form = document.getElementById(`form-${resignId}`);
    if (!form) return;
    const checkboxes = form.querySelectorAll('input[type="checkbox"][name*="[done]"]');
    const hiddenDones = form.querySelectorAll('input[type="hidden"][name*="[done]"][value="1"]');
    const total = checkboxes.length + hiddenDones.length;
    let done = 0;
    checkboxes.forEach((cb) => { if (cb.checked) done++; });
    done += hiddenDones.length;
    const percent = total > 0 ? Math.round((done / total) * 100) : 0;
    const fill = document.getElementById(`progress-fill-${resignId}`);
    const text = document.getElementById(`progress-text-${resignId}`);
    if (fill) fill.style.width = `${percent}%`;
    if (text) text.textContent = `${done}/${total} (${percent}%)`;
  };

  const canEnableSaveButton = (resignId) => {
    const form = document.getElementById(`form-${resignId}`);
    if (!form) return false;
    // Sedang mode edit (ada wrap keterangan tampil) → boleh simpan
    const visibleWrap = form.querySelector('.checklist-keterangan-wrap[data-ket-wrap="1"]');
    if (visibleWrap && visibleWrap.style.display !== 'none') return true;
    let hasDone = false;
    const checkboxes = form.querySelectorAll('input[type="checkbox"][name*="[done]"]:not([disabled])');
    for (const cb of checkboxes) {
      if (!cb.checked) continue;
      hasDone = true;
      const row = cb.closest('.checklist-row');
      const ketInput = row ? row.querySelector('textarea[name*="[keterangan]"], input[name*="[keterangan]"]') : null;
      const val = ketInput ? ketInput.value.trim() : '';
      if (!val) return false;
    }
    const hiddenDones = form.querySelectorAll('input[type="hidden"][name*="[done]"][value="1"]');
    for (const h of hiddenDones) {
      const itemKey = (h.name.match(/items\[([^\]]+)\]\[done\]/) || [])[1];
      if (!itemKey) continue;
      hasDone = true;
      const itemBlock = form.querySelector(`.checklist-item[data-item-key="${itemKey}"]`);
      const ketInput = itemBlock ? itemBlock.querySelector('textarea[name*="[keterangan]"], input[name*="[keterangan]"]') : null;
      const val = ketInput ? ketInput.value.trim() : '';
      if (!val) return false;
    }
    return hasDone;
  };

  const updateSaveButtonState = (resignId) => {
    const form = document.getElementById(`form-${resignId}`);
    if (!form) return;
    form.querySelectorAll('[data-ket-wrap="1"]').forEach((wrap) => {
      if (wrap.style.display === 'none') return;
      const ta = wrap.querySelector('textarea[name*="[keterangan]"]');
      const btn = wrap.querySelector('.checklist-item-save-btn');
      if (!btn || !ta) return;

      // Cari itemKey untuk mengetahui status checkbox (done / tidak)
      const itemBlock = wrap.closest('.checklist-item');
      const itemKey = itemBlock ? itemBlock.dataset.itemKey : null;
      let isChecked = false;
      if (itemKey) {
        const cb = form.querySelector(`input[type="checkbox"][name="items[${itemKey}][done]"]`);
        if (cb) isChecked = cb.checked;
      }

      // Jika sedang dicentang (checked) → tetap wajib isi keterangan.
      // Jika sedang di-uncheck (checkbox off / tidak ada checkbox) → boleh simpan walau keterangan kosong.
      if (isChecked) {
        btn.disabled = !ta.value.trim();
      } else {
        btn.disabled = false;
      }
    });
  };

  const saveChecklist = (resignId, showToastOnSuccess, fromButtonClick) => {
    const form = document.getElementById(`form-${resignId}`);
    if (!form) return;
    const runtime = getRuntime(resignId);
    if (runtime.saving) {
      runtime.pending = true;
      runtime.pendingShowToast = runtime.pendingShowToast || !!showToastOnSuccess;
      return;
    }
    runtime.saving = true;

    const updateUrl = form.dataset.updateUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const data = new FormData(form);

    hideChecklistError(resignId);

    fetch(updateUrl, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
      body: data,
    })
      .then((r) => {
        return r.json().then((res) => ({ ok: r.ok, status: r.status, res }));
      })
      .then(({ ok, status, res }) => {
        if (!ok || !res.success) {
          const msg = (res && res.message) ? res.message : (status === 422 ? 'Data tidak valid.' : status === 403 ? 'Akses ditolak.' : 'Gagal menyimpan checklist.');
          if (typeof showToast === 'function') showToast(msg, 'error', 0);
          showChecklistError(resignId, msg);
          return;
        }
        if (showToastOnSuccess && typeof showToast === 'function') {
          const by = res.saved_by ? ' Oleh: ' + res.saved_by : '';
          const text = res.all_done ? 'Semua checklist selesai!' + by : 'Checklist berhasil disimpan.' + by;
          if (fromButtonClick) {
            showToast(text, 'success', 0, function() {
              window.location.reload();
            });
          } else {
            showToast(text, 'success', 0);
          }
        } else if (fromButtonClick) {
          window.location.reload();
        }
      })
      .catch(() => {
        const msg = 'Gagal menyimpan checklist. Periksa koneksi atau coba lagi.';
        if (typeof showToast === 'function') showToast(msg, 'error', 0);
        showChecklistError(resignId, msg);
      })
      .finally(() => {
        runtime.saving = false;
        if (runtime.pending) {
          const shouldShow = runtime.pendingShowToast;
          runtime.pending = false;
          runtime.pendingShowToast = false;
          saveChecklist(resignId, shouldShow);
        }
      });
  };

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-checklist-form="1"]').forEach((form) => {
      const resignId = form.dataset.resignId;
      if (!resignId) return;

      updateProgressUI(resignId);
      updateSaveButtonState(resignId);

      form.addEventListener('submit', (e) => {
        e.preventDefault();
        if (!canEnableSaveButton(resignId)) {
          if (typeof showToast === 'function') showToast(requiredMsg, 'error', 0);
          showChecklistError(resignId, requiredMsg);
          return;
        }
        hideChecklistError(resignId);
        saveChecklist(resignId, true, true);
      });

      form.querySelectorAll('input[type="checkbox"][name*="[done]"]').forEach((cb) => {
        cb.addEventListener('change', () => {
          hideChecklistError(resignId);
          const row = cb.closest('.checklist-row');
          const wrap = row ? row.querySelector('[data-ket-wrap="1"]') : null;
          if (wrap) wrap.style.display = cb.checked ? 'block' : 'none';
          updateSaveButtonState(resignId);
          if (cb.checked) {
            const ketInput = row ? row.querySelector('textarea[name*="[keterangan]"]') : null;
            if (ketInput) ketInput.focus();
          }
        });
      });

      form.querySelectorAll('textarea[name*="[keterangan]"]').forEach((ta) => {
        ta.addEventListener('input', () => { hideChecklistError(resignId); updateSaveButtonState(resignId); });
        ta.addEventListener('change', () => updateSaveButtonState(resignId));
      });

      form.querySelectorAll('.checklist-edit-item-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
          if (btn.disabled) return;
          const itemKey = btn.dataset.itemKey;
          const itemBlock = form.querySelector(`.checklist-item[data-item-key="${itemKey}"]`);
          if (!itemBlock) return;
          const row = itemBlock.querySelector('.checklist-row');
          const savedBlock = itemBlock.querySelector('[data-done-block="1"]');
          const hiddenInput = itemBlock.querySelector('input[name*="[keterangan]"]');
          if (!row || !savedBlock || !hiddenInput) return;
          const currentVal = hiddenInput.value || '';
          // Ganti ikon centang + hidden(done=1) dengan checkbox (bisa uncheck) + hidden(done=0)
          const icon = row.querySelector('.checklist-done-icon');
          const hiddenDone = row.querySelector('input[name*="[done]"]');
          if (icon) icon.remove();
          if (hiddenDone) hiddenDone.remove();
          const hiddenZero = document.createElement('input');
          hiddenZero.type = 'hidden';
          hiddenZero.name = `items[${itemKey}][done]`;
          hiddenZero.value = '0';
          const cb = document.createElement('input');
          cb.type = 'checkbox';
          cb.name = `items[${itemKey}][done]`;
          cb.value = '1';
          cb.checked = true;
          cb.dataset.resignId = resignId;
          cb.dataset.itemKey = itemKey;
          row.insertBefore(hiddenZero, row.firstChild);
          row.insertBefore(cb, row.firstChild);
          cb.addEventListener('change', () => {
            updateSaveButtonState(resignId);
            updateProgressUI(resignId);
          });
          savedBlock.style.display = 'none';
          hiddenInput.remove();
          const wrap = document.createElement('div');
          wrap.className = 'checklist-keterangan-wrap';
          wrap.setAttribute('data-ket-wrap', '1');
          wrap.style.display = 'block';
          wrap.innerHTML = `<label>Keterangan</label><textarea name="items[${itemKey}][keterangan]" placeholder="Tambahkan keterangan..." data-resign-id="${resignId}" data-item-key="${itemKey}" rows="2"></textarea><div class="checklist-item-actions mt-2"><button type="submit" class="btn btn-primary btn-sm checklist-item-save-btn">Simpan</button></div>`;
          savedBlock.parentNode.insertBefore(wrap, savedBlock);
          const textarea = wrap.querySelector('textarea');
          textarea.value = currentVal;
          textarea.addEventListener('input', () => updateSaveButtonState(resignId));
          textarea.addEventListener('change', () => updateSaveButtonState(resignId));
          textarea.focus();
          updateSaveButtonState(resignId);
          updateProgressUI(resignId);
        });
      });

      form.querySelectorAll('[data-ket-wrap="1"]').forEach((wrap) => {
        const cb = form.querySelector(`input[data-item-key="${wrap.closest('.checklist-item')?.dataset.itemKey}"][name*="[done]"]`);
        if (cb && cb.checked) wrap.style.display = 'block';
      });
    });
  });
})();
