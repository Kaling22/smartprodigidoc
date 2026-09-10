<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentResource;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Daftar & detail dokumen BERLAKU untuk aplikasi mobile.
 *
 * Satu rute untuk SEMUA jenis dan departemen (schema-driven, CLAUDE.md §7) —
 * bukan `sopShe`/`ikCoe` seperti aplikasi lama. Menambah jenis dokumen baru
 * kelak (memo, MSDS, instruksi KTT, …) TIDAK menambah rute di sini.
 *
 * Yang ditampilkan HANYA dokumen berstatus `published`. Mobile adalah kanal
 * BACA untuk orang lapangan; draft, dokumen dalam peninjauan, dan yang sudah
 * tidak berlaku tak boleh bocor ke sana.
 */
class DocumentApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['nullable', 'string', 'max:20'],
            'department' => ['nullable', 'string', 'max:20'],
            'q' => ['nullable', 'string', 'max:100'],
            // Hanya dua nilai. Tanpa `in:`, salah ketik `?status=draf` akan
            // jatuh diam-diam ke cabang Berlaku dan layar arsip melabeli
            // dokumen yang justru masih berlaku sebagai "Tidak Berlaku" —
            // kesalahan yang membuat orang bekerja dengan acuan yang salah.
            'status' => ['nullable', 'string', 'in:berlaku,tidak_berlaku'],
        ]);

        $arsip = ($data['status'] ?? 'berlaku') === 'tidak_berlaku';

        // Arsip TERTUTUP bagi Non-Staff (User::bisaLihatArsip): dokumen obsolete
        // tak boleh pernah jadi acuan orang di lapangan.
        abort_if($arsip && ! $request->user()->bisaLihatArsip(), 403,
            'Dokumen tidak berlaku hanya dapat dibuka GL, Section/Departemen Head, dan Pimpinan.');

        // scopeBerlaku() = sumber tunggal definisi "Berlaku", dipakai bersama
        // halaman web "Dokumen Berlaku" agar mobile & web mustahil berbeda isi.
        $query = Document::query()
            ->with(['type', 'department'])
            // `direvisiOleh` dimuat HANYA di cabang arsip: daftar Berlaku tak
            // pernah menampilkan "digantikan oleh", jadi query ketiganya percuma.
            ->when($arsip, fn ($q) => $q->where('status', 'obsolete')->with('direvisiOleh'))
            ->when(! $arsip, fn ($q) => $q->berlaku());

        if ($kode = ($data['type'] ?? null)) {
            $typeId = DocumentType::where('code', strtoupper($kode))->value('id');

            // Jenis tak dikenal → daftar KOSONG, bukan seluruh dokumen.
            // Tanpa penjagaan ini, salah ketik `?type=SOPP` justru membocorkan
            // dokumen seluruh jenis.
            $query->where('document_type_id', $typeId ?? 0);
        }

        if ($kode = ($data['department'] ?? null)) {
            $deptId = Department::where('code', strtoupper($kode))->value('id');
            $query->where('department_id', $deptId ?? 0);
        }

        // Pagar departemen — SESUDAH filter dari query string, supaya
        // `?department=SHE` dari HP milik orang PLANT menghasilkan daftar
        // kosong, bukan dokumen SHE.
        $query->terlihatOleh($request->user());

        if ($cari = ($data['q'] ?? null)) {
            $query->where(function ($w) use ($cari) {
                $w->where('title', 'like', '%'.$cari.'%')
                    ->orWhere('doc_number', 'like', '%'.$cari.'%');
            });
        }

        $dokumen = $query->orderBy('doc_number')->get();

        // Dibungkus `data` karena itulah yang dibaca layar mobile
        // (`decoded['data']`).
        return response()->json([
            'data' => DocumentResource::collection($dokumen)->resolve(),
        ]);
    }

    public function show(Request $request, Document $document): JsonResponse
    {
        // Dokumen belum terbit tak pernah terbaca lewat mobile. Yang sudah
        // TIDAK berlaku terbaca, tapi hanya oleh GL/SH/DH/PJO — syarat yang
        // sama persis dengan index() dan DocumentFileController, sebab layar
        // arsip memanggil ketiganya berurutan untuk satu pratinjau PDF.
        $terbuka = in_array($document->status, ['published', 'sedang_direvisi'], true)
            || ($document->status === 'obsolete' && $request->user()->bisaLihatArsip());

        // Tanpa pagar departemen di sini, menebak `/api/documents/123` masih
        // membuka metadata departemen lain meski daftarnya sudah bersih.
        abort_unless($terbuka && $document->terlihatOleh($request->user()), 404);

        $document->load(['type', 'department', 'contents', 'direvisiOleh']);
        $base = (new DocumentResource($document))->resolve();

        // Untuk JSA, sertakan analisa langkah agar mobile bisa menyusun
        // form checklist tanpa harus parse PDF.
        if ($document->type?->code === 'JSA') {
            $contentMap  = $document->contentMap();
            $analisa     = is_array($contentMap['analisa'] ?? null) ? $contentMap['analisa'] : [];
            $listText    = function ($v) {
                if (is_array($v)) {
                    return implode(', ', array_filter(array_map('trim', $v), fn ($x) => $x !== ''));
                }

                return is_string($v) ? $v : '';
            };

            $langkah = [];
            foreach (array_values($analisa) as $li => $step) {
                $bahayaList = [];
                foreach (array_values((array) ($step['bahaya'] ?? [])) as $bi => $b) {
                    $pengList = array_values(array_filter(
                        array_map('strval', (array) ($b['pengendalian'] ?? [])),
                        fn ($p) => trim($p) !== ''
                    ));
                    $bahayaList[] = [
                        'ke'           => $bi,
                        'risiko'       => $b['risiko'] ?? '',
                        'pengendalian' => array_map(
                            fn ($pi, $p) => ['ke' => $pi, 'teks' => $p],
                            array_keys($pengList),
                            $pengList
                        ),
                    ];
                }
                $langkah[] = ['ke' => $li, 'teks' => $step['langkah'] ?? '', 'bahaya' => $bahayaList];
            }

            $base['jsa_info'] = [
                'lokasi_kerja' => $contentMap['lokasi_kerja'] ?? null,
                'apd'          => $listText($contentMap['apd'] ?? null),
                'tools'        => $listText($contentMap['tools'] ?? null),
            ];
            $base['langkah'] = $langkah;
        }

        return response()->json(['data' => $base]);
    }
}
