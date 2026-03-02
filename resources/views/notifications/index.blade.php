@extends('layouts.app')
@section('title', 'Notifikasi')

@section('styles')
<style>
  .notif-wrap { max-width: 700px; margin: 0 auto; }
  .notif-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
  .notif-card-title { margin: 0; }
  .notif-empty { color: #64748b; font-size: 13px; text-align: center; padding: 24px; }
  .notif-item { display: flex; gap: 12px; padding: 14px 0; border-bottom: 1px solid #e2e8f0; }
  .notif-item.unread { background: #eff6ff; margin: 0 -24px; padding: 14px 24px; }
  .notif-dot { width: 8px; height: 8px; border-radius: 50%; margin-top: 6px; flex-shrink: 0; background: #e2e8f0; }
  .notif-item.unread .notif-dot { background: #2563eb; }
  .notif-body { flex: 1; }
  .notif-title { font-size: 14px; font-weight: 500; }
  .notif-item.unread .notif-title { font-weight: 600; }
  .notif-message { font-size: 13px; color: #475569; margin-top: 2px; }
  .notif-time { font-size: 12px; color: #94a3b8; margin-top: 4px; }
  .notif-link { margin-top: 8px; }
  .notif-pagination { margin-top: 16px; }
  @media (max-width: 768px) {
    .notif-head { flex-direction: column; align-items: flex-start; gap: 8px; }
  }
</style>
@endsection

@section('content')
<div class="page-head notif-wrap">
  <div>
    <div class="page-title">Notifikasi</div>
    <div class="page-sub">Riwayat update status pengajuan resign Anda.</div>
  </div>
</div>

<div class="card notif-wrap">
  <div class="notif-head">
    <div class="card-title notif-card-title">Daftar Notifikasi</div>
    <form method="POST" action="{{ route('notifications.read-all') }}">
      @csrf
      <button type="submit" class="btn btn-outline btn-sm">Tandai Semua Dibaca</button>
    </form>
  </div>

  @if($notifications->isEmpty())
    <div class="notif-empty">Tidak ada notifikasi.</div>
  @else
    @foreach($notifications as $notif)
      <div class="notif-item {{ !$notif->is_read ? 'unread' : '' }}">
        <div class="notif-dot"></div>
        <div class="notif-body">
          <div class="notif-title">{{ $notif->title }}</div>
          <div class="notif-message">{{ $notif->message }}</div>
          <div class="notif-time">{{ $notif->created_at->diffForHumans() }}</div>
          @if(!empty($notif->data['resign_id']))
            <a href="{{ route('resign.detail', $notif->data['resign_id']) }}" class="btn btn-outline btn-sm notif-link">Lihat Pengajuan</a>
          @endif
        </div>
      </div>
    @endforeach

    <div class="notif-pagination">
      {{ $notifications->links() }}
    </div>
  @endif
</div>
@endsection
