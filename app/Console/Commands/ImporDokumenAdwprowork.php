<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Sekali-pakai: kosongkan dokumen+turunan lokal, impor ulang dari staging
 * adwprowork_staging (hasil load ADWPROWORK.sql). Lihat docs/preparationproduction.md
 * Fase 1-2. Jalankan dengan --dry-run dulu untuk lihat angka tanpa menulis apa pun.
 */
class ImporDokumenAdwprowork extends Command
{
    protected $signature = 'smartpro:impor-adwprowork {--dry-run}';
    protected $description = 'Wipe dokumen lokal, impor ulang dari DB staging adwprowork_staging (remap id via NRP)';

    private const STAGING = 'impor_staging';

    public function handle(): int
    {
        config(["database.connections.".self::STAGING => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'adwprowork_staging',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]]);

        $dry = (bool) $this->option('dry-run');
        $staging = DB::connection(self::STAGING);

        // 1. NRP -> local user id
        $userMap = DB::table('users')->pluck('id', 'nrp');
        $stagingUsers = $staging->table('users')->pluck('nrp', 'id'); // staging_id => nrp
        $stagingUserToLocal = [];
        foreach ($stagingUsers as $stagingId => $nrp) {
            if (! isset($userMap[$nrp])) {
                throw new RuntimeException("NRP {$nrp} (staging user id {$stagingId}) tak ada di users lokal.");
            }
            $stagingUserToLocal[$stagingId] = $userMap[$nrp];
        }

        $remapUser = fn (?int $id) => $id === null ? null : ($stagingUserToLocal[$id]
            ?? throw new RuntimeException("staging user id {$id} tak termapping."));

        $counts = [];

        DB::transaction(function () use ($staging, $remapUser, $dry, &$counts) {
            // 2. Hapus anak dulu, baru dokumen (urutan FK)
            $childTables = [
                'attachments', 'document_reads', 'document_authors',
                'document_versions', 'approvals', 'reviews', 'document_contents',
            ];
            foreach ($childTables as $t) {
                $counts["hapus:$t"] = DB::table($t)->count();
                if (! $dry) {
                    DB::table($t)->delete();
                }
            }
            $counts['hapus:audit_logs(document_id not null)'] = DB::table('audit_logs')->whereNotNull('document_id')->count();
            if (! $dry) {
                DB::table('audit_logs')->whereNotNull('document_id')->delete();
            }
            $counts['hapus:documents'] = DB::table('documents')->count();
            if (! $dry) {
                DB::table('documents')->delete();
            }

            // 3. Insert documents dulu (tanpa revises_document_id), rekam peta id lama->baru
            $docIdMap = [];
            $stagingDocs = $staging->table('documents')->get();
            $counts['insert:documents'] = $stagingDocs->count();
            foreach ($stagingDocs as $doc) {
                $row = (array) $doc;
                $oldId = $row['id'];
                unset($row['id']);
                $row['revises_document_id'] = null; // isi belakangan, pass 2
                $row['created_by'] = $remapUser($row['created_by']);
                $row['reviewer_id'] = $remapUser($row['reviewer_id']);
                $row['approver_id'] = $remapUser($row['approver_id']);
                $row['nonaktif_oleh'] = $remapUser($row['nonaktif_oleh'] ?? null);
                $row['salin_arsip_at'] = null; // kolom lokal, tak ada di dump
                $row['tanggal_revisi'] = null;
                if (! $dry) {
                    $docIdMap[$oldId] = DB::table('documents')->insertGetId($row);
                } else {
                    $docIdMap[$oldId] = $oldId; // dummy utk hitung pass 2 saat dry-run
                }
            }

            // Pass 2: isi revises_document_id
            if (! $dry) {
                foreach ($stagingDocs as $doc) {
                    if ($doc->revises_document_id !== null) {
                        DB::table('documents')
                            ->where('id', $docIdMap[$doc->id])
                            ->update(['revises_document_id' => $docIdMap[$doc->revises_document_id] ?? null]);
                    }
                }
            }

            // 4. Insert tabel anak, document_id + user FK di-remap lewat $docIdMap/$remapUser
            $remapDoc = fn (int $id) => $docIdMap[$id] ?? throw new RuntimeException("staging document id {$id} tak termapping.");

            $simpleChildren = [
                'document_contents' => [],
                'document_versions' => ['created_by' => $remapUser],
                'document_authors' => ['user_id' => $remapUser],
                'reviews' => ['reviewer_id' => $remapUser],
                'approvals' => ['approver_id' => $remapUser],
                'attachments' => [],
                'document_reads' => ['user_id' => $remapUser],
            ];
            foreach ($simpleChildren as $table => $userCols) {
                $rows = $staging->table($table)->get();
                $counts["insert:$table"] = $rows->count();
                if ($dry) {
                    continue;
                }
                foreach ($rows as $r) {
                    $row = (array) $r;
                    unset($row['id']);
                    $row['document_id'] = $remapDoc($row['document_id']);
                    foreach ($userCols as $col => $fn) {
                        $row[$col] = $fn($row[$col]);
                    }
                    DB::table($table)->insert($row);
                }
            }

            // 5. audit_logs (document_id not null)
            // Disaring ke dokumen yang benar-benar ada di dump: `audit_logs` tak
            // punya foreign key, jadi produksi menyisakan log milik dokumen yang
            // sudah dihapus (155 baris). Log yatim begitu tak bisa di-remap dan
            // tak berguna — dokumennya tak akan pernah ada untuk dibuka.
            $logs = $staging->table('audit_logs')
                ->whereIn('document_id', array_keys($docIdMap))
                ->get();
            $counts['insert:audit_logs'] = $logs->count();
            if (! $dry) {
                foreach ($logs as $log) {
                    $row = (array) $log;
                    unset($row['id']);
                    $row['document_id'] = $remapDoc($row['document_id']);
                    $row['user_id'] = $remapUser($row['user_id']);
                    DB::table('audit_logs')->insert($row);
                }
            }

            if ($dry) {
                DB::rollBack();
            }
        });

        foreach ($counts as $label => $n) {
            $this->line("$label: $n");
        }
        $this->info($dry ? 'DRY RUN selesai, tidak ada yang ditulis (transaksi di-rollback).' : 'Impor selesai.');

        return self::SUCCESS;
    }
}
