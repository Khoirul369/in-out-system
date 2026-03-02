<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ResignRequest;
use App\Models\ResignChecklistItem;
use App\Models\Notification;
use Illuminate\Support\Facades\Schema;

class ApprovalController extends Controller
{
    // ============================================================
    // PM
    // ============================================================

    public function pmIndex()
    {
        $user = $this->getAuthUser();

        if (!$user->isPm()) {
            abort(403, 'Anda tidak memiliki akses PM.');
        }

        $subordinateIds = User::where('pm_id', $user->id)->pluck('id');
        $pending = ResignRequest::whereIn('employees_id', $subordinateIds)
            ->where('workflow_stage', ResignRequest::STAGE_TO_PM)
            ->with('employee')
            ->orderBy('created_at', 'desc')
            ->get();

        $history = ResignRequest::whereIn('employees_id', $subordinateIds)
            ->whereNotIn('workflow_stage', [ResignRequest::STAGE_TO_PM])
            ->with('employee')
            ->orderBy('created_at', 'desc')
            ->get();

        // Mark PM approval sebagai sudah dilihat
        session(['pm_seen_until' => now()]);
        
        return view('approval.pm', compact('user', 'pending', 'history'));
    }

    public function pmAction(Request $request)
    {
        $user = $this->getAuthUser();
        $hasRejectedStage = Schema::hasColumn('resign_request', 'rejected_stage');

        if (!$user->isPm()) {
            abort(403);
        }

        $request->validate([
            'id'          => 'required|integer',
            'action'      => 'required|in:approved,rejected',
            'keterangan'  => 'required|string',
        ], [
            'keterangan.required' => 'Keterangan wajib diisi.',
        ]);

        $resign = ResignRequest::findOrFail($request->id);

        // Validasi resign milik bawahan user
        $isSubordinate = User::where('id', $resign->employees_id)
            ->where('pm_id', $user->id)
            ->exists();

        if (!$isSubordinate) {
            abort(403, 'Anda bukan PM dari karyawan ini.');
        }

        if (!$resign->needsPmApproval()) {
            return redirect()->route('approval.pm')->with('error', 'Pengajuan tidak memerlukan approval PM.');
        }

        if ($request->action === 'approved') {
            if (!$resign->canTransitionTo(ResignRequest::STAGE_TO_HC_APPROVAL)) {
                return redirect()->route('approval.pm')->with('error', 'Transisi workflow PM tidak valid.');
            }

            $payload = [
                'approved_description' => $request->keterangan,
                'approved_at'          => now(),
                'approved_by'          => $user->id,
                'rejected_at'          => null,
                'rejected_by'          => null,
                'workflow_stage'       => ResignRequest::STAGE_TO_HC_APPROVAL,
                'status'               => ResignRequest::STATUS_APPROVED,
            ];
            if ($hasRejectedStage) {
                $payload['rejected_stage'] = null;
            }
            $resign->update($payload);

            // Notifikasi ke HC
            $hcUsers = User::where('role', 'hc')->get();
            foreach ($hcUsers as $hc) {
                Notification::send(
                    $hc->id,
                    'resign_approved_pm',
                    'Pengajuan Resign Disetujui PM',
                    $resign->employee->nama . ' telah disetujui PM. Mohon segera diverifikasi.',
                    ['resign_id' => $resign->id]
                );
            }

            // Notifikasi ke karyawan
            Notification::send(
                $resign->employees_id,
                'resign_approved_pm',
                'Pengajuan Resign Disetujui',
                'Pengajuan resign Anda telah disetujui oleh ' . $user->nama . '. Menunggu verifikasi HC.',
                ['resign_id' => $resign->id]
            );

            return redirect()->route('approval.pm')->with('success', 'Pengajuan berhasil di-approve.');
        } else {
            if (!$resign->canTransitionTo(ResignRequest::STAGE_PM_REJECTED)) {
                return redirect()->route('approval.pm')->with('error', 'Transisi workflow PM tidak valid.');
            }

            $payload = [
                'approved_description' => $request->keterangan,
                'approved_at'          => null,
                'approved_by'          => null,
                'rejected_at'          => now(),
                'rejected_by'          => $user->id,
                'workflow_stage'       => ResignRequest::STAGE_PM_REJECTED,
                'status'               => ResignRequest::STATUS_REJECTED,
            ];
            if ($hasRejectedStage) {
                $payload['rejected_stage'] = 'pm';
            }
            $resign->update($payload);

            // Notifikasi ke karyawan
            Notification::send(
                $resign->employees_id,
                'resign_rejected_pm',
                'Pengajuan Resign Ditolak',
                'Pengajuan resign Anda ditolak oleh ' . $user->nama . '. Alasan: ' . $request->keterangan,
                ['resign_id' => $resign->id]
            );

            return redirect()->route('approval.pm')->with('success', 'Pengajuan berhasil di-reject.');
        }
    }

    // ============================================================
    // HC
    // ============================================================

