-- Tambah kolom done_by ke resign_checklist_items (jika belum ada)
-- Jalankan sekali di phpMyAdmin / MySQL client

ALTER TABLE `resign_checklist_items`
ADD COLUMN `done_by` BIGINT UNSIGNED NULL AFTER `done_at`;
