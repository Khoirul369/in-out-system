@extends('layouts.app')
@section('title', 'List Pengajuan Resign')

@section('styles')
<style>
  .resign-list-name { font-weight: 500; font-size: 14px; }
  .resign-list-workflow { color: var(--muted); font-size: 14px; }
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
    <div class="page-title">List Pengajuan Resign</div>
    <div class="page-sub">Daftar seluruh pengajuan resign sesuai akses role Anda.</div>
  </div>
</div>

<div class="card">
  <div class="card-title">Data Pengajuan</div>

  @if($resigns->isEmpty())
    <div class="empty-state">Belum ada data pengajuan resign.</div>
  @else
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Nama Karyawan</th>
            <th>Status</th>
            <th>Alur</th>
            <th>Tanggal Terakhir Bekerja</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($resigns as $r)
            <tr>
              <td>{{ $r->created_at->format('d M Y') }}</td>
              <td class="resign-list-name">{{ $r->employee->nama }}</td>
              <td><span class="badge {{ $statusBadge[$r->status] ?? 'badge-default' }}">{{ $r->getStatusLabel() }}</span></td>
              <td class="resign-list-workflow">{{ $r->getWorkflowLabel() }}</td>
              <td>{{ $r->last_date->format('d M Y') }}</td>
              <td><a href="{{ route('resign.detail', $r->id) }}" class="btn btn-outline btn-sm">Detail</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection
