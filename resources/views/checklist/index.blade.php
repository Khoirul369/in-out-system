@extends('layouts.app')
@section('title', 'Checklist Resign')

@section('styles')
<style>
  .checklist-card { padding: 20px 24px !important; overflow: hidden; width: 100%; max-width: 100%; }
  .checklist-card .checklist-card-head { margin-bottom: 14px; }
  .checklist-card .checklist-progress { margin-bottom: 18px; }
  .checklist-card .checklist-item { padding: 14px 0; overflow: hidden; border-bottom: 1px solid var(--border, #e5e7eb); }
  .checklist-card .checklist-item:last-child { border-bottom: none; }
  .checklist-row {
    display: flex;
    flex-wrap: nowrap;
    align-items: flex-start;
    gap: 12px;
    width: 100%;
    min-width: 0;
  }
  .checklist-row .checklist-done-icon,
  .checklist-row input[type="checkbox"] { flex-shrink: 0; }
  .checklist-item-body { flex: 1; min-width: 0; overflow: hidden; }
  .checklist-row .checklist-edit-item-btn { flex-shrink: 0; margin-left: auto; }
  .checklist-row-no-edit .checklist-item-body { flex: 1; }
  .checklist-row input[type=checkbox] {
    margin-top: 4px;
  }
  .checklist-done-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    color: #16a34a;
    font-size: 18px;
    font-weight: bold;
    line-height: 1;
    pointer-events: none;
    user-select: none;
  }
  .checklist-fields {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px;
    margin-top: 6px;
  }
  .checklist-fields input[type=text] {
    width: 100%;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 8px 12px;
    font-size: 15px;
    background: #fff;
  }
  .checklist-fields input[type=text]:focus {
    outline: none;
    border-color: var(--primary);
  }
  .checklist-card-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
  .checklist-employee-name { font-weight: 700; font-size: 18px; line-height: 1.25; }
  .checklist-employee-meta { color: var(--muted); font-size: 14px; margin-top: 4px; }
  .checklist-progress { margin-bottom: 20px; }
  .checklist-item-body { flex: 1; }
  .checklist-item-label { font-weight: 600; font-size: 15px; }
  .checklist-item-pic { color: var(--muted); font-size: 14px; margin-top: 4px; }
  .checklist-item-save-btn:disabled { opacity: 0.6; cursor: not-allowed; }
  .checklist-item-actions { margin-top: 12px; }
  .checklist-saved-msg { font-size: 14px; color: var(--success); font-weight: 500; margin-left: 0; }
  .checklist-keterangan-wrap { margin-top: 8px; }
  .checklist-keterangan-wrap label { font-size: 14px; font-weight: 500; margin-bottom: 4px; display: block; }
  .checklist-keterangan-wrap textarea { width: 100%; border: 1px solid var(--border); border-radius: var(--radius); padding: 8px 12px; font-size: 15px; min-height: 56px; }
  .checklist-saved-ket { margin-top: 8px; font-size: 14px; position: relative; word-wrap: break-word; overflow-wrap: break-word; }
  .checklist-saved-ket .checklist-ket-label { font-weight: 600; display: block; margin-bottom: 2px; font-size: 14px; color: var(--text); }
  .checklist-saved-ket .checklist-ket-value { margin: 0 0 4px 0; color: var(--text); font-size: 14px; line-height: 1.5; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; max-width: 100%; }
  .checklist-saved-ket small { color: var(--muted); font-size: 13px; word-wrap: break-word; overflow-wrap: break-word; }
  .checklist-saved-ket small.checklist-done-meta { display: block; margin-top: 2px; font-size: 13px; word-wrap: break-word; overflow-wrap: break-word; }
  .checklist-edit-item-btn { background: none; border: none; cursor: pointer; padding: 4px; color: var(--muted); font-size: 16px; flex-shrink: 0; }
  .checklist-edit-item-btn:hover { color: var(--primary); }
  .checklist-edit-item-btn:disabled { opacity: 0.35; cursor: not-allowed; }
  .checklist-card .checklist-item-actions { margin-top: 8px; }
  .checklist-card .mt-16 { margin-top: 12px; }
  @media (max-width: 768px) {
    .checklist-fields {
      grid-template-columns: 1fr;
    }
  }

  .doc-attach { margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border); }
  .doc-attach-title { font-weight: 700; margin-bottom: 10px; }
  .doc-attach-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; align-items: end; }
  .doc-attach-grid .field { margin-bottom: 0; }
  .doc-attach input[type="file"] { padding: 10px 14px; font-size: 15px; min-height: 44px; cursor: pointer; }
  .doc-attach .btn { padding: 10px 20px; font-size: 15px; min-height: 44px; }
  .doc-attach-actions { display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap; }
  .doc-attach-list { margin-top: 10px; }
  .doc-attach-item .btn { padding: 8px 16px; font-size: 14px; }
  .doc-attach-item { display: flex; justify-content: space-between; gap: 10px; padding: 8px 10px; border: 1px solid var(--border); border-radius: var(--radius); background: #f8fafc; margin-top: 8px; }
  .doc-attach form[action*="surat-keterangan"] { margin-bottom: 20px; }
  .doc-attach form[action*="hc.done"],
  .doc-attach .doc-attach-done-form { margin-top: 24px; }
  .doc-attach-item .meta { min-width: 0; }
  .doc-attach-item .meta .t { font-weight: 600; }
  .doc-attach-item .meta .f { color: var(--muted); font-size: 13px; word-break: break-word; }
  .doc-complete-badge { display:inline-block; padding: 4px 10px; border-radius: 999px; font-size: 13px; font-weight: 700; background:#dcfce7; color:#166534; margin-left: 10px; }
</style>
@endsection

@section('content')
<div class="page-head">
  <div>
    <div class="page-title">Checklist Resign</div>
    <div class="page-sub">
      Departemen {{ \App\Models\ResignChecklistItem::DEPARTMENT_LABELS[$dept] ?? strtoupper($dept) }}
    </div>
  </div>
</div>

@if($resigns->isEmpty())
  <div class="card">
    <div class="empty-state">Tidak ada pengajuan resign yang perlu diproses saat ini.</div>
  </div>
@else
  @foreach($resigns as $resign)
    @php
      $items = $resign->checklistItems;
      $deptCompletedAt = null;
      if ($dept === 'hc') $deptCompletedAt = $resign->completed_hc_at ?? null;
      if ($dept === 'it') $deptCompletedAt = $resign->completed_it_at ?? null;
      if ($dept === 'doc') $deptCompletedAt = $resign->completed_doc_at ?? null;
      if ($dept === 'finance') $deptCompletedAt = $resign->completed_finance_at ?? null;
      if ($dept === 'ga') $deptCompletedAt = $resign->completed_ga_at ?? null;
      $deptAllChecked = isset($items) ? ($items->where('done', 0)->count() === 0) : false;
      $completeDisabled = !$deptAllChecked || (bool) $deptCompletedAt;
      $isDone = !empty($resign->done_at) || ($resign->status === \App\Models\ResignRequest::STATUS_DONE);
      $editDisabled = (bool) $deptCompletedAt || $isDone;
      $isReadOnly = $editDisabled;
    @endphp
    <div class="card checklist-card">
      <div class="checklist-card-head">
        <div>
          <div class="checklist-employee-name">{{ $resign->employee->nama }}</div>
          <div class="checklist-employee-meta">
            {{ $resign->employee->username }} · Tanggal pengajuan: {{ $resign->created_at->format('d M Y') }}
          </div>
        </div>
        <a href="{{ route('resign.detail', $resign->id) }}" class="btn btn-outline btn-sm">Lihat Detail</a>
      </div>

      {{-- Progress --}}
      @php $progress = $resign->getChecklistProgress(); @endphp
      <div class="progress-wrap checklist-progress">
        <div class="progress-bar">
          <div class="progress-fill" id="progress-fill-{{ $resign->id }}" style="width:{{ $progress['percent'] }}%"></div>
        </div>
        <span class="progress-text" id="progress-text-{{ $resign->id }}">{{ $progress['done'] }}/{{ $progress['total'] }} ({{ $progress['percent'] }}%)</span>
      </div>

      <div id="checklist-error-{{ $resign->id }}" class="alert alert-error checklist-error-inline" style="display:none;"></div>

      <form
        method="POST"
        action="{{ route('checklist.update') }}"
        id="form-{{ $resign->id }}"
        data-checklist-form="1"
        data-resign-id="{{ $resign->id }}"
        data-update-url="{{ route('checklist.update') }}">
        @csrf
        <input type="hidden" name="resign_request_id" value="{{ $resign->id }}">

        @foreach($items as $item)
          <div class="checklist-item" id="item-{{ $item->item_key }}-{{ $resign->id }}" data-item-key="{{ $item->item_key }}" data-resign-id="{{ $resign->id }}">
            <div class="checklist-row {{ $item->done ? '' : 'checklist-row-no-edit' }}">
              @if($item->done)
                <span class="checklist-done-icon" aria-hidden="true">✓</span>
                <input type="hidden" name="items[{{ $item->item_key }}][done]" value="1">
              @else
                <input type="checkbox"
                  name="items[{{ $item->item_key }}][done]"
                  value="1"
                  data-resign-id="{{ $resign->id }}"
                  data-item-key="{{ $item->item_key }}"
                  {{ $isReadOnly ? 'disabled' : '' }}>
              @endif
              <div class="checklist-item-body">
                <span class="checklist-item-label">{{ $item->item_label }}</span>
                @if(!empty($item->pic))
                  <div class="checklist-item-pic">PIC: {{ $item->pic }}</div>
                @endif
                @if($item->done)
                  <div class="checklist-saved-ket" data-done-block="1">
                    <p class="checklist-ket-value">{{ $item->keterangan ?: '—' }}</p>
                    @if($item->done_at)
                      <small class="checklist-done-meta">
                        @if($item->doneByUser)
                          Diperiksa oleh {{ $item->doneByUser->nama ?? $item->doneByUser->username }} pada {{ $item->done_at->timezone('Asia/Jakarta')->format('d M Y, H.i') }}
                        @else
                          {{ $item->done_at->timezone('Asia/Jakarta')->format('d M Y, H.i') }}
                        @endif
                      </small>
                    @endif
                  </div>
                  <input type="hidden" name="items[{{ $item->item_key }}][keterangan]" value="{{ $item->keterangan }}">
                @else
                  <div class="checklist-keterangan-wrap" style="display:none;" data-ket-wrap="1">
                    <label>Keterangan</label>
                    <textarea name="items[{{ $item->item_key }}][keterangan]" placeholder="Tambahkan keterangan..." data-resign-id="{{ $resign->id }}" data-item-key="{{ $item->item_key }}" rows="2"></textarea>
                    <div class="checklist-item-actions mt-2">
                      <button type="submit" class="btn btn-primary btn-sm checklist-item-save-btn" disabled>Simpan</button>
                    </div>
                  </div>
                @endif
              </div>
              @if($item->done && !$isReadOnly)
                <button
                  type="button"
                  class="checklist-edit-item-btn"
                  title="Edit"
                  data-item-key="{{ $item->item_key }}"
                >✎</button>
              @endif
            </div>
          </div>
        @endforeach

        <div class="mt-16" id="checklist-msg-wrap-{{ $resign->id }}">
          <span class="checklist-saved-msg" id="saved-msg-{{ $resign->id }}" style="display:none;">Checklist telah tersimpan dan selesai.</span>
        </div>
      </form>

      {{-- Complete button untuk semua divisi --}}
      <form method="POST" action="{{ route('checklist.complete') }}" class="inline-form mt-12">
        @csrf
        <input type="hidden" name="resign_request_id" value="{{ $resign->id }}">
        <button
          type="submit"
          class="btn btn-outline btn-sm"
          {{ $completeDisabled ? 'disabled' : '' }}
          title="{{ !$deptAllChecked ? 'Centang semua item checklist dulu sebelum Complete.' : ($deptCompletedAt ? 'Sudah Complete.' : 'Complete') }}">
          {{ $deptCompletedAt ? 'Completed' : 'Complete' }}
        </button>
      </form>

      <div class="doc-attach">
        <div class="doc-attach-title">Attachment</div>

        <form method="POST" action="{{ route('checklist.attachment') }}" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="resign_request_id" value="{{ $resign->id }}">
          <div class="doc-attach-grid">
            <div class="field">
              <label>Title</label>
              <input type="text" name="title" placeholder="Contoh: BA Serah Terima" required>
            </div>
            <div class="field">
              <label>Choose File</label>
              <input type="file" name="file" required>
            </div>
          </div>
          <div class="doc-attach-actions">
            <button type="submit" class="btn btn-primary">Upload</button>
          </div>
        </form>

        @if(isset($resign->files) && $resign->files->isNotEmpty())
          <div class="doc-attach-list">
            @foreach($resign->files as $f)
              <div class="doc-attach-item">
                <div class="meta">
                  <div class="t">{{ $f->title }}</div>
                  <div class="f">{{ $f->filename }}</div>
                </div>
                <div>
                  <a class="btn btn-outline" href="{{ asset('storage/' . $f->filepath) }}" target="_blank" rel="noopener">Download</a>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>

      @if($dept === 'hc')
        @php
          $allDivCompleted = !empty($resign->completed_hc_at) && !empty($resign->completed_it_at) && !empty($resign->completed_doc_at) && !empty($resign->completed_finance_at) && !empty($resign->completed_ga_at);
          $allChecklistDone = \App\Models\ResignChecklistItem::where('resign_request_id', $resign->id)->where('done', 0)->doesntExist();
        @endphp

        @if(($allDivCompleted || $allChecklistDone) && !$isDone)
          <div class="doc-attach">
            <div class="doc-attach-title">Finalisasi HC</div>
            <div class="muted" style="margin-top:-6px;">
              Semua divisi sudah complete. Pilih file Surat Keterangan lalu klik Done untuk menyelesaikan pengajuan.
            </div>

            <form method="POST" action="{{ route('checklist.hc.done') }}" enctype="multipart/form-data" class="doc-attach-done-form mt-12">
              @csrf
              <input type="hidden" name="resign_request_id" value="{{ $resign->id }}">
              <div class="doc-attach-grid" style="max-width: 560px;">
                <div class="field">
                  <label>Surat Keterangan</label>
                  <input type="file" name="file" required accept=".pdf,.doc,.docx">
                </div>
                <div class="field">
                  <label>&nbsp;</label>
                  <button type="submit" class="btn btn-primary">Done</button>
                </div>
              </div>
            </form>
          </div>
        @endif
      @endif
    </div>
  @endforeach
@endif
@endsection

@section('scripts')
<script src="{{ asset('js/checklist.js') }}"></script>
@endsection
