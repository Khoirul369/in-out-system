<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class ResignChecklistItem extends Model
{
    protected $table = 'resign_checklist_items';
    public $timestamps = false;

    protected $fillable = [
        'resign_request_id', 'department', 'item_key', 'item_label',
        'pic', 'pj', 'keterangan', 'done', 'done_at', 'done_by', 'created_at',
    ];

    protected $casts = [
        'done' => 'boolean',
        'done_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    const DEPARTMENT_LABELS = [
        'hc'      => 'HC',
        'it'      => 'IT Support',
        'doc'     => 'Doc Center',
        'finance' => 'Finance',
        'ga'      => 'GA',
    ];

    const CHECKLIST_TEMPLATES = [
        'hc' => [
            'surat_ket_kerja'  => 'Surat keterangan kerja/magang',
            'nonaktif_idcard'  => 'Non aktif ID card',
            'bpjs'             => 'Terkait BPJS',
        ],
        'it' => [
            'kembali_laptop'   => 'Mengembalikan laptop',
            'nonaktif_akun'    => 'Nonaktif akun',
            'nonaktif_software'=> 'Nonaktif software',
        ],
        'doc' => [
            'kembali_buku'     => 'Mengembalikan buku (jika ada)',
            'arsip_hardcopy'   => 'Arsip hardcopy ke klien & Doc Center',
            'arsip_onedrive'   => 'Arsip One Drive',
        ],
        'finance' => [
            'cek_advance'      => 'Cek advance',
            'cek_pinjaman'     => 'Cek pinjaman',
            'tabungan_kurban'  => 'Tabungan kurban',
        ],
        'ga' => [
            'nonaktif_gocorps' => 'Non Aktif Akun GoCorps',
        ],
    ];

    // Relations
    public function resignRequest(): BelongsTo
    {
        return $this->belongsTo(ResignRequest::class, 'resign_request_id');
    }

    public function doneByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }

    // Generate default checklist items untuk resign request
    public static function createDefaultItems(int $resignRequestId): void
    {
        foreach (self::CHECKLIST_TEMPLATES as $dept => $defaultItems) {
            $itemsWithPic = [];

            if (Schema::hasTable('checklist_masters')) {
                $masterQuery = ChecklistMaster::where('department', $dept)
                    ->where('is_active', true);

                $masters = $masterQuery
                    ->orderBy('id')
                    ->get(['item_key', 'item_label', 'default_pic']);

                if ($masters->isNotEmpty()) {
                    foreach ($masters as $master) {
                        if (isset($itemsWithPic[$master->item_key])) {
                            continue;
                        }
                        $itemsWithPic[$master->item_key] = [
                            'label' => $master->item_label,
                            'pic' => $master->default_pic,
                        ];
                    }
                }
            }

            if (empty($itemsWithPic)) {
                foreach ($defaultItems as $key => $label) {
                    $itemsWithPic[$key] = ['label' => $label, 'pic' => null];
                }
            }

            foreach ($itemsWithPic as $key => $itemMeta) {
                self::firstOrCreate([
                    'resign_request_id' => $resignRequestId,
                    'department'        => $dept,
                    'item_key'          => $key,
                ], [
                    'item_label' => $itemMeta['label'],
                    'pic'        => $itemMeta['pic'],
                    'done'       => 0,
                    'created_at' => now(),
                ]);
            }
        }
    }
}
