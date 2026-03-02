@extends('layouts.app')
@section('title', 'Ajukan Resign')

@section('styles')
<style>
  .resign-form-wrap { max-width: 560px; margin-left: 0; margin-right: auto; padding: 18px 20px !important; }
  .resign-form-wrap .card-title { margin-bottom: 12px; }
  .resign-form-wrap .field { margin-bottom: 12px; }
  .resign-form-wrap .alert { padding: 12px 14px; margin-bottom: 12px; }
  .resign-form-actions { display: flex; gap: 10px; margin-top: 6px; }
  .req-mark { color: var(--danger); }
  .existing-link { margin-left: 12px; }
  @media (max-width: 768px) {
    .resign-form-actions { flex-direction: column; }
    .resign-form-actions .btn { justify-content: center; }
  }
</style>
@endsection

@section('content')
<div class="page-head page-head-narrow">
  <div>
    <div class="page-title">Ajukan Resign</div>
    <div class="page-sub">Isi formulir pengunduran diri dan unggah surat resign resmi.</div>
  </div>
</div>

<div class="card resign-form-wrap">
  <div class="card-title">Form Pengunduran Diri</div>

  @if($existingRequest)
    <div class="alert alert-warning">
      Anda sudah memiliki pengajuan resign yang sedang diproses (ID #{{ $existingRequest->id }}).
      Silakan tunggu hingga selesai sebelum mengajukan yang baru.
      <a href="{{ route('resign.detail', $existingRequest->id) }}" class="btn btn-outline btn-sm existing-link">Lihat</a>
    </div>
  @else
    <div class="alert alert-info">
      @if($hasPm)
        Pengajuan akan dikirim ke <strong>PM Anda</strong> untuk approval.
      @else
        Anda tidak memiliki PM. Pengajuan akan langsung dikirim ke <strong>HC</strong>.
      @endif
    </div>

    @if($errors->any())
      <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('resign.submit') }}" enctype="multipart/form-data" id="resign-submit-form">
      @csrf

      <div class="field">
        <label>Alasan Pengunduran Diri <span class="req-mark">*</span></label>
        <textarea
          name="alasan"
          rows="4"
          required
          minlength="50"
          placeholder="Tuliskan alasan pengunduran diri Anda (minimal 50 karakter)"
          data-char-counter="1"
          data-char-count-target="#char-count">{{ old('alasan') }}</textarea>
        <div class="muted">Karakter saat ini: <span id="char-count">0</span> (min. 50)</div>
        @error('alasan') <div class="error-msg">{{ $message }}</div> @enderror
      </div>

      <div class="field">
        <label>Tanggal Terakhir Bekerja <span class="req-mark">*</span></label>
        <input type="date" name="tanggal_berhenti" value="{{ old('tanggal_berhenti') }}" required min="{{ date('Y-m-d', strtotime('+1 day')) }}">
        @error('tanggal_berhenti') <div class="error-msg">{{ $message }}</div> @enderror
      </div>

      <div class="field">
        <label>Deskripsi Tambahan</label>
        <textarea name="deskripsi" rows="3" placeholder="Opsional">{{ old('deskripsi') }}</textarea>
      </div>

      <div class="field">
        <label>Upload Surat Resign @if($requiresResignFile)<span class="req-mark">*</span>@endif</label>
        <input type="file" name="resign_file" accept=".pdf,.doc,.docx" @if($requiresResignFile) required @endif>
        <div class="muted">
          Format: PDF, DOC, DOCX. Maks 5MB.
          @if(!$requiresResignFile)
            (Boleh dikosongkan untuk status kontrak/magang.)
          @endif
        </div>
        @error('resign_file') <div class="error-msg">{{ $message }}</div> @enderror
      </div>

      <div class="resign-form-actions">
        <button type="submit" class="btn btn-primary" id="resign-submit-btn" data-submit-text="Kirim Pengajuan" data-loading-text="Mengirim...">Kirim Pengajuan</button>
        <a href="{{ route('dashboard') }}" class="btn btn-outline">Batal</a>
      </div>
    </form>
  @endif
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/resign-form.js') }}"></script>
@endsection
