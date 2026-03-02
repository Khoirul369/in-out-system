<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - In-Out System</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Inter, system-ui, sans-serif; background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; font-size: 15px; }
    .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 32px; width: 100%; max-width: 420px; box-shadow: 0 16px 40px rgba(15, 23, 42, .08); }
    h1 { font-size: 24px; font-weight: 700; margin-bottom: 4px; }
    .sub { color: #64748b; font-size: 15px; margin-bottom: 24px; }
    .field { margin-bottom: 16px; }
    label { display: block; font-weight: 500; margin-bottom: 6px; font-size: 14px; }
    input { width: 100%; padding: 10px 12px; border: 1px solid #dbe3ee; border-radius: 10px; font-size: 15px; }
    input:focus { outline: none; border-color: #2563eb; }
    .btn { width: 100%; padding: 11px; background: #2563eb; color: #fff; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 8px; }
    .btn:hover { background: #1d4ed8; }
    .error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 10px 14px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; }
    .success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 10px 14px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; }
    .link { text-align: center; margin-top: 16px; font-size: 14px; color: #64748b; }
    .link a { color: #2563eb; text-decoration: none; }
    .err-msg { color: #dc2626; font-size: 12px; margin-top: 4px; }
  </style>
</head>
<body>
  <div class="card">
    <h1>In-Out System</h1>
    <div class="sub">Administrasi Karyawan Masuk & Keluar</div>

    @if(session('success'))
      <div class="success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
      <div class="error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.post') }}" id="login-form">
      @csrf
      <div class="field">
        <label>Username</label>
        <input type="text" name="username" id="username-input" list="username-history" value="{{ old('username') }}" placeholder="Masukkan username" autofocus required>
        <datalist id="username-history"></datalist>
        @error('username') <div class="err-msg">{{ $message }}</div> @enderror
      </div>
      <button type="submit" class="btn">Masuk</button>
    </form>

    <div class="link">
      Belum punya akun? <a href="{{ route('register') }}">Daftar di sini</a>
    </div>
  </div>
  <script>
    (function () {
      var storageKey = 'inout:login_username_history_v2';
      var legacyStorageKey = 'inout:login_username_history';
      var maxHistory = 10;
      var usernameInput = document.getElementById('username-input');
      var loginForm = document.getElementById('login-form');
      var datalist = document.getElementById('username-history');
      if (!usernameInput || !loginForm || !datalist) return;

      // Reset log username versi lama.
      localStorage.removeItem(legacyStorageKey);

      function readHistory() {
        try {
          var raw = localStorage.getItem(storageKey);
          var parsed = raw ? JSON.parse(raw) : [];
          return Array.isArray(parsed) ? parsed.filter(Boolean) : [];
        } catch (e) {
          return [];
        }
      }

      function writeHistory(list) {
        localStorage.setItem(storageKey, JSON.stringify(list.slice(0, maxHistory)));
      }

      function renderHistoryOptions(list) {
        datalist.innerHTML = '';
        list.forEach(function (username) {
          var opt = document.createElement('option');
          opt.value = username;
          datalist.appendChild(opt);
        });
      }

      function saveUsername(username) {
        if (!username) return;
        var history = readHistory().filter(function (item) { return item !== username; });
        history.unshift(username);
        writeHistory(history);
      }

      // Isi otomatis dari browser saat field kosong.
      var history = readHistory();
      renderHistoryOptions(history);
      if (!usernameInput.value && history.length > 0) usernameInput.value = history[0];

      loginForm.addEventListener('submit', function () {
        saveUsername(usernameInput.value.trim());
      });
    })();
  </script>
</body>
</html>
