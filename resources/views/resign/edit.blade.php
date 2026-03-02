@extends('layouts.app')
@section('title', 'Edit Pengajuan Resign')

@section('styles')
<style>
  .resign-edit-wrap { max-width: 720px; margin: 0 auto; }
  .resign-edit-actions { display: flex; gap: 12px; margin-top: 8px; }
  .resign-edit-file-note { font-size: 13px; color: #64748b; margin-bottom: 8px; }
  .req-mark { color: #dc2626; }
  @media (max-width: 768px) {
    .resign-edit-actions { flex-direction: column; }
    .resign-edit-actions .btn { justify-content: center; }
  }
</style>
@endsection

@section('content')
<div class="page-head page-head-narrow">
  <div>
    <div class="page-title">Edit Pengajuan Resign</div>
    <div class="page-sub">Perbarui detail pengajuan selama status masih pending.</div>
  </div>
  <div>
    <a href="{{ route('resign.detail', $resign->id) }}" class="btn btn-outline btn-sm">← Kembali</a>
  </div>
</div>

<div class="card resign-edit-wrap">
  <div class="card-title">Form Edit Pengunduran Diri</div>

  @if($errors->any())
    <div class="alert alert-error">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('resign.update', $resign->id) }}" enctype="multipart/form-data">
    @csrf

    <div class="field">
      <label>Alasan Pengunduran Diri <span class="req-mark">*</span></label>
      <textarea
        name="alasan"
        rows="4"
        required
        minlength="50"
        data-char-counter="1"
        data-char-count-target="#char-count">{{ old('alasan', $resign->reason) }}</textarea>
      <div class="muted">Karakter saat ini: <span id="char-count">0</span> (min. 50)</div>
      @error('alasan') <div class="error-msg">{{ $message }}</div> @enderror
    </div>

    <div class="field">
      <label>Tanggal Terakhir Bekerja <span class="req-mark">*</span></label>
      <input type="date" name="tanggal_berhenti" value="{{ old('tanggal_berhenti', $resign->last_date->format('Y-m-d')) }}" required min="{{ date('Y-m-d', strtotime('+1 day')) }}">
      @error('tanggal_berhenti') <div class="error-msg">{{ $message }}</div> @enderror
    </div>

    <div class="field">
      <label>Deskripsi Tambahan</label>
      <textarea name="deskripsi" rows="3">{{ old('deskripsi', $resign->description) }}</textarea>
    </div>

    <div class="field">
      <label>Ganti File Surat Resign (opsional)</label>
      @if($resign->resign_filename)
        <div class="resign-edit-file-note">File saat ini: <strong>{{ $resign->resign_filename }}</strong></div>
      @endif
      <input type="file" name="resign_file" accept=".pdf,.doc,.docx">
      <div class="muted">Kosongkan jika tidak ingin mengganti file. Format: PDF, DOC, DOCX. Maks 5MB.</div>
      @error('resign_file') <div class="error-msg">{{ $message }}</div> @enderror
    </div>

    <div class="resign-edit-actions">
      <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      <a href="{{ route('resign.detail', $resign->id) }}" class="btn btn-outline">Batal</a>
    </div>
  </form>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/resign-form.js') }}"></script>
@endsection
