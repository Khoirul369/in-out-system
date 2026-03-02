@extends('layouts.app')
@section('title', 'Master Checklist')

@section('styles')
<style>
  .master-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
  .master-grid { display: grid; grid-template-columns: 280px minmax(0, 1fr); gap: 20px; align-items: start; }
  .master-grid.form-hidden { grid-template-columns: 1fr; }
  .master-grid.form-hidden .master-table { grid-column: 1 / -1; }
  .master-form-card { padding: 18px 20px !important; }
  .master-form-card .card-title { margin-bottom: 12px; }
  .master-form-card .field { margin-bottom: 12px; }
  .master-form-card .mt-16 { margin-top: 12px; }
  .master-form-toggle { white-space: nowrap; }
  .master-form-card,
  .master-table { border-radius: var(--radius); }
  .master-status-group { display: flex; align-items: center; gap: 10px; margin-top: 6px; }
  .master-status-option { display: inline-flex; align-items: center; gap: 6px; font-size: 14px; }
  .master-status-option input { width: auto; }
  .master-submit-wrap { display: flex; gap: 8px; margin-top: 12px; }
  .master-table .card-title { margin-bottom: 16px; }
  .master-table .table-wrap { border-radius: var(--radius); }
  .master-table .table { min-width: 700px; }
  .master-table .table th { background: #f8fafc; font-size: 13px; text-transform: uppercase; letter-spacing: .5px; color: var(--muted); border-bottom: 1px solid var(--border); padding: 12px 14px; }
  .master-table .table td { border-bottom: 1px solid var(--border); padding: 12px 14px; font-size: 14px; }
  .master-table .table tr:hover td { background: #f8fafc; }
  .master-pill { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 13px; font-weight: 600; line-height: 1; }
  .master-pill-pic { background: #dbeafe; color: #1e40af; }
  .master-pill-active { background: #dcfce7; color: #166534; }
  .master-pill-inactive { background: #e2e8f0; color: #475569; }
  .master-actions { display: flex; align-items: center; gap: 8px; }
  .master-icon-btn { border: 1px solid var(--border); background: #fff; width: 30px; height: 30px; border-radius: var(--radius); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
  .master-icon-btn:hover { background: #f8fafc; }
  .master-icon-btn.danger { color: var(--danger); }
  .master-icon-btn[disabled] { opacity: .45; cursor: not-allowed; }
  .master-lock { color: var(--muted); font-size: 14px; }
  .master-field-lock-msg { font-size: 12px; color: var(--muted); margin-top: 4px; display: block; }
  .master-hide { display: none; }
  @media (max-width: 980px) {
    .master-grid { grid-template-columns: 1fr; }
    .master-head { align-items: flex-start; flex-direction: column; }
  }
</style>
@endsection

@section('content')
@php
  $isEditMode = $editingItem !== null;
  $formAction = $isEditMode ? route('checklist.master.update', $editingItem->id) : route('checklist.master.store');
  $formTitle = $isEditMode ? 'Edit Checklist Item' : 'Tambah Checklist Item';
  $submitText = $isEditMode ? 'Update' : 'Tambah';
  $currentItemLabel = old('item_label', $editingItem->item_label ?? '');
  $currentPic = old('default_pic', $editingItem->default_pic ?? '');
  $currentStatus = (string) old('is_active', $isEditMode ? ((int) $editingItem->is_active) : '1');
@endphp

<div class="page-head master-head">
  <div>
    <div class="page-title">Master Checklist</div>
    <div class="page-sub">Kelola item checklist & PIC default untuk departemen {{ \App\Models\ResignChecklistItem::DEPARTMENT_LABELS[$department] ?? strtoupper($department) }}.</div>
  </div>
  <button type="button" class="btn btn-primary master-form-toggle" id="btn-master-toggle-form">Sembunyikan Form</button>
</div>

<div class="master-grid" id="master-grid">
  <div class="card master-form-card" id="master-form-card" data-store-action="{{ route('checklist.master.store') }}">
    <div class="card-title" id="master-form-title">{{ $formTitle }}</div>
    <form method="POST" action="{{ $formAction }}" class="mt-16" id="master-form">
      @csrf
      <input type="hidden" name="edit_item_id" id="master-edit-item-id" value="{{ $editingItem->id ?? '' }}">
      <div class="field" id="master-field-label-wrap">
        <label>Nama Checklist Item</label>
        <input type="text" name="item_label" id="master-item-label" value="{{ $currentItemLabel }}" placeholder="Masukkan nama checklist item" required>
        <span class="master-field-lock-msg master-hide" id="master-label-lock-msg">Item sudah dipakai; hanya status yang dapat diubah.</span>
      </div>
      <div class="field" id="master-field-pic-wrap">
        <label>PIC (Person In Charge)</label>
        <select name="default_pic" id="master-default-pic">
          <option value="">Pilih PIC...</option>
          @foreach($picOptions as $pic)
            <option value="{{ $pic['nama'] }}" {{ $currentPic === $pic['nama'] ? 'selected' : '' }}>{{ $pic['nama'] }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label>Status</label>
        <div class="master-status-group">
          <label class="master-status-option">
            <input type="radio" name="is_active" value="1" {{ $currentStatus === '1' ? 'checked' : '' }}>
            Active
          </label>
          <label class="master-status-option">
            <input type="radio" name="is_active" value="0" {{ $currentStatus === '0' ? 'checked' : '' }}>
            Inactive
          </label>
        </div>
      </div>
      <div class="master-submit-wrap">
        <button type="submit" class="btn btn-primary" id="master-submit-btn">{{ $submitText }}</button>
        <button type="button" class="btn btn-outline {{ $isEditMode ? '' : 'master-hide' }}" id="master-cancel-edit">Batal</button>
      </div>
    </form>
  </div>

  <div class="card master-table">
    <div class="card-title">Daftar Checklist</div>

    @if($items->isEmpty())
      <div class="empty-state">Belum ada item master checklist untuk admin ini.</div>
    @else
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th style="width:64px;">No</th>
              <th>Nama Item</th>
              <th style="width:170px;">PIC</th>
              <th style="width:120px;">Status</th>
              <th style="width:120px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach($items as $index => $item)
              @php
                $isOwnedByUser = (int) $item->admin_user_id === (int) $user->id;
                $isUsedAndChecked = in_array($item->id, $masterIdsLocked ?? [], true);
                $editDisabled = !$isOwnedByUser;
                $deleteDisabled = !$isOwnedByUser || $isUsedAndChecked;
              @endphp
              <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                  {{ $item->item_label }}
                  @if(!$isOwnedByUser)
                    <span class="master-lock" title="Readonly">🔒</span>
                  @endif
                  @if($isUsedAndChecked)
                    <span class="master-lock" title="Sudah dipakai & dicentang di checklist">🔒</span>
                  @endif
                </td>
                <td>
                  @if($item->default_pic)
                    <span class="master-pill master-pill-pic">{{ $item->default_pic }}</span>
                  @else
                    <span class="master-pill master-pill-inactive">-</span>
                  @endif
                </td>
                <td>
                  <span class="master-pill {{ $item->is_active ? 'master-pill-active' : 'master-pill-inactive' }}">
                    {{ $item->is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td>
                  <div class="master-actions">
                    <button type="button"
                      class="master-icon-btn btn-master-edit"
                      title="{{ $isUsedAndChecked ? 'Item sudah dipakai: hanya status Active/Inactive yang bisa diubah' : 'Edit' }}"
                      data-id="{{ $item->id }}"
                      data-item-label="{{ $item->item_label }}"
                      data-default-pic="{{ $item->default_pic }}"
                      data-is-active="{{ $item->is_active ? '1' : '0' }}"
                      data-update-action="{{ route('checklist.master.update', $item->id) }}"
                      data-used-and-checked="{{ $isUsedAndChecked ? '1' : '0' }}"
                      {{ $editDisabled ? 'disabled' : '' }}>
                      ✎
                    </button>
                    <button type="button"
                      class="master-icon-btn danger btn-master-delete"
                      title="{{ $isUsedAndChecked ? 'Item sudah dipakai dan dicentang di checklist' : 'Hapus' }}"
                      data-delete-form="delete-master-{{ $item->id }}"
                      {{ $deleteDisabled ? 'disabled' : '' }}>
                      🗑
                    </button>
                  </div>
                  <form id="delete-master-{{ $item->id }}" method="POST" action="{{ route('checklist.master.destroy', $item->id) }}" class="master-hide">
                    @csrf
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/checklist-master.js') }}"></script>
@endsection
