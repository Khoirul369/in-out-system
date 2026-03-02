(() => {
  document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('master-grid');
    const formCard = document.getElementById('master-form-card');
    const form = document.getElementById('master-form');
    const formTitle = document.getElementById('master-form-title');
    const itemLabel = document.getElementById('master-item-label');
    const defaultPic = document.getElementById('master-default-pic');
    const editItemId = document.getElementById('master-edit-item-id');
    const submitBtn = document.getElementById('master-submit-btn');
    const cancelEditBtn = document.getElementById('master-cancel-edit');
    const toggleFormBtn = document.getElementById('btn-master-toggle-form');
    if (!grid || !formCard || !form || !formTitle || !itemLabel || !defaultPic || !editItemId || !submitBtn || !cancelEditBtn) {
      return;
    }

    const storeAction = formCard.dataset.storeAction || form.getAttribute('action');
    const originalFormTitle = 'Tambah Checklist Item';
    const originalSubmitText = 'Tambah';

    const labelLockMsg = document.getElementById('master-label-lock-msg');

    const setFieldsLocked = (locked) => {
      itemLabel.disabled = locked;
      defaultPic.disabled = locked;
      if (labelLockMsg) labelLockMsg.classList.toggle('master-hide', !locked);
    };

    const resetToCreateMode = () => {
      form.setAttribute('action', storeAction);
      formTitle.textContent = originalFormTitle;
      submitBtn.textContent = originalSubmitText;
      editItemId.value = '';
      itemLabel.value = '';
      defaultPic.value = '';
      setFieldsLocked(false);
      const activeRadio = form.querySelector('input[name="is_active"][value="1"]');
      if (activeRadio) activeRadio.checked = true;
      cancelEditBtn.classList.add('master-hide');
      itemLabel.focus();
    };

    document.querySelectorAll('.btn-master-edit').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id || '';
        const label = btn.dataset.itemLabel || '';
        const pic = btn.dataset.defaultPic || '';
        const isActive = btn.dataset.isActive === '0' ? '0' : '1';
        const action = btn.dataset.updateAction || '';
        const usedAndChecked = btn.dataset.usedAndChecked === '1';
        if (!id || !action) return;

        form.setAttribute('action', action);
        formTitle.textContent = 'Edit Checklist Item';
        submitBtn.textContent = 'Update';
        editItemId.value = id;
        itemLabel.value = label;
        defaultPic.value = pic;
        setFieldsLocked(usedAndChecked);
        const activeRadio = form.querySelector(`input[name="is_active"][value="${isActive}"]`);
        if (activeRadio) activeRadio.checked = true;
        cancelEditBtn.classList.remove('master-hide');
        if (formCard.classList.contains('master-hide')) {
          formCard.classList.remove('master-hide');
          grid.classList.remove('form-hidden');
          if (toggleFormBtn) toggleFormBtn.textContent = 'Sembunyikan Form';
        }
        itemLabel.focus();
      });
    });

    cancelEditBtn.addEventListener('click', resetToCreateMode);

    document.querySelectorAll('.btn-master-delete').forEach((btn) => {
      btn.addEventListener('click', () => {
        const formId = btn.dataset.deleteForm;
        if (!formId) return;
        const deleteForm = document.getElementById(formId);
        if (!deleteForm) return;

        showConfirm(
          'Hapus item master checklist ini?',
          () => deleteForm.submit(),
          '🗑️',
          'Hapus Item'
        );
      });
    });

    if (toggleFormBtn) {
      toggleFormBtn.addEventListener('click', () => {
        const hidden = formCard.classList.toggle('master-hide');
        grid.classList.toggle('form-hidden', hidden);
        toggleFormBtn.textContent = hidden ? 'Tampilkan Form' : 'Sembunyikan Form';
      });
    }

    // Jika mode edit dari server, jangan reset.
    if (!editItemId.value) {
      resetToCreateMode();
    }

    // Jika form sedang hidden dari state awal (mis. setelah klik toggle + refresh parsial),
    // paksa tabel melebar penuh.
    if (formCard.classList.contains('master-hide')) {
      grid.classList.add('form-hidden');
    }
  });
})();
