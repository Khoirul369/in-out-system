@extends('layouts.app')
@section('title', 'Verifikasi HC')

@section('styles')
<style>
  .approval-badge-gap { margin-left: 8px; }
  .approval-name { font-weight: 500; }
  .approval-reason { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
@endsection

@section('content')
<div class="page-head">
  <div>
    <div class="page-title">Verifikasi HC</div>
    <div class="page-sub">Validasi akhir sebelum proses checklist lintas divisi dimulai.</div>
  </div>
</div>

<div class="card">
  <div class="card-title">Menunggu Verifikasi <span class="badge badge-pending approval-badge-gap">{{ $pending->count() }}</span></div>
  @if($pending->isEmpty())
    <div class="empty-state">Tidak ada pengajuan yang perlu diverifikasi.</div>
  @else
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>Tanggal</th><th>Nama</th><th>Alasan</th><th>Tgl Terakhir</th><th>Aksi</th></tr>
        </thead>
        <tbody>
          @foreach($pending as $r)
            <tr>
              <td>{{ $r->created_at->format('d M Y') }}</td>
              <td class="approval-name">{{ $r->employee->nama }}</td>
              <td class="approval-reason">{{ Str::limit($r->reason, 60) }}</td>
              <td>{{ $r->last_date->format('d M Y') }}</td>
              <td><a href="{{ route('resign.detail', $r->id) }}" class="btn btn-primary btn-sm">Verifikasi</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

@if($history->isNotEmpty())
  <div class="card">
    <div class="card-title">Riwayat</div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>Tanggal</th><th>Nama</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
          @foreach($history as $r)
            <tr>
              <td>{{ $r->created_at->format('d M Y') }}</td>
              <td>{{ $r->employee->nama }}</td>
              <td><span class="badge badge-{{ $r->status }}">{{ $r->getStatusLabel() }}</span></td>
              <td><a href="{{ route('resign.detail', $r->id) }}" class="btn btn-outline btn-sm">Detail</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endif
@endsection
