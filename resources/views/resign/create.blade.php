@extends('layouts.app')
@section('title', 'Ajukan Resign')

@section('styles')
<style>
  .resign-page-grid { display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; max-width: 960px; margin-left: 0; margin-right: auto; }
  .resign-form-wrap { padding: 18px 20px !important; }
  .resign-form-wrap .card-title { margin-bottom: 12px; }
  .resign-form-wrap .field { margin-bottom: 12px; }
  .resign-form-wrap .alert { padding: 12px 14px; margin-bottom: 12px; }
  .resign-form-actions { display: flex; gap: 10px; margin-top: 6px; }
  .req-mark { color: var(--danger); }
  .existing-link { margin-left: 12px; }
  .leave-balance-card { padding: 20px; position: sticky; top: 90px; }
  .leave-balance-card .card-title { margin-bottom: 14px; font-size: 16px; }
  .leave-balance-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border); font-size: 14px; }
  .leave-balance-row:last-child { border-bottom: none; }
  .leave-balance-row .label { color: var(--muted); }
  .leave-balance-row .value { font-weight: 600; color: var(--text); }
  .leave-balance-sisa { font-size: 28px; font-weight: 700; color: var(--primary); margin: 8px 0 4px; }
  .leave-balance-empty { color: var(--muted); font-size: 14px; padding: 20px 0; text-align: center; }
  .leave-balance-error { color: var(--danger); font-size: 14px; padding: 20px 0; text-align: center; }
  @media (max-width: 900px) {
    .resign-page-grid { grid-template-columns: 1fr; }
    .leave-balance-card { position: static; }
  }
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

<div class="resign-page-grid">
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

<div class="card leave-balance-card">
  <div class="card-title">Sisa Cuti</div>
  @if(isset($leaveBalance) && $leaveBalance !== null)
    <div class="leave-balance-sisa-text" style="font-size: 14px; color: var(--muted); margin-bottom: 4px;">Sisa cuti Anda saat ini</div>
    <div class="leave-balance-sisa">{{ number_format($leaveBalance['total_max_leave'] ?? 0, 1, ',', '.') }} <span style="font-size: 14px; font-weight: 500; color: var(--muted);">hari</span></div>
  @else
    <div class="leave-balance-empty">
      Data sisa cuti tidak dapat dimuat. Data Anda mungkin belum tercatat di sistem leave, atau sistem sedang sementara tidak dapat diakses.
    </div>
  @endif
</div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/resign-form.js') }}"></script>
@endsection
