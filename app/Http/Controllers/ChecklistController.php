<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\ResignRequest;
use App\Models\ResignChecklistItem;
use App\Models\Notification;

class ChecklistController extends Controller
{
    private const CHECKLIST_REQUIRED_MSG = 'Checklist wajib diisi Keterangan sebelum disimpan maupun dicentang.';

    public function index(Request $request)
    {
        $user = $this->getAuthUser();

        if (!$user->canAccessChecklist()) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $dept = $user->getDepartment();
        $resignId = $request->get('id');

        $query = ResignRequest::where('workflow_stage', ResignRequest::STAGE_TO_HC)
            ->where('employees_id', '!=', $user->id)
            ->with(['employee', 'checklistItems' => function ($q) use ($dept) {
                $q->where('department', $dept)->with('doneByUser')->orderBy('item_key');
            }]);

        if ($resignId) {
            $query->where('id', $resignId);
        }

        $resigns = $query->orderBy('created_at', 'desc')->get();

        // Generate checklist items jika belum ada
        foreach ($resigns as $r) {
            ResignChecklistItem::createDefaultItems($r->id);
            // Reload items setelah generate
            $r->load(['checklistItems' => function ($q) use ($dept) {
                $q->where('department', $dept)->with('doneByUser')->orderBy('item_key');
            }]);
        }

        // Mark checklist sebagai sudah dilihat
        session(['checklist_seen_until' => now()]);
        
        return view('checklist.index', compact('user', 'resigns', 'dept'));
    }

    public function markSeen(Request $request)
    {
        $user = $this->getAuthUser();
        if (!$user->canAccessChecklist()) {
            abort(403);
        }
        session(['checklist_seen_until' => now()]);
        return response()->json(['success' => true]);
    }

    public function update(Request $request)
    {
        $user = $this->getAuthUser();

        if (!$user->canAccessChecklist()) {
            abort(403);
        }

        $request->validate([
            'resign_request_id' => 'required|integer',
            'items'             => 'required|array',
        ]);

        $dept = $user->getDepartment();
        $resignId = (int) $request->resign_request_id;

        $resign = ResignRequest::findOrFail($resignId);

        // Hardening: user tidak boleh memproses checklist untuk pengajuan resign miliknya sendiri.
        if ((int) $resign->employees_id === (int) $user->id) {
            $message = 'Anda tidak dapat memproses checklist pengajuan resign milik sendiri.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }
            return redirect()->route('checklist.index')->with('error', $message);
        }

        if (!$resign->isInChecklistStage()) {
            return response()->json(['success' => false, 'message' => 'Pengajuan tidak dalam tahap checklist.'], 422);
        }

        // Update setiap item checklist
        foreach ($request->items as $itemKey => $itemData) {
            $item = ResignChecklistItem::where('resign_request_id', $resignId)
                ->where('department', $dept)
                ->where('item_key', $itemKey)
                ->first();

            if (!$item) continue;

            $done = !empty($itemData['done']);
            $keterangan = trim((string) ($itemData['keterangan'] ?? ''));

            if ($done && $keterangan === '') {
                $message = self::CHECKLIST_REQUIRED_MSG;
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                return back()->withErrors(['items' => $message])->withInput();
            }

            $payload = [
                'done'       => $done,
                'done_at'    => $done ? ($item->done_at ?? now()) : null,
                'pic'        => $item->pic,
                'pj'         => $itemData['pj'] ?? $item->pj,
                'keterangan' => $keterangan !== '' ? $keterangan : null,
            ];
            if (Schema::hasColumn($item->getTable(), 'done_by')) {
                // Set done_by hanya jika belum pernah di-set atau jika di-uncheck (set null)
                if ($done && !$item->done_by) {
                    $payload['done_by'] = $user->id;
                } elseif (!$done) {
                    $payload['done_by'] = null;
                }
                // Jika sudah done dan sudah ada done_by, pertahankan (tidak di-overwrite)
            }
            $item->update($payload);
        }

        // Cek apakah semua checklist sudah selesai
        $allDone = ResignChecklistItem::where('resign_request_id', $resignId)
            ->where('done', 0)
            ->doesntExist();

        if ($allDone) {
            if (!$resign->canTransitionTo(ResignRequest::STAGE_COMPLETED)) {
                $message = 'Transisi workflow checklist tidak valid.';
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 422);
                }
                return redirect()->route('checklist.index')->with('error', $message);
            }

            $resign->update([
                'workflow_stage' => ResignRequest::STAGE_COMPLETED,
                'status'         => ResignRequest::STATUS_DONE,
            ]);

            // Notifikasi ke karyawan bahwa proses selesai
            Notification::send(
                $resign->employees_id,
                'resign_completed',
                'Proses Resign Selesai',
                'Semua checklist resign Anda telah selesai. Proses resign dinyatakan complete.',
                ['resign_id' => $resign->id]
            );
        }

        if ($request->ajax()) {
            $savedBy = $user->nama ?? $user->username;
            $msg = $allDone ? 'Semua checklist selesai!' : 'Checklist berhasil disimpan.';
            return response()->json([
                'success'      => true,
                'all_done'     => $allDone,
                'message'      => $msg,
                'saved_by'     => $savedBy,
                'redirect_url' => route('checklist.index'),
            ]);
        }

        return redirect()->route('checklist.index')
            ->with('success', $allDone ? 'Semua checklist selesai!' : 'Checklist berhasil disimpan.');
    }

}
