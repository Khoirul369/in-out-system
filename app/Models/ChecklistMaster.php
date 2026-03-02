<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistMaster extends Model
{
    protected $table = 'checklist_masters';
    public $timestamps = false;

    protected $fillable = [
        'department',
        'admin_user_id',
        'item_key',
        'item_label',
        'default_pic',
        'updated_by',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
