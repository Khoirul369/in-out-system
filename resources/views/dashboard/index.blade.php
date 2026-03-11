@extends('layouts.app')
@section('title', 'Dashboard')

@section('styles')
<style>
  .pm-info-card { margin-bottom: 16px; }
  .pm-info-card.has-pm { background: #f0fdf4; border-color: #bbf7d0; }
  .pm-info-card.no-pm { background: #fffbeb; border-color: #fde68a; }
  .pm-info-title { font-weight: 700; margin-bottom: 6px; }
  .pm-info-title.ok { color: #065f46; }
  .pm-info-title.warn { color: #92400e; }
  .pm-info-meta { color: var(--muted); font-size: 14px; }
  .pm-info-note { font-size: 14px; }
  .pm-info-note.ok { color: #065f46; }
  .pm-info-note.warn { color: #92400e; }
  .workflow-text { font-size: 14px; color: var(--muted); }
  .progress-pending-note { font-size: 13px; color: var(--muted); margin-top: 4px; }
  .badge-gap { margin-left: 8px; }
  .pm-pending-alert { margin-bottom: 16px; }
  .dash-grid { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 20px; align-items: start; }
  .dash-grid .card { margin-bottom: 0; }
  .notif-card { position: sticky; top: 90px; }
  .notif-item { padding: 12px 0; border-bottom: 1px solid var(--border); }
  .notif-item:last-child { border-bottom: none; }
  .notif-title { font-weight: 700; font-size: 14px; }
  .notif-msg { color: var(--muted); font-size: 13px; margin-top: 2px; }
  .notif-msg .notif-name { font-weight: 700; color: #0f172a; background: #e0f2fe; padding: 1px 4px; border-radius: 4px; }
  .notif-time { color: #94a3b8; font-size: 12px; margin-top: 4px; }
  .notif-empty { color: var(--muted); font-size: 14px; padding: 16px 0; text-align: center; }
  .notif-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
  .notif-badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 12px; font-weight: 700; background: #eff6ff; color: #1e40af; }
  @media (max-width: 980px) { .dash-grid { grid-template-columns: 1fr; } .notif-card { position: static; } }
</style>
@endsection

@section('content')
@php
  $statusBadge = [
    \App\Models\ResignRequest::STATUS_PENDING => 'badge-pending',
    \App\Models\ResignRequest::STATUS_APPROVED => 'badge-approved',
    \App\Models\ResignRequest::STATUS_APPROVED_HC => 'badge-approved_hc',
    \App\Models\ResignRequest::STATUS_REJECTED => 'badge-rejected',
    \App\Models\ResignRequest::STATUS_DONE => 'badge-done',
  ];
@endphp

<div class="page-head">
  <div>
    <div class="page-title">Dashboard</div>
    <div class="page-sub">Ringkasan pengajuan resign.</div>
  </div>
</div>

{{-- Notifikasi Checklist Pending --}}
@if($user->canAccessChecklist() && ($checklistPendingCount ?? 0) > 0)
  <div class="alert alert-warning pm-pending-alert">
    Ada <strong>{{ $checklistPendingCount }}</strong> pengajuan resign baru yang perlu diproses checklist.
    <a href="{{ route('checklist.index') }}" class="btn btn-primary btn-sm existing-link" style="margin-left: 12px;">Ke Halaman Checklist</a>
  </div>
@endif

{{-- Notifikasi Approval PM Pending --}}
@if($userIsPm && ($pmPendingCount ?? 0) > 0)
  <div class="alert alert-warning pm-pending-alert">
    Ada <strong>{{ $pmPendingCount }}</strong> pengajuan pengunduran diri baru menunggu persetujuan Anda.
    <a href="{{ route('approval.pm') }}" class="btn btn-primary btn-sm existing-link" style="margin-left: 12px;">Ke Halaman Approval PM</a>
  </div>
@endif

{{-- Notifikasi Verifikasi HC Pending --}}
@if($user->canVerifyHcResign() && ($hcPendingCount ?? 0) > 0)
  <div class="alert alert-warning pm-pending-alert">
    Ada <strong>{{ $hcPendingCount }}</strong> pengajuan resign baru yang perlu diverifikasi.
    <a href="{{ route('approval.hc') }}" class="btn btn-primary btn-sm existing-link" style="margin-left: 12px;">Ke Halaman Verifikasi HC</a>
  </div>
@endif

{{-- Notifikasi khusus HC observer read-only --}}
@if($user->isReadonlyHcObserver() && ($readonlyHcPendingCount ?? 0) > 0)
  <div class="alert alert-warning pm-pending-alert">
    Ada <strong>{{ $readonlyHcPendingCount }}</strong> karyawan yang mengajukan resign.
    <a href="{{ route('resign.list') }}" class="btn btn-outline btn-sm existing-link">Lihat List Pengajuan</a>
  </div>
@endif

{{-- Approval PM --}}
@if($userIsPm && $pmPending->isNotEmpty())
  <div class="card">
    <div class="card-title">⏳ Menunggu Approval PM <span class="badge badge-pending badge-gap">{{ $pmPending->count() }}</span></div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>Tanggal</th><th>Nama</th><th>Tanggal Terakhir Bekerja</th><th>Aksi</th></tr>
        </thead>
        <tbody>
          @foreach($pmPending as $r)
            <tr>
              <td>{{ $r->created_at->format('d M Y') }}</td>
              <td>{{ $r->employee->nama }}</td>
              <td>{{ $r->last_date->format('d M Y') }}</td>
              <td><a href="{{ route('resign.detail', $r->id) }}" class="btn btn-primary btn-sm">Proses</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endif

{{-- Pengajuan Resign Saya --}}
<div class="dash-grid">
<div class="card">
  <div class="card-title">Pengajuan Resign Saya</div>

  @if($myResigns->isEmpty())
    <div class="empty-state">Belum ada pengajuan resign.</div>
  @else
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Status</th>
            <th>Progress</th>
            <th>Tanggal Terakhir Bekerja</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($myResigns as $r)
            <tr>
              <td>{{ $r->created_at->format('d M Y') }}</td>
              <td><span class="badge {{ $statusBadge[$r->status] ?? 'badge-default' }}">{{ $r->getStatusLabel() }}</span></td>
              <td>
                @if($r->workflow_stage === \App\Models\ResignRequest::STAGE_TO_HC && isset($r->progress))
                  <div class="progress-wrap">
                    <div class="progress-bar"><div class="progress-fill" style="width:{{ $r->progress['percent'] }}%"></div></div>
                    <span class="progress-text">{{ $r->progress['percent'] }}%</span>
                  </div>
                  @if(!empty($r->progress['pending']))
                    <div class="progress-pending-note">Belum: {{ implode(', ', $r->progress['pending']) }}</div>
                  @endif
                @else
                  <span class="workflow-text">{{ $r->getWorkflowLabel() }}</span>
                @endif
              </td>
              <td>{{ $r->last_date->format('d M Y') }}</td>
              <td><a href="{{ route('resign.detail', $r->id) }}" class="btn btn-outline btn-sm">Detail</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

<div class="card notif-card">
  <div class="notif-head">
    <div class="card-title" style="margin:0;">Notifikasi</div>
    @if(($unreadCount ?? 0) > 0)
      <span class="notif-badge">{{ $unreadCount }} baru</span>
    @endif
  </div>

  @if(($notifications ?? collect())->isEmpty())
    <div class="notif-empty">Tidak ada notifikasi.</div>
  @else
    @foreach($notifications as $n)
      <div class="notif-item">
        <div class="notif-title">{{ $n->title }}</div>
        <div class="notif-msg">
          @if(!empty($n->data['employee_name']))
            @php
              $escapedMsg = e($n->message);
              $escapedName = e($n->data['employee_name']);
              $highlighted = '<span class="notif-name">' . $escapedName . '</span>';
              $msg = str_replace($escapedName, $highlighted, $escapedMsg);
            @endphp
            {!! $msg !!}
          @else
            {{ $n->message }}
          @endif
        </div>
        <div class="notif-time">{{ $n->created_at ? $n->created_at->diffForHumans() : '' }}</div>
        @if(!empty($n->data['resign_id']))
          <div class="mt-8">
            <a href="{{ route('resign.detail', $n->data['resign_id']) }}" class="btn btn-outline btn-sm">Lihat Pengajuan</a>
          </div>
        @endif
      </div>
    @endforeach
  @endif
</div>
</div>

{{-- Verifikasi HC --}}
@if($user->canVerifyHcResign() && $hcPending->isNotEmpty())
  <div class="card">
    <div class="card-title">⏳ Menunggu Verifikasi HC <span class="badge badge-pending badge-gap">{{ $hcPending->count() }}</span></div>
    <div class="table-wrap">
    <table class="table">
      <thead>
        <tr><th>Tanggal</th><th>Nama</th><th>Tanggal Terakhir Bekerja</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        @foreach($hcPending as $r)
          <tr>
            <td>{{ $r->created_at->format('d M Y') }}</td>
            <td>{{ $r->employee->nama }}</td>
            <td>{{ $r->last_date->format('d M Y') }}</td>
            <td><a href="{{ route('resign.detail', $r->id) }}" class="btn btn-primary btn-sm">Verifikasi</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
    </div>
  </div>
@endif

{{-- Ringkasan list resign khusus HC observer read-only --}}
@if($user->isReadonlyHcObserver())
  <div class="card">
    <div class="card-title">List Pengajuan Resign (Terbaru)</div>
    @if(($readonlyHcRecentResigns ?? collect())->isEmpty())
      <div class="empty-state">Belum ada pengajuan resign.</div>
    @else
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Nama Karyawan</th>
              <th>Status</th>
              <th>Tanggal Terakhir Bekerja</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach($readonlyHcRecentResigns as $r)
              <tr>
                <td>{{ $r->created_at->format('d M Y') }}</td>
                <td>{{ $r->employee->nama }}</td>
                <td><span class="badge {{ $statusBadge[$r->status] ?? 'badge-default' }}">{{ $r->getStatusLabel() }}</span></td>
                <td>{{ $r->last_date->format('d M Y') }}</td>
                <td><a href="{{ route('resign.detail', $r->id) }}" class="btn btn-outline btn-sm">Detail</a></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="mt-16">
        <a href="{{ route('resign.list') }}" class="btn btn-outline">Lihat lebih banyak</a>
      </div>
    @endif
  </div>
@endif
@endsection
