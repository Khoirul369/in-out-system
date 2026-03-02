@extends('layouts.app')
@section('title', 'Detail Pengajuan Resign')

@section('styles')
<style>
  .resign-detail-wrap { max-width: 860px; margin: 0 auto; }
  .resign-detail-head-actions { display: flex; align-items: center; gap: 10px; }
  .resign-meta-grid { gap: 16px; }
  .resign-meta-label { font-size: 14px; color: var(--muted); margin-bottom: 4px; }
  .resign-meta-primary { font-weight: 600; font-size: 15px; }
  .resign-meta-muted { font-size: 14px; color: var(--muted); }
  .resign-meta-danger { font-weight: 600; color: var(--danger); }
  .resign-note-block { margin-top: 16px; }
  .resign-note-body { background: #f8fafc; padding: 12px 14px; border-radius: var(--radius); font-size: 14px; border: 1px solid var(--border); }
  .approval-meta { font-size: 14px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  .approval-time { color: var(--muted); }
  .approval-note { background: #f8fafc; padding: 12px 14px; border-radius: var(--radius); font-size: 14px; margin-top: 10px; border: 1px solid var(--border); }
  .section-actions { display: flex; gap: 12px; }
  .employee-actions { display: flex; align-items: center; gap: 12px; margin-top: 10px; }
  .employee-actions form { margin: 0; }
  .file-section { margin-top: 20px; }
  .file-label { font-size: 14px; color: var(--muted); margin-bottom: 8px; }
  .file-viewer { border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
  .file-viewer-head { padding: 12px 14px; background: #f8fafc; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
  .file-name { font-size: 14px; font-weight: 500; }
  .file-iframe { width: 100%; height: 600px; border: none; }
  .progress-detail { margin-bottom: 20px; }
  .dept-box { margin-bottom: 20px; border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
  .dept-box-head { padding: 12px 16px; background: #f8fafc; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); }
  .dept-box-title { font-weight: 600; font-size: 15px; }
  .dept-table { margin: 0; width: 100%; table-layout: fixed; }
  .dept-col-icon { width: 40px; }
  .dept-col-item { width: 38%; }
  .dept-col-pic { width: 18%; }
  .dept-col-note { width: 22%; }
  .dept-col-time { width: 22%; }
  .dept-table th, .dept-table td { vertical-align: middle; padding: 12px 14px; font-size: 14px; }
  .cell-center { text-align: center; }
  .cell-strong { font-weight: 500; }
  .cell-muted { color: var(--muted); }
  .cell-text { color: var(--text); }
  .cell-time { font-size: 13px; white-space: nowrap; }
  .cell-time.done { color: var(--success); }
  .cell-time.pending { color: var(--muted); }
  .req-mark { color: var(--danger); }
  @media (max-width: 768px) {
    .resign-detail-head-actions { width: 100%; justify-content: space-between; }
  }
</style>
@endsection

@section('content')
@php
  $sessionUser = session('user');
  $statusBadge = [
    \App\Models\ResignRequest::STATUS_PENDING => 'badge-pending',
    \App\Models\ResignRequest::STATUS_APPROVED => 'badge-approved',
    \App\Models\ResignRequest::STATUS_APPROVED_HC => 'badge-approved_hc',
    \App\Models\ResignRequest::STATUS_REJECTED => 'badge-rejected',
    \App\Models\ResignRequest::STATUS_DONE => 'badge-done',
  ];
  $deptLabels = ['hc' => 'HC', 'it' => 'IT Support', 'doc' => 'Doc Center', 'finance' => 'Finance', 'ga' => 'GA'];
@endphp

<div class="resign-detail-wrap">
  <div class="page-head">
    <div>
      <div class="page-title">Detail Pengajuan Resign</div>
      <div class="page-sub">Informasi lengkap pengajuan dan progres checklist.</div>
    </div>
    <div class="resign-detail-head-actions">
      <span class="badge {{ $statusBadge[$resign->status] ?? 'badge-default' }}">{{ $resign->getStatusLabel() }}</span>
      <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm">← Kembali</a>
    </div>
  </div>

  {{-- Info Utama --}}
  <div class="card">
    <div class="card-title">Informasi Pengajuan</div>
    <div class="grid grid-2 resign-meta-grid">
      <div>
        <div class="resign-meta-label">Karyawan</div>
        <div class="resign-meta-primary">{{ $resign->employee->nama }}</div>
        <div class="resign-meta-muted">{{ $resign->employee->username }}</div>
      </div>
      <div>
        <div class="resign-meta-label">Tanggal Pengajuan</div>
        <div>{{ $resign->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</div>
      </div>
      <div>
        <div class="resign-meta-label">Tanggal Terakhir Bekerja</div>
        <div class="resign-meta-danger">{{ $resign->last_date->format('d M Y') }}</div>
      </div>
      <div>
        <div class="resign-meta-label">Status Alur</div>
        <div>{{ $resign->getWorkflowLabel() }}</div>
      </div>
    </div>

    <div class="resign-note-block">
      <div class="resign-meta-label">Alasan Resign</div>
      <div class="resign-note-body">{{ $resign->reason }}</div>
    </div>

    @if($resign->description)
      <div class="resign-note-block">
        <div class="resign-meta-label">Deskripsi Tambahan</div>
        <div class="resign-note-body">{{ $resign->description }}</div>
      </div>
    @endif

    {{-- File Surat Resign - Tampil Inline --}}
    @if($resign->resign_filename)
      <div class="file-section">
        <div class="file-label">Surat Resign</div>
        @php
          $ext = strtolower(pathinfo($resign->resign_filename, PATHINFO_EXTENSION));
          $fileUrl = asset('storage/' . $resign->resign_file_path);
        @endphp
        @if($ext === 'pdf')
          <div class="file-viewer">
            <div class="file-viewer-head">
              <span class="file-name">📄 {{ $resign->resign_filename }}</span>
              <a href="{{ $fileUrl }}" target="_blank" class="btn btn-outline btn-sm">Buka di Tab Baru</a>
            </div>
            <iframe src="{{ $fileUrl }}" class="file-iframe" title="Surat Resign"></iframe>
          </div>
        @else
          {{-- DOC/DOCX: tampilkan via Google Docs Viewer --}}
          <div class="file-viewer">
            <div class="file-viewer-head">
              <span class="file-name">📄 {{ $resign->resign_filename }}</span>
              <a href="{{ $fileUrl }}" target="_blank" class="btn btn-outline btn-sm">Download</a>
            </div>
            <iframe src="https://docs.google.com/viewer?url={{ urlencode($fileUrl) }}&embedded=true"
              class="file-iframe" title="Surat Resign"></iframe>
          </div>
        @endif
      </div>
    @endif
  </div>

  {{-- Approval PM --}}
  @if($resign->approved_by || $resign->workflow_stage === \App\Models\ResignRequest::STAGE_PM_REJECTED)
    <div class="card">
      <div class="card-title">Approval PM</div>
      @php
        $isPmRejected = $resign->workflow_stage === \App\Models\ResignRequest::STAGE_PM_REJECTED;
      @endphp
      <div class="approval-meta">
        <strong>{{ $isPmRejected ? ($resign->rejectedBy?->nama ?? '-') : ($resign->approvedBy?->nama ?? '-') }}</strong>
        <span class="badge {{ $isPmRejected ? 'badge-rejected' : 'badge-approved' }}">
          {{ $isPmRejected ? 'Ditolak' : 'Disetujui' }}
        </span>
        @php
          $pmDecisionAt = $isPmRejected ? $resign->rejected_at : $resign->approved_at;
        @endphp
        @if($pmDecisionAt)
          <span class="approval-time">· {{ $pmDecisionAt->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</span>
        @endif
      </div>
      @if($resign->approved_description)
        <div class="approval-note">{{ $resign->approved_description }}</div>
      @endif
    </div>
  @endif

  {{-- Verifikasi HC --}}
  @if($resign->approved_hc_by || $resign->workflow_stage === \App\Models\ResignRequest::STAGE_HC_REJECTED)
    <div class="card">
      <div class="card-title">Verifikasi HC</div>
      @php
        $isHcRejected = $resign->workflow_stage === \App\Models\ResignRequest::STAGE_HC_REJECTED;
      @endphp
      <div class="approval-meta">
        <strong>{{ $isHcRejected ? ($resign->rejectedBy?->nama ?? '-') : ($resign->approvedHcBy?->nama ?? '-') }}</strong>
        <span class="badge {{ $isHcRejected ? 'badge-rejected' : 'badge-approved_hc' }}">
          {{ $isHcRejected ? 'Ditolak' : 'Diverifikasi' }}
        </span>
        @php
          $hcDecisionAt = $isHcRejected ? $resign->rejected_at : $resign->approved_hc_at;
        @endphp
        @if($hcDecisionAt)
          <span class="approval-time">· {{ $hcDecisionAt->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</span>
        @endif
      </div>
      @if($resign->approved_hc_description)
        <div class="approval-note">{{ $resign->approved_hc_description }}</div>
      @endif
    </div>
  @endif

  {{-- Progress Checklist Detail per Divisi --}}
  @if($resign->isInChecklistStage() || $resign->isCompleted())
    <div class="card">
      <div class="card-title">Progress Checklist Divisi</div>
      @php $progress = $resign->getChecklistProgress(); @endphp
      <div class="progress-wrap progress-detail">
        <div class="progress-bar"><div class="progress-fill" style="width:{{ $progress['percent'] }}%"></div></div>
        <span class="progress-text">{{ $progress['done'] }}/{{ $progress['total'] }} ({{ $progress['percent'] }}%)</span>
      </div>

      @php
        $itemsByDept = $resign->checklistItems->groupBy('department');
        $deptOrder = ['hc', 'it', 'doc', 'finance', 'ga'];
      @endphp

      @foreach($deptOrder as $dept)
        @if($itemsByDept->has($dept))
          @php $deptItems = $itemsByDept[$dept]; $deptDone = $deptItems->where('done', 1)->count(); $deptTotal = $deptItems->count(); @endphp
          <div class="dept-box">
            <div class="dept-box-head">
              <div class="dept-box-title">{{ $deptLabels[$dept] ?? strtoupper($dept) }}</div>
              <span class="badge {{ $deptDone === $deptTotal ? 'badge-done' : 'badge-pending' }}">
                {{ $deptDone }}/{{ $deptTotal }} {{ $deptDone === $deptTotal ? '✓ Selesai' : 'Belum Selesai' }}
              </span>
            </div>
            <table class="table dept-table">
              <thead>
                <tr>
                  <th class="dept-col-icon"></th>
                  <th class="dept-col-item">Item</th>
                  <th class="dept-col-pic">PIC</th>
                  <th class="dept-col-note">Keterangan</th>
                  <th class="dept-col-time">Waktu Selesai</th>
                </tr>
              </thead>
              <tbody>
                @foreach($deptItems as $item)
                  <tr>
                    <td class="cell-center">{{ $item->done ? '✅' : '⬜' }}</td>
                    <td class="cell-strong">{{ $item->item_label }}</td>
                    <td class="{{ $item->pic ? 'cell-text' : 'cell-muted' }}">{{ $item->pic ?: '-' }}</td>
                    <td class="{{ $item->keterangan ? 'cell-text' : 'cell-muted' }}">{{ $item->keterangan ?: '-' }}</td>
                    <td class="cell-time {{ $item->done_at ? 'done' : 'pending' }}">
                      {{ $item->done_at ? $item->done_at->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB' : '-' }}
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      @endforeach
    </div>
  @endif

  {{-- Form Approval PM --}}
  @if($resign->needsPmApproval() && $user->isPm() && $resign->employees_id !== $user->id)
    @php $isSubordinate = \App\Models\User::where('id', $resign->employees_id)->where('pm_id', $user->id)->exists(); @endphp
    @if($isSubordinate)
      <div class="card">
        <div class="card-title">Action Approval PM</div>
        <form method="POST" action="{{ route('approval.pm.action') }}" id="form-pm-action">
          @csrf
          <input type="hidden" name="id" value="{{ $resign->id }}">
          <input type="hidden" name="action" value="approved" id="pm-action-val">
          <div class="field">
            <label>Keterangan <span class="req-mark">*</span></label>
            <textarea name="keterangan" rows="3" required placeholder="Berikan alasan approval/penolakan..."></textarea>
          </div>
          <div class="section-actions">
            <button type="button" class="btn btn-success" id="btn-pm-approve">✓ Approve</button>
            <button type="button" class="btn btn-danger" id="btn-pm-reject">✗ Reject</button>
          </div>
        </form>
      </div>
    @endif
  @endif

  {{-- Form Verifikasi HC --}}
  @if($resign->needsHcApproval() && $user->isHc() && $resign->employees_id !== $user->id)
    <div class="card">
      <div class="card-title">Verifikasi HC</div>
      <form method="POST" action="{{ route('approval.hc.action') }}" id="form-hc-action">
        @csrf
        <input type="hidden" name="id" value="{{ $resign->id }}">
        <input type="hidden" name="action" value="approved" id="hc-action-val">
        <div class="field">
          <label>Keterangan <span class="req-mark">*</span></label>
          <textarea name="keterangan" rows="3" required placeholder="Berikan catatan verifikasi..."></textarea>
        </div>
        <div class="section-actions">
          <button type="button" class="btn btn-success" id="btn-hc-approve">✓ Verifikasi</button>
          <button type="button" class="btn btn-danger" id="btn-hc-reject">✗ Tolak</button>
        </div>
      </form>
    </div>
  @endif

  {{-- Tombol aksi karyawan --}}
  @if($resign->employees_id === $sessionUser['id'] && $resign->status === \App\Models\ResignRequest::STATUS_PENDING)
    <div class="employee-actions">
      <a href="{{ route('resign.edit', $resign->id) }}" class="btn btn-outline">Edit Pengajuan</a>
      <form method="POST" action="{{ route('resign.cancel', $resign->id) }}" id="form-cancel">
        @csrf
        <button type="button" class="btn btn-danger" id="btn-cancel-resign">Batalkan Pengajuan</button>
      </form>
    </div>
  @endif
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/resign-detail.js') }}"></script>
@endsection
