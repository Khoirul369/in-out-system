<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResignRequest extends Model
{
    protected $table = 'resign_request';
    public $timestamps = false;

    protected $fillable = [
        'employees_id', 'resign_file_method', 'reason', 'last_date',
        'description', 'resign_file_path', 'resign_filename',
        'status', 'workflow_stage',
        'approved_description', 'approved_at', 'approved_by',
        'approved_hc_description', 'approved_hc_at', 'approved_hc_by',
        'rejected_at', 'rejected_by', 'rejected_stage',
        'created_at', 'created_by', 'updated_at', 'updated_by',
    ];

    protected $casts = [
        'last_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'approved_at' => 'datetime',
        'approved_hc_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING     = 'pending';
    const STATUS_APPROVED    = 'approved';
    const STATUS_APPROVED_HC = 'approved_hc';
    const STATUS_REJECTED    = 'rejected';
    const STATUS_DONE        = 'done';

    // Workflow stages
    const STAGE_TO_PM          = 'to_pm';
    const STAGE_TO_HC_APPROVAL = 'to_hc_approval';
    const STAGE_TO_HC          = 'to_hc';
    const STAGE_COMPLETED      = 'completed';
    const STAGE_PM_REJECTED    = 'pm_rejected';
    const STAGE_HC_REJECTED    = 'hc_rejected';

    private const ALLOWED_STAGE_TRANSITIONS = [
        self::STAGE_TO_PM => [
            self::STAGE_TO_HC_APPROVAL,
            self::STAGE_PM_REJECTED,
        ],
        self::STAGE_TO_HC_APPROVAL => [
            self::STAGE_TO_HC,
            self::STAGE_HC_REJECTED,
        ],
        self::STAGE_TO_HC => [
            self::STAGE_COMPLETED,
        ],
        self::STAGE_PM_REJECTED => [],
        self::STAGE_HC_REJECTED => [],
        self::STAGE_COMPLETED => [],
    ];

    private const TERMINAL_STAGES = [
        self::STAGE_COMPLETED,
        self::STAGE_PM_REJECTED,
        self::STAGE_HC_REJECTED,
    ];

    // Relations
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employees_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvedHcBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_hc_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(ResignChecklistItem::class, 'resign_request_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ResignFile::class, 'resign_request_id');
    }

    // Status checks
    public function needsPmApproval(): bool
    {
        return $this->workflow_stage === self::STAGE_TO_PM;
    }

    public function needsHcApproval(): bool
    {
        return $this->workflow_stage === self::STAGE_TO_HC_APPROVAL;
    }

    public function isInChecklistStage(): bool
    {
        return $this->workflow_stage === self::STAGE_TO_HC;
    }

    public function isCompleted(): bool
    {
        return $this->workflow_stage === self::STAGE_COMPLETED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function canTransitionTo(string $nextStage): bool
    {
        $current = (string) $this->workflow_stage;
        if ($current === $nextStage) {
            return true;
        }

        return in_array($nextStage, self::ALLOWED_STAGE_TRANSITIONS[$current] ?? [], true);
    }

    // Labels
    public function getStatusLabel(): string
    {
        $map = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved by PM',
            self::STATUS_APPROVED_HC => 'Approved by HC',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_DONE => 'Done',
        ];
        return $map[$this->status] ?? $this->status;
    }

    public function getWorkflowLabel(): string
    {
        $map = [
            self::STAGE_TO_PM => 'Menunggu Approval PM',
            self::STAGE_PM_REJECTED => 'Ditolak PM',
            self::STAGE_TO_HC_APPROVAL => 'Menunggu Verifikasi HC',
            self::STAGE_HC_REJECTED => 'Ditolak HC',
            self::STAGE_TO_HC => 'Proses Divisi',
            self::STAGE_COMPLETED => 'Selesai',
        ];
        return $map[$this->workflow_stage] ?? $this->workflow_stage;
    }

    public function isTerminalStage(): bool
    {
        return in_array((string) $this->workflow_stage, self::TERMINAL_STAGES, true);
    }

    // Checklist progress
    public function getChecklistProgress(): array
    {
        $items = $this->checklistItems;
        $total = $items->count();
        $done  = $items->where('done', 1)->count();
        $percent = $total > 0 ? (int) round(($done / $total) * 100) : 0;

        $deptLabels = ResignChecklistItem::DEPARTMENT_LABELS;
        $pending = [];
        $completed = [];

        foreach ($items->groupBy('department') as $dept => $deptItems) {
            $label = $deptLabels[$dept] ?? strtoupper($dept);
            if ($deptItems->where('done', 0)->count() > 0) {
                $pending[] = $label;
            } else {
                $completed[] = $label;
            }
        }

        return compact('total', 'done', 'percent', 'pending', 'completed');
    }
}
