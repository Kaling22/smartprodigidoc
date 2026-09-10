<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OffDayController;
use App\Models\UserOffDay;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * "Ketersediaan Saya" dari HP — cuti, off day, dinas luar.
 *
 * Cerminan {@see OffDayController}. Aturannya TIDAK ditulis ulang: validasi
 * datang dari `UserOffDay::aturan()`, dan pemberitahuan ke pembuat dokumen
 * dari `OffDayController::beriTahuPembuat()`. Yang berbeda hanya bentuk
 * balasannya — JSON, bukan redirect.
 *
 * LANGSUNG BERLAKU tanpa persetujuan: tak ada status "menunggu disetujui" di
 * mana pun, dan jangan pernah ditambahkan. Cuti resmi tetap urusan HCGA.
 */
class OffDayApiController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * Off milik SENDIRI. Bawaannya yang belum berakhir; `?bulan=2026-08`
     * menambahkan yang bersinggungan dengan bulan itu supaya kalender di HP
     * bisa menggambar bulan mana pun tanpa menarik seluruh riwayat.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $user->offDays()->orderBy('mulai');

        if ($request->filled('bulan') && preg_match('/^\d{4}-\d{2}$/', (string) $request->bulan)) {
            $awal = \Carbon\Carbon::parse($request->bulan.'-01')->startOfMonth();
            $query->where(fn ($q) => $q->bertindih($awal->toDateString(), $awal->copy()->endOfMonth()->toDateString())
                ->orWhere(fn ($w) => $w->whereDate('sampai', '>=', now()->toDateString())));
        } else {
            $query->belumBerakhir();
        }

        $hari = now()->toDateString();

        return response()->json([
            'data' => $query->get()->map(fn (UserOffDay $o) => $this->baris($o))->all(),
            'meta' => [
                // Kartu status di puncak layar: sedang off atau tersedia.
                'sedang_off' => $user->offDays()->aktifPada($hari)->get()
                    ->map(fn (UserOffDay $o) => $this->baris($o))->first(),
                'jenis' => collect(UserOffDay::JENIS_LABELS)
                    ->map(fn ($label, $kunci) => ['kunci' => $kunci, 'label' => $label])->values()->all(),
                'maks_tanggal' => now()->addDays(60)->toDateString(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        // Non-Staff TIDAK mencatat ketersediaan (PLAN-MOBILE-v6 §2.1).
        // Menyembunyikan menunya di Beranda tanpa menutup pintunya bukan
        // pembatasan, hanya penyamaran.
        abort_unless($user->bisaCatatKetersediaan(), 403,
            'Ketersediaan dicatat GL, Section/Departemen Head, dan Pimpinan. Cuti Non-Staff tetap diurus HCGA.');

        $data = $request->validate(...UserOffDay::aturan());

        // Bertindih → sebut YANG MANA. Kalimatnya sama persis dengan web supaya
        // orang yang sama tak membaca dua penjelasan berbeda di dua layar.
        $bentrok = $user->offDays()->bertindih($data['mulai'], $data['sampai'])->first();
        if ($bentrok) {
            throw ValidationException::withMessages([
                'mulai' => "Anda sudah punya {$bentrok->jenisLabel()} {$bentrok->rentangLabel()}. Batalkan dulu yang itu bila mau menggantinya.",
            ]);
        }

        $off = $user->offDays()->create($data);

        $this->audit->log('off.create', null, [
            'jenis' => $off->jenis,
            'mulai' => $off->mulai->toDateString(),
            'sampai' => $off->sampai->toDateString(),
        ]);

        OffDayController::beriTahuPembuat($user, $off);

        return response()->json([
            'data' => $this->baris($off),
            'pesan' => "{$off->jenisLabel()} {$off->rentangLabel()} tercatat. Anda tidak muncul sebagai peninjau yang bisa dipilih selama rentang itu.",
        ], 201);
    }

    public function destroy(Request $request, UserOffDay $off): JsonResponse
    {
        // Hanya milik sendiri — off orang lain bukan urusan siapa pun, termasuk
        // atasannya, sebab ini bukan alur persetujuan.
        abort_unless($off->user_id === $request->user()->id, 403);

        $label = $off->jenisLabel().' '.$off->rentangLabel();
        $off->delete();

        $this->audit->log('off.cancel', null, ['rentang' => $label]);

        return response()->json([
            'pesan' => "{$label} dibatalkan. Anda kembali muncul sebagai peninjau yang bisa dipilih.",
        ]);
    }

    /** @return array<string, mixed> */
    private function baris(UserOffDay $o): array
    {
        $hari = now()->startOfDay();

        return [
            'id' => $o->id,
            'jenis' => $o->jenis,
            'jenis_label' => $o->jenisLabel(),
            'mulai' => $o->mulai->toDateString(),
            'sampai' => $o->sampai->toDateString(),
            // Label rentang dari server, bukan dirakit ulang di HP: bentuknya
            // ("28 Maret–2 April 2025") punya tiga kasus, dan menyalinnya ke
            // Dart berarti dua penulis kalimat yang sama.
            'rentang_label' => $o->rentangLabel(),
            'catatan' => $o->catatan,
            // Inklusif di kedua ujung — off sehari = 1 hari, bukan 0.
            'jumlah_hari' => $o->mulai->diffInDays($o->sampai) + 1,
            'berjalan' => $o->mulai->lte($hari) && $o->sampai->gte($hari),
            'sisa_hari' => $o->sampai->gte($hari) ? $hari->diffInDays($o->sampai) + 1 : 0,
        ];
    }
}
