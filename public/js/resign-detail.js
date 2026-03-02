(() => {
  document.addEventListener('DOMContentLoaded', () => {
    const btnCancel = document.getElementById('btn-cancel-resign');
    if (btnCancel) {
      btnCancel.onclick = () => {
        showConfirm(
          'Pengajuan resign Anda akan dibatalkan dan tidak dapat dikembalikan.',
          () => {
            document.getElementById('form-cancel')?.submit();
          },
          '🗑️',
          'Batalkan Pengajuan?'
        );
      };
    }

    const btnPmApprove = document.getElementById('btn-pm-approve');
    if (btnPmApprove) {
      btnPmApprove.onclick = () => {
        const actionInput = document.getElementById('pm-action-val');
        if (actionInput) actionInput.value = 'approved';
        document.getElementById('form-pm-action')?.submit();
      };
    }

    const btnPmReject = document.getElementById('btn-pm-reject');
    if (btnPmReject) {
      btnPmReject.onclick = () => {
        showConfirm(
          'Pengajuan resign ini akan ditolak.',
          () => {
            const actionInput = document.getElementById('pm-action-val');
            if (actionInput) actionInput.value = 'rejected';
            document.getElementById('form-pm-action')?.submit();
          },
          '❌',
          'Tolak Pengajuan?'
        );
      };
    }

    const btnHcApprove = document.getElementById('btn-hc-approve');
    if (btnHcApprove) {
      btnHcApprove.onclick = () => {
        const actionInput = document.getElementById('hc-action-val');
        if (actionInput) actionInput.value = 'approved';
        document.getElementById('form-hc-action')?.submit();
      };
    }

    const btnHcReject = document.getElementById('btn-hc-reject');
    if (btnHcReject) {
      btnHcReject.onclick = () => {
        showConfirm(
          'Pengajuan resign ini tidak akan diverifikasi.',
          () => {
            const actionInput = document.getElementById('hc-action-val');
            if (actionInput) actionInput.value = 'rejected';
            document.getElementById('form-hc-action')?.submit();
          },
          '❌',
          'Tolak Verifikasi?'
        );
      };
    }
  });
})();
