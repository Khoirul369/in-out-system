<?php

namespace App\Http\Controllers;

use App\Models\ChecklistMaster;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChecklistMasterController extends Controller
{
    public function index(): View
    {
        $user = $this->getAuthUser();
        if (!$user->canAccessChecklist()) {
            abort(403, 'Anda tidak memiliki akses ke Master Checklist.');
        }

        $department = $user->getDepartment();
        $items = ChecklistMaster::where('department', $department)
            ->where(function ($q) use ($user) {
                $q->where('admin_user_id', $user->id)->orWhereNull('admin_user_id');
            })
            ->orderBy('item_label')
            ->get();

        // Master item yang sudah muncul di checklist resign dan sudah dicentang (done=1) → aksi edit/hapus dinonaktifkan
        $lockedItemKeys = \App\Models\ResignChecklistItem::where('done', 1)
            ->where('department', $department)
            ->distinct()
            ->pluck('item_key')
            ->all();
        $masterIdsLocked = $items->whereIn('item_key', $lockedItemKeys)->pluck('id')->all();

        $picOptions = $this->getPicOptionsByDepartment($department);
        $editingItem = null;
        $editingId = (int) old('edit_item_id');
        if ($editingId > 0) {
            $editingItem = $items->firstWhere('id', $editingId);
        }

        return view('checklist.master', compact('user', 'department', 'items', 'picOptions', 'editingItem', 'masterIdsLocked'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->getAuthUser();
        if (!$user->canAccessChecklist()) {
            abort(403);
        }

        $department = $user->getDepartment();
        $picOptions = $this->getPicOptionsByDepartment($department);
        $picNames = array_column($picOptions, 'nama');
        $validated = $request->validate([
            'item_label' => 'required|string|max:255',
            'default_pic' => ['nullable', 'string', 'max:255', Rule::in($picNames)],
            'is_active' => 'nullable|boolean',
        ]);

        $itemKey = $this->generateUniqueItemKey($department, (int) $user->id, $validated['item_label']);

        ChecklistMaster::create([
            'department' => $department,
            'admin_user_id' => $user->id,
            'item_key' => $itemKey,
            'item_label' => $validated['item_label'],
            'default_pic' => $validated['default_pic'] ?: null,
            'updated_by' => $user->id,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('checklist.master.index')->with('success', 'Master checklist berhasil ditambahkan.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = $this->getAuthUser();
        if (!$user->canAccessChecklist()) {
            abort(403);
        }

        $master = ChecklistMaster::findOrFail($id);
        if ((int) $master->admin_user_id !== (int) $user->id) {
            abort(403, 'Anda hanya bisa mengubah item milik admin Anda.');
        }

        $usedAndChecked = $this->isMasterUsedAndChecked($master);

        if ($usedAndChecked) {
            // Item sudah dipakai dan dicentang: hanya izinkan ubah status Active/Inactive
            $validated = $request->validate([
                'is_active' => 'nullable|boolean',
                'edit_item_id' => 'nullable|integer',
            ]);
            $master->update([
                'updated_by' => $user->id,
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'updated_at' => now(),
            ]);
        } else {
            $department = $user->getDepartment();
            $picOptions = $this->getPicOptionsByDepartment($department);
            $picNames = array_column($picOptions, 'nama');
            $validated = $request->validate([
                'item_label' => 'required|string|max:255',
                'default_pic' => ['nullable', 'string', 'max:255', Rule::in($picNames)],
                'is_active' => 'nullable|boolean',
                'edit_item_id' => 'nullable|integer',
            ]);

            $currentLabel = (string) $master->item_label;
            $newLabel = (string) $validated['item_label'];
            $itemKey = (string) $master->item_key;
            if (Str::lower($currentLabel) !== Str::lower($newLabel)) {
                $itemKey = $this->generateUniqueItemKey($department, (int) $user->id, $newLabel, (int) $master->id);
            }

            $master->update([
                'item_key' => $itemKey,
                'item_label' => $validated['item_label'],
                'default_pic' => $validated['default_pic'] ?: null,
                'updated_by' => $user->id,
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('checklist.master.index')->with('success', 'Master checklist berhasil diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $user = $this->getAuthUser();
        if (!$user->canAccessChecklist()) {
            abort(403);
        }

        $master = ChecklistMaster::findOrFail($id);
        if ((int) $master->admin_user_id !== (int) $user->id) {
            abort(403, 'Anda hanya bisa menghapus item milik admin Anda.');
        }
        if ($this->isMasterUsedAndChecked($master)) {
            abort(403, 'Item ini sudah dipakai dan dicentang di checklist resign. Tidak dapat dihapus.');
        }

        $master->delete();

        return redirect()->route('checklist.master.index')->with('success', 'Master checklist berhasil dihapus.');
    }

    private function generateUniqueItemKey(string $department, int $adminUserId, string $itemLabel, ?int $ignoreId = null): string
    {
        $base = Str::slug($itemLabel, '_');
        if ($base === '') {
            $base = 'item';
        }
        $base = substr($base, 0, 90);
        $candidate = $base;
        $counter = 2;

        while (true) {
            $query = ChecklistMaster::where('department', $department)
                ->where('admin_user_id', $adminUserId)
                ->where('item_key', $candidate);

            if ($ignoreId !== null) {
                $query->where('id', '!=', $ignoreId);
            }

            if (!$query->exists()) {
                return $candidate;
            }

            $suffix = '_' . $counter;
            $candidate = substr($base, 0, 100 - strlen($suffix)) . $suffix;
            $counter++;
        }
    }

    private function getPicOptionsByDepartment(?string $department): array
    {
        if (!$department) {
            return [];
        }

        return User::query()
            ->orderBy('nama')
            ->get(['id', 'nama', 'username', 'role', 'divisi_posisi'])
            ->filter(function (User $candidate) use ($department) {
                if ($candidate->getDepartment() !== $department) {
                    return false;
                }

                // Khusus HC: sembunyikan akun placeholder seperti "ID Card Cadangan".
                if ($department === 'hc') {
                    $nama = strtolower((string) $candidate->nama);
                    $username = strtolower((string) $candidate->username);
                    if (str_contains($nama, 'id card') || str_contains($username, 'id card')) {
                        return false;
                    }
                }

                return true;
            })
            ->map(function (User $candidate) {
                return [
                    'id' => (int) $candidate->id,
                    'nama' => (string) $candidate->nama,
                ];
            })
            ->values()
            ->all();
    }

    private function isMasterUsedAndChecked(ChecklistMaster $master): bool
    {
        return \App\Models\ResignChecklistItem::where('department', $master->department)
            ->where('item_key', $master->item_key)
            ->where('done', 1)
            ->exists();
    }
}
