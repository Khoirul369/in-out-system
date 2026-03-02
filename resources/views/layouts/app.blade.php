<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'In-Out System') - In-Out System</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --primary: #2563eb; --primary-dark: #1d4ed8; --danger: #dc2626;
      --success: #16a34a; --warning: #d97706; --bg: #f1f5f9;
      --card: #ffffff; --border: #e2e8f0; --text: #0f172a;
      --muted: #64748b; --radius: 12px; --shadow: 0 6px 20px rgba(15, 23, 42, .06);
    }
    body { font-family: Inter, system-ui, -apple-system, sans-serif; background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); color: var(--text); font-size: 15px; line-height: 1.6; }
    .header { background: rgba(255,255,255,.9); backdrop-filter: blur(8px); border-bottom: 1px solid var(--border); padding: 6px 24px; position: sticky; top: 0; z-index: 100; }
    .header-inner { max-width: 1240px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; min-height: 70px; gap: 16px; }
    .brand h1 { font-size: 21px; font-weight: 700; color: var(--text); letter-spacing: -.2px; }
    .brand .sub { font-size: 13px; color: var(--muted); }
    .nav { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
    .nav-user { font-size: 13px; color: var(--muted); margin-left: 4px; white-space: nowrap; }
    .nav > a { padding: 7px 12px; border-radius: 10px; color: var(--muted); text-decoration: none; font-size: 14px; transition: all .15s; display: inline-block; font-weight: 500; }
    .nav > a:hover, .nav > a.active { background: #e2e8f0; color: var(--text); }
    .dropdown { position: relative; display: inline-block; }
    .dropdown-toggle { padding: 7px 12px; border-radius: 10px; color: var(--muted); font-size: 14px; cursor: pointer; display: flex; align-items: center; gap: 4px; background: none; border: none; font-weight: 500; position: relative; }
    .dropdown-toggle:hover, .dropdown-toggle.active { background: #e2e8f0; color: var(--text); }
    .dropdown-toggle svg { width: 12px; height: 12px; transition: transform .15s; }
    .nav-badge-dot { position: absolute; top: 6px; right: 6px; width: 8px; height: 8px; background: #dc2626; border-radius: 50%; border: 2px solid #fff; }
    .dropdown:hover .dropdown-toggle svg { transform: rotate(180deg); }
    .dropdown-menu {
      position: absolute;
      top: calc(100% + 6px);
      left: 0;
      background: #fff;
      border: 1px solid var(--border);
      border-radius: 10px;
      box-shadow: var(--shadow);
      min-width: 220px;
      z-index: 200;
      padding: 6px;
      opacity: 0;
      visibility: hidden;
      transform: translateY(6px);
      pointer-events: none;
      transition: opacity .16s ease, transform .16s ease, visibility 0s linear .24s;
    }
    .dropdown:hover .dropdown-menu,
    .dropdown:focus-within .dropdown-menu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
      pointer-events: auto;
      transition-delay: 0s, 0s, 0s;
    }
    .dropdown-menu a { display: block; padding: 8px 12px; color: var(--text); text-decoration: none; font-size: 14px; border-radius: 6px; }
    .dropdown-menu a:hover { background: #f1f5f9; }
    .main { max-width: 1240px; margin: 26px auto 34px; padding: 0 24px; }
    .card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; margin-bottom: 20px; box-shadow: var(--shadow); }
    .card-title { font-size: 18px; font-weight: 700; margin-bottom: 16px; letter-spacing: -.1px; }
    .page-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
    .page-head > div:first-child { min-width: 0; }
    .page-head-narrow { max-width: 560px; margin-left: 0; margin-right: auto; }
    .mt-8 { margin-top: 8px; }
    .mt-12 { margin-top: 12px; }
    .mt-16 { margin-top: 16px; }
    .inline-form { display: inline; }
    .page-title { font-size: 24px; font-weight: 700; letter-spacing: -.2px; line-height: 1.25; margin: 0; }
    .page-sub { margin-top: 6px; color: var(--muted); font-size: 14px; line-height: 1.4; }
    .empty-state { border: 1px dashed var(--border); border-radius: var(--radius); background: #f8fafc; padding: 24px; color: var(--muted); font-size: 15px; text-align: center; }
    .alert { padding: 14px 18px; border-radius: var(--radius); margin-bottom: 16px; font-size: 14px; line-height: 1.5; }
    .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
    .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
    .alert-warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
    .alert-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
    .field { margin-bottom: 16px; }
    .field label { display: block; font-weight: 500; margin-bottom: 6px; font-size: 14px; }
    .field input, .field textarea, .field select { width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: var(--radius); font-size: 15px; background: #fff; transition: border-color .15s; }
    .field textarea { resize: vertical; }
    .field input:focus, .field textarea:focus, .field select:focus { outline: none; border-color: var(--primary); }
    .field .error-msg { color: var(--danger); font-size: 13px; margin-top: 4px; }
    .field .muted { color: var(--muted); font-size: 13px; margin-top: 4px; }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: var(--radius); font-size: 14px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; transition: all .15s; }
    .btn-primary { background: var(--primary); color: #fff; }
    .btn-primary:hover { background: var(--primary-dark); }
    .btn-danger { background: var(--danger); color: #fff; }
    .btn-danger:hover { background: #b91c1c; }
    .btn-success { background: var(--success); color: #fff; }
    .btn-outline { background: #fff; color: var(--text); border: 1px solid var(--border); }
    .btn-outline:hover { background: #f8fafc; border-color: #cbd5e1; }
    .btn-sm { padding: 4px 10px; font-size: 13px; }
    .table-wrap { overflow-x: auto; border: 1px solid var(--border); border-radius: var(--radius); }
    .table { width: 100%; border-collapse: collapse; min-width: 760px; }
    .table th { text-align: left; padding: 12px 14px; font-size: 13px; font-weight: 700; color: var(--muted); border-bottom: 1px solid var(--border); text-transform: uppercase; letter-spacing: .5px; background: #f8fafc; }
    .table td { padding: 12px 14px; font-size: 14px; border-bottom: 1px solid var(--border); vertical-align: middle; }
    .table tr:last-child td { border-bottom: none; }
    .table tr:hover td { background: #f8fafc; }
    .badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; letter-spacing: .2px; }
    .badge-pending { background: #fef3c7; color: #92400e; }
    .badge-approved { background: #d1fae5; color: #065f46; }
    .badge-approved_hc { background: #dbeafe; color: #1e40af; }
    .badge-rejected { background: #fee2e2; color: #991b1b; }
    .badge-done { background: #d1fae5; color: #065f46; }
    .badge-default { background: #f1f5f9; color: var(--muted); }
    .progress-wrap { display: flex; align-items: center; gap: 8px; }
    .progress-bar { flex: 1; height: 8px; background: var(--border); border-radius: 4px; overflow: hidden; }
    .progress-fill { height: 100%; background: var(--primary); border-radius: 4px; transition: width .3s; }
    .progress-text { font-size: 14px; color: var(--muted); white-space: nowrap; }
    .grid { display: grid; gap: 20px; }
    .grid-2 { grid-template-columns: repeat(2, 1fr); }
    .grid-3 { grid-template-columns: repeat(3, 1fr); }
    .metric-grid { display: grid; gap: 12px; grid-template-columns: repeat(4, 1fr); margin-bottom: 16px; }
    .metric-card { background: #fff; border: 1px solid var(--border); border-radius: var(--radius); padding: 16px; box-shadow: var(--shadow); }
    .metric-label { color: var(--muted); font-size: 14px; margin-bottom: 4px; }
    .metric-value { font-size: 22px; font-weight: 700; line-height: 1.2; }
    .checklist-item { display: flex; align-items: flex-start; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border); }
    .checklist-item:last-child { border-bottom: none; }
    .checklist-item input[type=checkbox] { width: 16px; height: 16px; margin-top: 2px; cursor: pointer; }

    /* ===== TOAST ===== */
    #toast-container { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10000; pointer-events: none; }
    .toast { background: #fff; border: 1px solid var(--border); border-radius: 18px; padding: 34px 36px 30px; width: 680px; max-width: 94vw; box-shadow: 0 20px 60px rgba(0,0,0,.24); pointer-events: all; text-align: center; }
    .toast.success { border-top: 6px solid var(--success); }
    .toast.error   { border-top: 6px solid var(--danger); }
    .toast.info    { border-top: 6px solid var(--primary); }
    .toast-icon  { display: inline-flex; align-items: center; justify-content: center; width: 58px; height: 58px; border-radius: 999px; font-size: 28px; margin: 0 auto 14px; background: #f8fafc; border: 1px solid #e2e8f0; }
    .toast-body  { width: 100%; }
    .toast-title { font-weight: 700; font-size: 24px; line-height: 1.25; margin-bottom: 8px; }
    .toast-msg   { font-size: 16px; color: var(--muted); line-height: 1.65; margin-bottom: 20px; }
    .toast-actions { display: flex; justify-content: center; }
    .toast-close-btn { cursor: pointer; background: #0f172a; color: #fff; border: none; border-radius: 12px; padding: 10px 20px; font-size: 13px; font-weight: 600; min-width: 120px; }
    .toast-close-btn:hover { background: #1e293b; }
    .toast-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.40); z-index: 9999; }

    /* ===== CONFIRM ===== */
    .confirm-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.40); z-index: 10001; display: flex; align-items: center; justify-content: center; }
    .confirm-box { background: #fff; border: 1px solid var(--border); border-radius: 18px; padding: 34px 36px 30px; width: 680px; max-width: 94vw; box-shadow: 0 20px 60px rgba(0,0,0,.24); text-align: center; }
    .confirm-icon  { display: inline-flex; align-items: center; justify-content: center; width: 58px; height: 58px; border-radius: 999px; font-size: 28px; margin: 0 auto 14px; background: #f8fafc; border: 1px solid #e2e8f0; }
    .confirm-title { font-size: 24px; font-weight: 700; line-height: 1.25; margin-bottom: 8px; color: var(--text); }
    .confirm-msg   { font-size: 16px; color: var(--muted); margin-bottom: 20px; line-height: 1.65; }
    .confirm-actions { display: flex; gap: 12px; justify-content: center; }
    .confirm-actions .btn { min-width: 160px; justify-content: center; padding: 11px 18px; font-size: 14px; border-radius: 12px; }

    @media (max-width: 768px) {
      .main { padding: 0 16px; }
      .header { padding: 8px 14px; }
      .header-inner { align-items: flex-start; min-height: 74px; padding: 8px 0; }
      .brand .sub { margin-top: 2px; }
      .nav { gap: 4px; }
      .grid-2, .grid-3 { grid-template-columns: 1fr; }
      .metric-grid { grid-template-columns: repeat(2, 1fr); }
      .page-title { font-size: 20px; }
      .toast { width: 92vw; padding: 24px 20px 22px; }
      .toast-title { font-size: 22px; }
      .toast-msg { font-size: 15px; }
      .confirm-box { width: 92vw; padding: 24px 20px 22px; }
      .confirm-title { font-size: 22px; }
      .confirm-msg { font-size: 15px; }
    }
  </style>
  @yield('styles')
</head>
<body>
  <div id="toast-container"></div>

  <header class="header">
    <div class="header-inner">
      <div class="brand">
        <h1>In-Out System</h1>
      </div>

      @if(session('user'))
        @php
          $sessionUser = session('user');
          $navUser     = \App\Models\User::find($sessionUser['id']);
          $isAdmin     = $navUser->canAccessChecklist();
          $isHc        = $navUser->isHc();
          $canVerifyHc = $navUser->canVerifyHcResign();
          $isPm        = $navUser->isPm();
          $isKaryawan  = $navUser->isKaryawan();
          $canSeeResignList = $isAdmin || $navUser->isReadonlyHcObserver();
          $navChecklistPendingCount = 0;
          $navPmPendingCount = 0;
          $navHcPendingCount = 0;
          if ($isAdmin) {
            $dept = $navUser->getDepartment();
            $checklistSeenUntil = session('checklist_seen_until');
            $navChecklistPendingCount = \App\Models\ResignRequest::where('workflow_stage', \App\Models\ResignRequest::STAGE_TO_HC)
              ->where('employees_id', '!=', $navUser->id)
              ->when($checklistSeenUntil, function ($q) use ($checklistSeenUntil) {
                $q->where('created_at', '>', $checklistSeenUntil);
              })
              ->count();
          }
          if ($isPm) {
            $subordinateIds = \App\Models\User::where('pm_id', $navUser->id)->pluck('id');
            $pmSeenUntil = session('pm_seen_until');
            $navPmPendingCount = \App\Models\ResignRequest::whereIn('employees_id', $subordinateIds)
              ->where('workflow_stage', \App\Models\ResignRequest::STAGE_TO_PM)
              ->when($pmSeenUntil, function ($q) use ($pmSeenUntil) {
                $q->where('created_at', '>', $pmSeenUntil);
              })
              ->count();
          }
          if ($canVerifyHc) {
            $hcSeenUntil = session('hc_seen_until');
            $navHcPendingCount = \App\Models\ResignRequest::where('workflow_stage', \App\Models\ResignRequest::STAGE_TO_HC_APPROVAL)
              ->where('employees_id', '!=', $navUser->id)
              ->when($hcSeenUntil, function ($q) use ($hcSeenUntil) {
                $q->where('created_at', '>', $hcSeenUntil);
              })
              ->count();
          }
          $navTotalPendingCount = $navChecklistPendingCount + $navPmPendingCount + $navHcPendingCount;
        @endphp
        <nav class="nav">
          <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>

          <div class="dropdown">
            <button class="dropdown-toggle {{ request()->routeIs('resign.*','approval.*','checklist.*') ? 'active' : '' }}" id="nav-pengajuan-btn">
              Pengajuan
              @if($navTotalPendingCount > 0)
                <span class="nav-badge-dot"></span>
              @endif
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>
            <div class="dropdown-menu">
              @if($isKaryawan)
                <a href="{{ route('resign.create') }}">Ajukan Resign</a>
              @endif
              @if($isPm)
                <a href="{{ route('approval.pm') }}">Approval PM</a>
              @endif
              @if($isAdmin)
                <a href="{{ route('checklist.index') }}">Checklist Dokumen</a>
              @endif
              @if($canVerifyHc)
                <a href="{{ route('approval.hc') }}">Verifikasi HC</a>
              @endif
              @if($canSeeResignList)
                <a href="{{ route('resign.list') }}">List Pengajuan Resign</a>
              @endif
            </div>
          </div>

          @if($isAdmin)
            <a href="{{ route('checklist.master.index') }}" class="{{ request()->routeIs('checklist.master.*') ? 'active' : '' }}">Master Checklist</a>
          @endif

          <form method="POST" action="{{ route('logout') }}" class="inline-form">
            @csrf
            <button type="submit" class="btn btn-outline btn-sm">Logout</button>
          </form>
          <span class="nav-user">{{ session('user.nama') }}</span>
        </nav>
      @endif
    </div>
  </header>

  <main class="main">
    @yield('content')
  </main>

  <script>
    /* ===== TOAST ===== */
    var __activeToastCloser = null;
    var __lastToastMeta = { key: null, at: 0 };

    function showToast(message, type, duration, onClose) {
      type     = (type     !== undefined) ? type     : 'success';
      duration = (duration !== undefined) ? duration : 0;
      onClose  = (onClose  !== undefined) ? onClose  : null;

      var now = Date.now();
      var toastKey = type + '::' + String(message);
      if (__lastToastMeta.key === toastKey && (now - __lastToastMeta.at) < 1200) {
        return;
      }
      __lastToastMeta = { key: toastKey, at: now };

      if (typeof __activeToastCloser === 'function') {
        __activeToastCloser(false);
      }

      var icons  = { success: '✅', error: '❌', info: 'ℹ️' };
      var titles = { success: 'Berhasil', error: 'Gagal', info: 'Info' };

      var overlay = document.createElement('div');
      overlay.className = 'toast-overlay';
      document.body.appendChild(overlay);

      var toast = document.createElement('div');
      toast.className = 'toast ' + type;
      toast.innerHTML =
        '<span class="toast-icon">' + (icons[type] || '✅') + '</span>' +
        '<div class="toast-body">' +
          '<div class="toast-title">' + (titles[type] || 'Info') + '</div>' +
          '<div class="toast-msg">'   + message + '</div>' +
          '<div class="toast-actions">' +
            '<button class="toast-close-btn" id="_toastCloseBtn">Tutup</button>' +
          '</div>' +
        '</div>';
      document.getElementById('toast-container').appendChild(toast);

      var closed = false;
      function _close(callOnClose) {
        if (callOnClose === undefined) callOnClose = true;
        if (closed) return;
        closed = true;
        toast.remove();
        overlay.remove();
        if (__activeToastCloser === _close) {
          __activeToastCloser = null;
        }
        if (callOnClose && onClose) onClose();
      }
      __activeToastCloser = _close;

      document.getElementById('_toastCloseBtn').onclick = function() { _close(true); };
      overlay.onclick = function() { _close(true); };
      if (duration > 0) setTimeout(function() { _close(true); }, duration);
    }

    /* ===== CONFIRM ===== */
    function showConfirm(message, onConfirm, icon, title) {
      icon  = icon  || '⚠️';
      title = title || 'Konfirmasi';

      var overlay = document.createElement('div');
      overlay.className = 'confirm-overlay';
      overlay.innerHTML =
        '<div class="confirm-box">' +
          '<div class="confirm-icon">'  + icon    + '</div>' +
          '<div class="confirm-title">' + title   + '</div>' +
          '<div class="confirm-msg">'   + message + '</div>' +
          '<div class="confirm-actions">' +
            '<button class="btn btn-outline" id="_confirmBatal">Batal</button>' +
            '<button class="btn btn-danger"  id="_confirmLanjut">Ya, Lanjutkan</button>' +
          '</div>' +
        '</div>';
      document.body.appendChild(overlay);

      document.getElementById('_confirmBatal').onclick  = function() { overlay.remove(); };
      document.getElementById('_confirmLanjut').onclick = function() { overlay.remove(); onConfirm(); };
    }

    /* ===== FLASH SESSION ===== */
    @if(session('redirect_after_toast'))
      showToast(
        @json(session('success') ?? 'Berhasil'),
        'success',
        0,
        function() { window.location.href = @json(session('redirect_after_toast')); }
      );
    @elseif(session('success'))
      showToast(@json(session('success')), 'success', 0);
    @endif
    @if(session('error'))
      showToast(@json(session('error')), 'error', 0);
    @endif

    /* ===== NOTIFICATION MARK SEEN ===== */
    @if($navTotalPendingCount > 0)
      document.addEventListener('DOMContentLoaded', function() {
        var pengajuanBtn = document.getElementById('nav-pengajuan-btn');
        var checklistLinks = document.querySelectorAll('.dropdown-menu a[href*="checklist"]');
        var pmLinks = document.querySelectorAll('.dropdown-menu a[href*="approval.pm"]');
        var hcLinks = document.querySelectorAll('.dropdown-menu a[href*="approval.hc"]');
        var dot = document.querySelector('.nav-badge-dot');
        var hasChecklistPending = {{ $navChecklistPendingCount }} > 0;
        var hasPmPending = {{ $navPmPendingCount }} > 0;
        var hasHcPending = {{ $navHcPendingCount }} > 0;
        
        var markChecklistSeen = function() {
          if (hasChecklistPending) {
            fetch('{{ route("checklist.mark-seen") }}', {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
              }
            }).then(function() {
              hasChecklistPending = false;
              checkAndRemoveDot();
            }).catch(function() {});
          }
        };
        
        var markPmSeen = function() {
          if (hasPmPending) {
            fetch('{{ route("approval.pm.mark-seen") }}', {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
              }
            }).then(function() {
              hasPmPending = false;
              checkAndRemoveDot();
            }).catch(function() {});
          }
        };
        
        var markHcSeen = function() {
          if (hasHcPending) {
            fetch('{{ route("approval.hc.mark-seen") }}', {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
              }
            }).then(function() {
              hasHcPending = false;
              checkAndRemoveDot();
            }).catch(function() {});
          }
        };
        
        var checkAndRemoveDot = function() {
          if (dot && !hasChecklistPending && !hasPmPending && !hasHcPending) {
            dot.remove();
          }
        };
        
        if (pengajuanBtn) {
          pengajuanBtn.addEventListener('click', function() {
            markChecklistSeen();
            markPmSeen();
            markHcSeen();
          });
        }
        checklistLinks.forEach(function(link) {
          link.addEventListener('click', markChecklistSeen);
        });
        pmLinks.forEach(function(link) {
          link.addEventListener('click', markPmSeen);
        });
        hcLinks.forEach(function(link) {
          link.addEventListener('click', markHcSeen);
        });
      });
    @endif
  </script>

  @yield('scripts')
</body>
</html>