    public function hcIndex()
    {
        $user = $this->getAuthUser();

        if (!$user->canVerifyHcResign()) {
            abort(403, 'Anda tidak memiliki akses HC.');
        }

        $pending = ResignRequest::where('workflow_stage', ResignRequest::STAGE_TO_HC_APPROVAL)
            ->where('employees_id', '!=', $user->id)
            ->with('employee')
            ->orderBy('created_at', 'desc')
            ->get();

        $history = ResignRequest::whereNotIn('workflow_stage', [
                ResignRequest::STAGE_TO_PM,
                ResignRequest::STAGE_TO_HC_APPROVAL,
            ])
            ->where('employees_id', '!=', $user->id)
            ->with('employee')
            ->orderBy('created_at', 'desc')
            ->get();

        // Mark HC verification sebagai sudah dilihat
        session(['hc_seen_until' => now()]);
        
        return view('approval.hc', compact('user', 'pending', 'history'));
    }

    public function markPmSeen(Request $request)
    {
        $user = $this->getAuthUser();
        if (!$user->isPm()) {
            abort(403);
        }
        session(['pm_seen_until' => now()]);
        return response()->json(['success' => true]);
    }

    public function markHcSeen(Request $request)
    {
        $user = $this->getAuthUser();
        if (!$user->canVerifyHcResign()) {
            abort(403);
        }
        session(['hc_seen_until' => now()]);
        return response()->json(['success' => true]);
    }

    public function hcAction(Request $request)
    {
        $user = $this->getAuthUser();
        $hasRejectedStage = Schema::hasColumn('resign_request', 'rejected_stage');

        if (!$user->canVerifyHcResign()) {
            abort(403);
        }

        $request->validate([
            'id'         => 'required|integer',
            'action'     => 'required|in:approved,rejected',
            'keterangan' => 'required|string',
        ], [
            'keterangan.required' => 'Keterangan wajib diisi.',
        ]);

        $resign = ResignRequest::findOrFail($request->id);

        if ((int) $resign->employees_id === (int) $user->id) {
            return redirect()->route('approval.hc')->with('error', 'Tidak bisa verifikasi pengajuan sendiri.');
        }

        if (!$resign->needsHcApproval()) {
            return redirect()->route('approval.hc')->with('error', 'Pengajuan tidak memerlukan verifikasi HC.');
        }

        if ($request->action === 'approved') {
            if (!$resign->canTransitionTo(ResignRequest::STAGE_TO_HC)) {
                return redirect()->route('approval.hc')->with('error', 'Transisi workflow HC tidak valid.');
            }

            $payload = [
                'approved_hc_description' => $request->keterangan,
                'approved_hc_at'          => now(),
                'approved_hc_by'          => $user->id,
                'rejected_at'             => null,
                'rejected_by'             => null,
                'workflow_stage'          => ResignRequest::STAGE_TO_HC,
                'status'                  => ResignRequest::STATUS_APPROVED_HC,
            ];
            if ($hasRejectedStage) {
                $payload['rejected_stage'] = null;
            }
            $resign->update($payload);

            // Hapus item lama dulu (jika ada dari request sebelumnya) lalu buat fresh dengan done=0
            ResignChecklistItem::where('resign_request_id', $resign->id)->delete();
            ResignChecklistItem::createDefaultItems($resign->id);

            // Notifikasi ke semua departemen
            $deptRoles = ['hc', 'it', 'doc', 'finance', 'ga'];
            $deptUsersQuery = User::whereIn('role', $deptRoles);
            if (Schema::hasColumn('users', 'checklist_admin')) {
                $deptUsersQuery->where('checklist_admin', 1);
            }
            $deptUsers = $deptUsersQuery->get();
            foreach ($deptUsers as $deptUser) {
                Notification::send(
                    $deptUser->id,
                    'checklist_assigned',
                    'Checklist Resign Baru',
                    'Ada checklist resign baru untuk ' . $resign->employee->nama . '. Mohon segera diproses.',
                    ['resign_id' => $resign->id]
                );
            }

            // Notifikasi ke karyawan
            Notification::send(
                $resign->employees_id,
                'resign_approved_hc',
                'Pengajuan Resign Diverifikasi HC',
                'Pengajuan resign Anda telah diverifikasi HC. Proses checklist sedang berjalan.',
                ['resign_id' => $resign->id]
            );

            return redirect()->route('approval.hc')->with('success', 'Pengajuan berhasil diverifikasi.');
        } else {
            if (!$resign->canTransitionTo(ResignRequest::STAGE_HC_REJECTED)) {
                return redirect()->route('approval.hc')->with('error', 'Transisi workflow HC tidak valid.');
            }

            $payload = [
                'approved_hc_description' => $request->keterangan,
                'approved_hc_at'          => null,
                'approved_hc_by'          => null,
                'rejected_at'             => now(),
                'rejected_by'             => $user->id,
                'workflow_stage'          => ResignRequest::STAGE_HC_REJECTED,
                'status'                  => ResignRequest::STATUS_REJECTED,
            ];
            if ($hasRejectedStage) {
                $payload['rejected_stage'] = 'hc';
            }
            $resign->update($payload);

            // Notifikasi ke karyawan
            Notification::send(
                $resign->employees_id,
                'resign_rejected_hc',
                'Pengajuan Resign Tidak Diverifikasi',
                'Pengajuan resign Anda tidak diverifikasi HC. Alasan: ' . $request->keterangan,
                ['resign_id' => $resign->id]
            );

            return redirect()->route('approval.hc')->with('success', 'Pengajuan berhasil ditolak.');
        }
    }

}
