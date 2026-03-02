@extends('layouts.app')
@section('title', 'Checklist Resign')

@section('styles')
<style>
  .checklist-card { padding: 18px 20px !important; overflow: hidden; }
  .checklist-card .checklist-card-head { margin-bottom: 14px; }
  .checklist-card .checklist-progress { margin-bottom: 14px; }
  .checklist-card .checklist-item { padding: 10px 0; overflow: hidden; }
  .checklist-row {
    display: grid;
    grid-template-columns: 24px 1fr;
    gap: 10px;
    align-items: start;
    min-width: 0;
  }
  .checklist-item-body { min-width: 0; overflow: hidden; }
  .checklist-row input[type=checkbox] {
    margin-top: 4px;
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
  .checklist-item-label { font-weight: 600; cursor: pointer; font-size: 15px; }
  .checklist-item-pic { color: var(--muted); font-size: 14px; margin-top: 4px; }
  .checklist-item-save-btn:disabled { opacity: 0.6; cursor: not-allowed; }
  .checklist-item-actions { margin-top: 12px; }
  .checklist-saved-msg { font-size: 14px; color: var(--success); font-weight: 500; margin-left: 0; }
  .checklist-keterangan-wrap { margin-top: 8px; }
  .checklist-keterangan-wrap label { font-size: 14px; font-weight: 500; margin-bottom: 4px; display: block; }
  .checklist-keterangan-wrap textarea { width: 100%; border: 1px solid var(--border); border-radius: var(--radius); padding: 8px 12px; font-size: 15px; min-height: 56px; }
  .checklist-saved-ket { margin-top: 8px; padding: 10px 12px; background: #f8fafc; border-radius: var(--radius); font-size: 14px; position: relative; border: 1px solid var(--border); overflow: hidden; word-wrap: break-word; overflow-wrap: break-word; }
  .checklist-saved-ket .checklist-ket-label { font-weight: 600; display: block; margin-bottom: 4px; font-size: 14px; }
  .checklist-saved-ket .checklist-ket-value { margin: 0; color: var(--text); font-size: 14px; line-height: 1.5; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; max-width: 100%; }
  .checklist-saved-ket small { color: var(--muted); font-size: 14px; word-wrap: break-word; overflow-wrap: break-word; }
  .checklist-saved-ket small.checklist-done-meta { display: block; margin-top: 4px; font-size: 14px; word-wrap: break-word; overflow-wrap: break-word; }
  .checklist-edit-item-btn { position: absolute; top: 8px; right: 8px; background: none; border: none; cursor: pointer; padding: 4px; color: var(--muted); font-size: 16px; }
  .checklist-edit-item-btn:hover { color: var(--primary); }
  .checklist-card .checklist-item-actions { margin-top: 8px; }
  .checklist-card .mt-16 { margin-top: 12px; }
  @media (max-width: 768px) {
    .checklist-fields {
      grid-template-columns: 1fr;
    }
  }
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
    @php $items = $resign->checklistItems; @endphp
    <div class="card checklist-card">
      <div class="checklist-card-head">
        <div>
          <div class="checklist-employee-name">{{ $resign->employee->nama }}</div>
          <div class="checklist-employee-meta">
            {{ $resign->employee->username }} · Terakhir: {{ $resign->last_date->format('d M Y') }}
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
            <div class="checklist-row">
              <input type="checkbox"
                name="items[{{ $item->item_key }}][done]"
                value="1"
                data-resign-id="{{ $resign->id }}"
                data-item-key="{{ $item->item_key }}"
                {{ $item->done ? 'checked' : '' }}>
              <div class="checklist-item-body">
                <label class="checklist-item-label">{{ $item->item_label }}</label>
                @if(!empty($item->pic))
                  <div class="checklist-item-pic">PIC: {{ $item->pic }}</div>
                @endif
                @if($item->done)
                  <div class="checklist-saved-ket" data-done-block="1">
                    <span class="checklist-ket-label">Keterangan</span>
                    <p class="checklist-ket-value">{{ $item->keterangan }}</p>
                    @if($item->done_at)
                      <small class="checklist-done-meta">
                        @if($item->doneByUser)
                          Dichecklist  oleh {{ $item->doneByUser->nama ?? $item->doneByUser->username }} pada {{ $item->done_at->timezone('Asia/Jakarta')->format('d M Y, H.i') }}
                        @else
                          {{ $item->done_at->timezone('Asia/Jakarta')->format('d M Y, H.i') }}
                        @endif
                      </small>
                    @endif
                    <button type="button" class="checklist-edit-item-btn" title="Edit" data-item-key="{{ $item->item_key }}">✎</button>
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
            </div>
          </div>
        @endforeach

        <div class="mt-16" id="checklist-msg-wrap-{{ $resign->id }}">
          <span class="checklist-saved-msg" id="saved-msg-{{ $resign->id }}" style="display:none;">Checklist telah tersimpan dan selesai.</span>
        </div>
      </form>
    </div>
  @endforeach
@endif
@endsection

@section('scripts')
<script src="{{ asset('js/checklist.js') }}"></script>
@endsection
