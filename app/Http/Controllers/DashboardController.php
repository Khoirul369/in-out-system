<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ResignRequest;
use App\Models\Notification;

class DashboardController extends Controller
{
    public function index()
    {
        $user = $this->getAuthUser();

        $myResigns = ResignRequest::where('employees_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Progress checklist untuk resign yang di tahap to_hc
        foreach ($myResigns as $r) {
            if ($r->workflow_stage === ResignRequest::STAGE_TO_HC) {
                $r->progress = $r->getChecklistProgress();
            }
        }

        // PM data
        $pmPending = collect();
        $pmPendingCount = 0;
        $userIsPm = $user->isPm();
        $readonlyHcPendingCount = 0;
        $readonlyHcRecentResigns = collect();

        if ($userIsPm) {
            // Ambil bawahan dari pm_id
            $subordinateIds = User::where('pm_id', $user->id)->pluck('id');
            $pmSeenUntil = session('pm_seen_until');
            
            $pmQuery = ResignRequest::whereIn('employees_id', $subordinateIds)
                ->where('workflow_stage', ResignRequest::STAGE_TO_PM);
            
            // Hitung yang baru (belum dilihat)
            $pmPendingCount = (clone $pmQuery)
                ->when($pmSeenUntil, function ($q) use ($pmSeenUntil) {
                    $q->where('created_at', '>', $pmSeenUntil);
                })
                ->count();
            
            $pmPending = $pmQuery
                ->with('employee')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($user->isReadonlyHcObserver()) {
            $seenUntil = session('readonly_hc_seen_until');

            $readonlyHcPendingCount = ResignRequest::where('employees_id', '!=', $user->id)
                ->whereNotIn('workflow_stage', [
                    ResignRequest::STAGE_COMPLETED,
                    ResignRequest::STAGE_PM_REJECTED,
                    ResignRequest::STAGE_HC_REJECTED,
                ])
                ->when($seenUntil, function ($q) use ($seenUntil) {
                    $q->where('created_at', '>', $seenUntil);
                })
                ->count();

            $readonlyHcRecentResigns = ResignRequest::where('employees_id', '!=', $user->id)
                ->with('employee')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        }

        // HC data
        $hcPending = collect();
        $hcPendingCount = 0;
        if ($user->canVerifyHcResign()) {
            $hcSeenUntil = session('hc_seen_until');
            
            $hcQuery = ResignRequest::where('workflow_stage', ResignRequest::STAGE_TO_HC_APPROVAL)
                ->where('employees_id', '!=', $user->id);
            
            // Hitung yang baru (belum dilihat)
            $hcPendingCount = (clone $hcQuery)
                ->when($hcSeenUntil, function ($q) use ($hcSeenUntil) {
                    $q->where('created_at', '>', $hcSeenUntil);
                })
                ->count();
            
            $hcPending = $hcQuery
                ->with('employee')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Admin list (semua resign) untuk admin role
        $allResigns = collect();
        if ($user->isAdmin()) {
            $allResigns = ResignRequest::with('employee')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Checklist data
        $checklistItems = collect();
        $checklistPendingCount = 0;
        if ($user->canAccessChecklist()) {
            $dept = $user->getDepartment();
            $checklistSeenUntil = session('checklist_seen_until');
            
            $checklistQuery = ResignRequest::where('workflow_stage', ResignRequest::STAGE_TO_HC)
                ->where('employees_id', '!=', $user->id);
            
            // Hitung yang baru (belum dilihat)
            $checklistPendingCount = (clone $checklistQuery)
                ->when($checklistSeenUntil, function ($q) use ($checklistSeenUntil) {
                    $q->where('created_at', '>', $checklistSeenUntil);
                })
                ->count();
            
            $checklistItems = $checklistQuery
                ->with(['employee', 'checklistItems' => function ($q) use ($dept) {
                    $q->where('department', $dept);
                }])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Notifications
        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        $unreadCount = Notification::where('user_id', $user->id)->where('is_read', false)->count();

        // PM info untuk karyawan
        $myPm = null;
        if ($user->isKaryawan() && $user->pm_id) {
            $myPm = User::find($user->pm_id);
        }

        return view('dashboard.index', compact(
            'user', 'myResigns', 'pmPending', 'hcPending',
            'allResigns', 'checklistItems', 'notifications',
            'unreadCount', 'myPm', 'userIsPm', 'readonlyHcPendingCount', 'readonlyHcRecentResigns',
            'checklistPendingCount', 'pmPendingCount', 'hcPendingCount'
        ));
    }
}
