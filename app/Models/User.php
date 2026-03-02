<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    private const READONLY_HC_USER_ID = 32;
    private const READONLY_HC_USERNAME = 'erry.riyadi@muc.co.id';

    protected $table = 'users';
    public $timestamps = false;

    protected $fillable = [
        'username', 'password_hash', 'role', 'nama',
        'id_karyawan', 'divisi_posisi', 'pm_id', 'is_pm', 'checklist_admin', 'created_at',
    ];

    protected $hidden = ['password_hash'];
    protected $casts = [
        'is_pm' => 'boolean',
        'checklist_admin' => 'boolean',
    ];

    // Roles
    const ROLE_KARYAWAN = 'karyawan';
    const ROLE_HC       = 'hc';
    const ROLE_IT       = 'it';
    const ROLE_DOC      = 'doc';
    const ROLE_GA       = 'ga';
    const ROLE_FINANCE  = 'finance';

    const ADMIN_ROLES = ['hc', 'it', 'doc', 'ga', 'finance'];

    // Relations
    public function resignRequests(): HasMany
    {
        return $this->hasMany(ResignRequest::class, 'employees_id');
    }

    public function pm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pm_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'pm_id');
    }

    // Role checks
    public function isKaryawan(): bool
    {
        return $this->role === self::ROLE_KARYAWAN;
    }

    public function isHc(): bool
    {
        return $this->role === self::ROLE_HC || $this->getDepartment() === 'hc';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, self::ADMIN_ROLES, true);
    }

    public function isPm(): bool
    {
        return $this->is_pm || $this->subordinates()->exists();
    }

    public function hasSubordinates(): bool
    {
        return $this->subordinates()->exists();
    }

    // Department logic (sama seperti department_for_role() di PHP native)
    public function getDepartment(): ?string
    {
        $adminMap = ['hc' => 'hc', 'it' => 'it', 'doc' => 'doc', 'finance' => 'finance', 'ga' => 'ga'];
        if (isset($adminMap[$this->role])) {
            return $adminMap[$this->role];
        }

        if ($this->role === self::ROLE_KARYAWAN && $this->divisi_posisi) {
            $divisi = strtolower($this->divisi_posisi);

            if (str_contains($divisi, 'human capital') || str_contains($divisi, ' hc')) return 'hc';
            if (str_contains($divisi, 'finance')) return 'finance';
            if (str_contains($divisi, 'document center') || str_contains($divisi, 'doc center')) return 'doc';
            if (str_contains($divisi, 'it support')) return 'it';
            if (str_contains($divisi, 'general affair')) return 'ga';
        }

        return null;
    }

    public function canAccessChecklist(): bool
    {
        if ($this->isReadonlyHcObserver()) {
            return false;
        }

        // Semua karyawan di divisi checklist (HC, IT, Doc, Finance, GA) boleh akses
        // checklist, list pengajuan resign, dan master checklist.
        return $this->getDepartment() !== null;
    }

    public function isChecklistAdmin(): bool
    {
        if (!array_key_exists('checklist_admin', $this->attributes)) {
            // Backward-compatible fallback sebelum migration dijalankan.
            return $this->getDepartment() !== null;
        }

        return (bool) $this->checklist_admin;
    }

    public function getDepartmentLabel(): string
    {
        $labels = [
            'hc' => 'HC',
            'it' => 'IT Support',
            'doc' => 'Doc Center',
            'finance' => 'Finance',
            'ga' => 'GA',
        ];
        return $labels[$this->getDepartment() ?? ''] ?? '-';
    }

    public function isReadonlyHcObserver(): bool
    {
        return $this->isHc()
            && (int) $this->id === self::READONLY_HC_USER_ID
            && strtolower((string) $this->username) === self::READONLY_HC_USERNAME;
    }

    public function canVerifyHcResign(): bool
    {
        return $this->isHc() && !$this->isReadonlyHcObserver();
    }
}
