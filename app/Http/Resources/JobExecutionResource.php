<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representasi satu pelaksanaan pekerjaan untuk aplikasi mobile.
 *
 * Mode ringkas (dipakai di daftar): hanya field dasar tanpa langkah.
 * Mode detail (dipakai di show): sertakan langkah + status checklist.
 *
 * @mixin \App\Models\JobExecution
 */
class JobExecutionResource extends JsonResource
{
    private bool $withDetail;

    public function __construct($resource, bool $withDetail = false)
    {
        parent::__construct($resource);
        $this->withDetail = $withDetail;
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $progres = $this->progres();

        $base = [
            'id'                  => $this->id,
            'nama_pekerjaan'      => $this->nama_pekerjaan,
            'lokasi'              => $this->lokasi,
            'tanggal_pelaksanaan' => $this->tanggal_pelaksanaan?->toDateString(),
            'status'              => $this->status,
            'status_label'        => \App\Models\JobExecution::STATUS_LABELS[$this->status] ?? $this->status,
            'catatan'             => $this->catatan,
            'progres'             => $progres,
            'jsa' => [
                'id'         => $this->document?->id,
                'judul'      => $this->document?->title,
                'no_dokumen' => $this->document?->displayNumber(),
                'departemen' => $this->document?->department?->code,
            ],
            'pelaksana' => [
                'id'   => $this->user?->id,
                'nama' => $this->user?->name,
                'nrp'  => $this->user?->nrp,
            ],
            'dibuat_pada' => $this->created_at?->toIso8601String(),
        ];

        if (! $this->withDetail) {
            return $base;
        }

        // Gabungkan analisa_snapshot dengan status centang dari tabel checklist.
        // Diindeks oleh "langkah_ke-bahaya_ke-pengendalian_ke" untuk lookup O(1).
        $checklistMap = $this->checklistItems
            ->keyBy(fn ($item) => "{$item->langkah_ke}-{$item->bahaya_ke}-{$item->pengendalian_ke}");

        $langkah = [];
        foreach (array_values($this->analisa_snapshot ?? []) as $li => $step) {
            $bahayaList = [];
            foreach (array_values((array) ($step['bahaya'] ?? [])) as $bi => $b) {
                $pengList = array_values(array_filter(
                    array_map('strval', (array) ($b['pengendalian'] ?? [])),
                    fn ($p) => trim($p) !== ''
                ));

                $pengendalian = [];
                foreach ($pengList as $pi => $p) {
                    $key  = "{$li}-{$bi}-{$pi}";
                    $item = $checklistMap->get($key);
                    $pengendalian[] = [
                        'ke'         => $pi,
                        'teks'       => $p,
                        'checked'    => (bool) ($item?->checked ?? false),
                        'checked_at' => $item?->checked_at?->toIso8601String(),
                        'catatan'    => $item?->catatan,
                    ];
                }

                $bahayaList[] = [
                    'ke'           => $bi,
                    'risiko'       => $b['risiko'] ?? '',
                    'pengendalian' => $pengendalian,
                ];
            }

            $langkah[] = [
                'ke'       => $li,
                'teks'     => $step['langkah'] ?? '',
                'bahaya'   => $bahayaList,
            ];
        }

        return array_merge($base, ['langkah' => $langkah]);
    }
}
