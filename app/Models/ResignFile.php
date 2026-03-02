<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResignFile extends Model
{
    protected $table = 'resign_files';
    public $timestamps = false;

    protected $fillable = [
        'resign_request_id',
        'title',
        'filename',
        'filepath',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function resignRequest(): BelongsTo
    {
        return $this->belongsTo(ResignRequest::class, 'resign_request_id');
    }
}

