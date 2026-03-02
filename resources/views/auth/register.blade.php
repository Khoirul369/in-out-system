<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registrasi - In-Out System</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Inter, system-ui, sans-serif; background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 24px; font-size: 15px; }
    .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 32px; width: 100%; max-width: 500px; box-shadow: 0 16px 40px rgba(15, 23, 42, .08); }
    h1 { font-size: 24px; font-weight: 700; margin-bottom: 4px; }
    .sub { color: #64748b; font-size: 15px; margin-bottom: 24px; }
    .field { margin-bottom: 16px; }
    label { display: block; font-weight: 500; margin-bottom: 6px; font-size: 14px; }
    input, select { width: 100%; padding: 10px 12px; border: 1px solid #dbe3ee; border-radius: 10px; font-size: 15px; }
    input:focus, select:focus { outline: none; border-color: #2563eb; }
    .btn { width: 100%; padding: 11px; background: #2563eb; color: #fff; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 8px; }
    .btn:hover { background: #1d4ed8; }
    .error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 10px 14px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; }
    .link { text-align: center; margin-top: 16px; font-size: 14px; color: #64748b; }
    .link a { color: #2563eb; text-decoration: none; }
    .err-msg { color: #dc2626; font-size: 12px; margin-top: 4px; }
    .hidden { display: none; }
  </style>
</head>
<body>
  <div class="card">
    <h1>Daftar Akun</h1>
    <div class="sub">Buat akun baru untuk In-Out System</div>

    @if($errors->any())
      <div class="error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('register.post') }}">
      @csrf
      <div class="field">
        <label>Username</label>
        <input type="text" name="username" value="{{ old('username') }}" required>
        @error('username') <div class="err-msg">{{ $message }}</div> @enderror
      </div>

      <div class="field">
        <label>Nama Lengkap</label>
        <input type="text" name="nama" value="{{ old('nama') }}" required>
      </div>

      <div class="field">
        <label>Password</label>
        <input type="password" name="password" required>
        @error('password') <div class="err-msg">{{ $message }}</div> @enderror
      </div>

      <div class="field">
        <label>Konfirmasi Password</label>
        <input type="password" name="password_confirmation" required>
      </div>

      <div class="field">
        <label>Role</label>
        <select name="role" id="role" onchange="onRoleChange(this.value)">
          <option value="karyawan" {{ old('role') === 'karyawan' ? 'selected' : '' }}>Karyawan</option>
          <option value="hc" {{ old('role') === 'hc' ? 'selected' : '' }}>HC</option>
          <option value="it" {{ old('role') === 'it' ? 'selected' : '' }}>IT Support</option>
          <option value="doc" {{ old('role') === 'doc' ? 'selected' : '' }}>Doc Center</option>
          <option value="ga" {{ old('role') === 'ga' ? 'selected' : '' }}>GA</option>
          <option value="finance" {{ old('role') === 'finance' ? 'selected' : '' }}>Finance</option>
        </select>
      </div>

      <div class="field" id="field-id-karyawan">
        <label>ID Karyawan</label>
        <input type="text" name="id_karyawan" value="{{ old('id_karyawan') }}">
      </div>

      <div class="field">
        <label>Divisi / Posisi</label>
        <input type="text" name="divisi_posisi" value="{{ old('divisi_posisi') }}" placeholder="contoh: IT Support - Staff">
      </div>

      <div class="field hidden" id="field-admin-code">
        <label>Kode Admin</label>
        <input type="text" name="admin_code" value="{{ old('admin_code') }}">
        @error('admin_code') <div class="err-msg">{{ $message }}</div> @enderror
      </div>

      <button type="submit" class="btn">Daftar</button>
    </form>

    <div class="link">
      Sudah punya akun? <a href="{{ route('login') }}">Login di sini</a>
    </div>
  </div>

  <script>
    function onRoleChange(role) {
      const isKaryawan = role === 'karyawan';
      document.getElementById('field-id-karyawan').style.display = isKaryawan ? '' : 'none';
      document.getElementById('field-admin-code').style.display = isKaryawan ? 'none' : '';
    }
    onRoleChange(document.getElementById('role').value);
  </script>
</body>
</html>
