<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Services\SchemaService;
use Illuminate\Database\Seeder;

/**
 * Seeds document type schemas (D1). SOP is built first as the reference
 * pattern (D4); IK, SP and JSA follow once sample documents are available.
 *
 * The schema is the single source of truth: one engine renders the form,
 * the preview, and the PDF from it — so they cannot drift apart.
 */
class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        // SOP: 6 bab; alur peninjau (SH/DH) → penyetuju (PJO), pengesahan 3 baris.
        DocumentType::updateOrCreate(
            ['code' => 'SOP'],
            [
                'name' => 'Standard Operating Procedure', 'class' => 'inti',
                'scope' => 'all_departments', 'is_active' => true,
                // SOP saja yang memakai bab FLOWCHART + lampiran berupa RUJUKAN
                // dokumen; SP belum diminta berubah.
                'schema_json' => $this->standardSchema('SOP', 'STANDARD OPERATING PROCEDURE', 'sop', flowchart: true),
            ],
        );

        // SP (Standar Parameter): layout 6 bab identik SOP; alur SATU SH/DH yang
        // meninjau SEKALIGUS menyetujui (pengesahan 2 baris) — referensi
        // docs/PPA-ADRO-SP-ICTMD-01.docx.
        // Kode tetap `SP` (dulu "Standar Produksi") — penomoran dibangun dari
        // kode ini, jadi mengubahnya akan memutus seluruh nomor yang terbit.
        DocumentType::updateOrCreate(
            ['code' => 'SP'],
            [
                'name' => 'Standar Parameter', 'class' => 'inti',
                'scope' => 'all_departments', 'is_active' => true,
                'schema_json' => $this->standardSchema('SP', 'STANDAR PARAMETER', 'dual'),
            ],
        );

        // IK (Instruksi Kerja): HANYA tabel Aktivitas & Tanggung Jawab → langsung
        // pengesahan; alur satu SH/DH (dual) — referensi docs/Instruksi-kerja.docx.
        DocumentType::updateOrCreate(
            ['code' => 'IK'],
            [
                'name' => 'Instruksi Kerja', 'class' => 'inti',
                'scope' => 'all_departments', 'is_active' => true,
                'schema_json' => $this->ikSchema(),
            ],
        );

        // JSA — Formulir SHE landscape tersendiri (docs/JSA.docx). Alur review/
        // approval mengikuti aturan SOP (jabatan-based).
        DocumentType::updateOrCreate(
            ['code' => 'JSA'],
            [
                'name' => 'Job Safety Analysis',
                'class' => 'inti',
                'scope' => 'all_departments',
                'is_active' => true,
                'schema_json' => $this->jsaSchema(),
            ],
        );

        /*
        | FK & PX — jenis UNGGAHAN (keputusan pemilik BARU-1, docs/PLAN-MASTER.md).
        |
        | Keduanya tak pernah masuk wizard: isinya berkas PDF yang diunggah, dan
        | formulirnya hanya nomor/judul/edisi/revisi/tanggal efektif — persis
        | formulir dokumen arsip butir 0. Jadi keduanya menumpang jalur yang sudah
        | ada (DocumentArsipController) dan TIDAK memerlukan schema apa pun.
        |
        | `schema_json` diisi larik KOSONG, bukan null: kolomnya NOT NULL dan
        | SchemaService::__construct bertipe array. Kosong = tak ada bab, dan itu
        | memang keadaan yang benar untuk jenis ini.
        */
        foreach (['FK' => 'Formulir Kerja', 'PX' => 'Prosedur External'] as $code => $name) {
            DocumentType::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name, 'class' => 'unggahan',
                    'scope' => 'all_departments', 'is_active' => true,
                    'schema_json' => [],
                ],
            );
        }
    }

    /**
     * Schema JSA (Formulir Job Safety Analysis) — landscape, berbeda dari SOP.
     * Struktur analisa: Langkah Kerja → Bahaya & Risiko → Tindakan Pengendalian
     * (nested 3 tingkat). Alur review/approval sama dengan SOP (jabatan-based).
     */
    private function jsaSchema(): array
    {
        return [
            'doc_type' => 'JSA',
            'doc_type_label' => 'FORMULIR JOB SAFETY ANALYSIS',
            'orientation' => 'landscape',
            'header' => '_kop_jsa',
            'footer' => '_footer',
            'print_view' => 'documents.print.render-jsa',
            'footer_text' => 'Dokumen elektronik ini merupakan dokumen tidak terkendali apabila dicetak.',
            // JSA TIDAK memakai halaman cover: blok TTD-nya sudah menyatu di
            // halaman 1 formulir (render-jsa), bukan lembar tersendiri.
            'approval_page' => '_pengesahan',
            'steps' => [
                [
                    'step' => 1,
                    'title' => 'Informasi Umum',
                    'sections' => [
                        ['key' => 'lokasi_kerja', 'label' => 'Lokasi Kerja', 'type' => 'text', 'placeholder' => 'mis. View Point'],
                        ['key' => 'apd', 'label' => 'APD yang digunakan', 'type' => 'rich_list', 'min_items' => 1, 'placeholder' => 'mis. Helm safety, Body harness, Sarung tangan...'],
                        ['key' => 'tools', 'label' => 'Peralatan yang digunakan', 'type' => 'rich_list', 'min_items' => 1, 'placeholder' => 'mis. Obeng, Bor, Tang...'],
                        // No. Dokumen & Revisi pada kop TIDAK diisi manual: keduanya
                        // otomatis (penomoran + no_revisi) spt SOP/IK/SP. Hanya Tgl
                        // Efektif yang diisi pembuat, tanpa isi default.
                        // Tanggal WAJIB widget tanggal (bukan diketik).
                        ['key' => 'form_tgl_efektif', 'label' => 'Tgl. Efektif (kop)', 'type' => 'date'],
                        [
                            // TANPA "(DH/SH)" — peninjau JSA adalah GL SHE & Plant
                            // (docs/aturan-alur-v2.md), jadi keterangan itu keliru di
                            // sini. Di SOP/IK/SP ia tetap benar dan tetap dipakai.
                            'key' => 'peninjau', 'label' => 'Ditinjau Oleh',
                            'type' => 'user_picker', 'role_filter' => ['group_leader', 'section_head'], 'required' => true,
                        ],
                        [
                            'key' => 'penyetuju', 'label' => 'Disetujui Oleh',
                            'type' => 'user_picker', 'role_filter' => ['pimpinan'], 'required' => true,
                        ],
                    ],
                ],
                [
                    'step' => 2,
                    'title' => 'Analisa Bahaya',
                    'sections' => [
                        [
                            'key' => 'analisa', 'label' => 'Analisa Bahaya', 'type' => 'jsa_analysis',
                            'help' => 'Masukkan tahapan pekerjaan, potensi bahaya yang mungkin timbul, dan langkah pengendaliannya.',
                        ],
                    ],
                ],
            ],
            'approval_page_layout' => [
                'columns' => ['Nama', 'Jabatan', 'Tanggal', 'Pengesahan'],
                'rows' => [
                    ['role_label' => 'Dibuat Oleh', 'role' => 'pembuat'],
                    ['role_label' => 'Ditinjau Oleh', 'role' => 'peninjau'],
                    ['role_label' => 'Disetujui Oleh', 'role' => 'penyetuju'],
                ],
                'stamp_on_published' => 'APPROVED',
            ],
        ];
    }

    /**
     * Beri nomor bab menurut URUTANNYA di larik.
     *
     * Tiap bab cukup menyebut `nama`-nya; "V." dan `auto_number` "5." lahir dari
     * posisinya. Dengan begitu menyisipkan bab baru (mis. FLOWCHART di tengah)
     * TIDAK menuntut penyuntingan nomor di tujuh tempat — dulu nomor Romawi dan
     * auto_number ditulis tangan dan gampang berselisih satu sama lain.
     *
     * `nama` IKUT TERSIMPAN di section, bukan dibuang sesudah dipakai: bab
     * opsional yang dimatikan membuat bab sesudahnya harus dinomori ULANG saat
     * dokumen ditampilkan ({@see SchemaService::denganPenomoran}), dan itu
     * mustahil bila yang tersisa cuma label bernomornya.
     *
     * @param  array<int, array>  $bab  tiap butir memakai kunci `nama`
     */
    private function bernomor(array $bab): array
    {
        $out = [];
        foreach (array_values($bab) as $i => $b) {
            $nomor = $i + 1;

            // Kunci turunan ditaruh di KIRI: pada gabungan larik, sisi kiri menang.
            $out[] = ['label' => SchemaService::ROMAWI[$nomor].'. '.$b['nama'], 'auto_number' => $nomor.'.'] + $b;
        }

        return $out;
    }

    /**
     * Schema "standar" (SOP/IK/SP) — layout identik, hanya label jenis berbeda.
     * Diturunkan dari docs/schema-sop.json, dikelompokkan ke DUA langkah form
     * (PRD v2 §4.1: satu sumber untuk form / preview / PDF).
     *
     * $flowchart — sisipkan bab FLOWCHART sebelum AKTIVITAS. Saat ini hanya SOP;
     * SP memakai fungsi yang sama tetapi belum memerlukannya.
     */
    private function standardSchema(string $code, string $label, string $flow = 'sop', bool $flowchart = false): array
    {
        /*
        | Bab isi, URUT. Nomor Romawi & auto_number diturunkan dari posisinya
        | (lihat bernomor()), jadi yang ditulis di sini hanya urutannya.
        */
        $bab = $this->bernomor(array_values(array_filter([
            [
                'key' => 'tujuan', 'nama' => 'TUJUAN', 'type' => 'rich_list',
                'min_items' => 1, 'placeholder' => 'Masukkan poin tujuan...',
            ],
            [
                'key' => 'ruang_lingkup', 'nama' => 'RUANG LINGKUP', 'type' => 'rich_list',
                'min_items' => 1, 'placeholder' => 'Masukkan poin ruang lingkup...',
            ],
            [
                'key' => 'referensi', 'nama' => 'REFERENSI', 'type' => 'reference_picker',
                'allow_add' => true, 'placeholder' => 'Masukkan referensi...',
                'suggestions' => [
                    'ISO 9001:2015 Sistem Manajemen Mutu',
                    'ISO 45001:2018 Sistem Manajemen K3',
                    'ISO 14001:2015 Sistem Manajemen Lingkungan',
                    'SMKP Minerba (Permen ESDM No. 26/2018)',
                    'UU No. 1 Tahun 1970 tentang Keselamatan Kerja',
                ],
            ],
            [
                'key' => 'definisi', 'nama' => 'DEFINISI', 'type' => 'rich_list',
                'placeholder' => 'Masukkan definisi...',
            ],
            // FLOWCHART berdiri SENDIRI di halamannya (permintaan pemilik):
            // `page_break both` memaksa bab ini mulai di halaman baru dan bab
            // sesudahnya mulai di halaman baru lagi. Bab I–IV dan VI–VII tetap
            // mengalir & menghabiskan halamannya masing-masing.
            $flowchart ? [
                'key' => 'flowchart', 'nama' => 'FLOWCHART', 'type' => 'repeatable_group',
                // BAB OPSIONAL: penyusun menyatakannya lewat sakelar "Gunakan
                // Flowchart", bukan dengan membiarkannya kosong. Dimatikan →
                // babnya tak tercetak DAN bab sesudahnya naik nomor (AKTIVITAS
                // jadi V, LAMPIRAN jadi VI) — nomor bolong tak pantas untuk
                // dokumen mutu. Isinya tetap tersimpan; lihat
                // SchemaService::denganPenomoran().
                'bab_opsional' => true,
                'toggle_key' => 'pakai_flowchart',
                'toggle_label' => 'Gunakan Flowchart',
                // Satu baris STANDBY seperti AKTIVITAS: penyusun langsung melihat
                // kolom yang harus diisi, tak perlu menemukan tombol "+ Tambah"
                // lebih dulu. Baris yang dibiarkan kosong tetap dibuang
                // DocumentWizard::cleanValue(), jadi bab ini tak memakan halaman
                // bila memang tak dipakai.
                'min_groups' => 1, 'page_break' => 'both',
                // TIDAK WAJIB: tak semua SOP punya flowchart. `required: false`
                // membuat validasi layar melewatinya (data-optional), sementara
                // `warn_if_empty` tetap MENGINGATKAN saat Kirim — peringatan,
                // bukan penghalang.
                'required' => false, 'warn_if_empty' => true,
                'add_button_label' => '+ Tambah Flowchart',
                'group_fields' => [
                    ['key' => 'judul', 'label' => 'Judul Flowchart', 'type' => 'text', 'placeholder' => 'Judul (Contoh: Alur Serah Terima Shift)', 'warn_if_empty' => true],
                    ['key' => 'gambar', 'label' => 'Gambar Flowchart', 'type' => 'image', 'image_accept' => 'image/jpeg,image/png', 'image_max_mb' => 5],
                    ['key' => 'keterangan', 'label' => 'Keterangan', 'type' => 'textarea', 'placeholder' => 'Keterangan flowchart...', 'warn_if_empty' => true],
                ],
            ] : null,
            [
                'key' => 'aktivitas', 'nama' => 'AKTIVITAS DAN TANGGUNG JAWAB',
                'type' => 'repeatable_group', 'min_groups' => 1,
                'add_button_label' => '+ Tambah Aktivitas/Tanggung Jawab',
                'group_fields' => [
                    ['key' => 'sub_judul', 'label' => 'Sub Judul', 'type' => 'text', 'placeholder' => 'Sub Judul (Contoh: Tahap Persiapan)'],
                    ['key' => 'deskripsi', 'label' => 'Deskripsi Aktivitas', 'type' => 'rich_text', 'placeholder' => 'Uraikan langkahnya. Bisa ditebalkan, dibuat daftar bernomor, dikutip, atau disisipi foto.'],
                    ['key' => 'pic', 'label' => 'PIC', 'type' => 'text', 'placeholder' => 'PIC (Contoh: Tim ICT)'],
                ],
            ],
            // LAMPIRAN kini MERUJUK dokumen lain, bukan menempel gambar: dipilih
            // dari dokumen Berlaku ATAU diketik manual (document_picker).
            // Komentarnya OPSIONAL — boleh dikosongkan (permintaan pemilik).
            $flowchart ? [
                'key' => 'lampiran', 'nama' => 'LAMPIRAN', 'type' => 'repeatable_group',
                // Standby 1 baris, alasan yang sama dengan FLOWCHART di atas.
                'min_groups' => 1, 'add_button_label' => '+ Tambah Lampiran',
                // TIDAK WAJIB — tak semua SOP merujuk dokumen lain.
                'required' => false, 'warn_if_empty' => true,
                'group_fields' => [
                    [
                        'key' => 'dokumen', 'label' => 'Nomor Dokumen', 'type' => 'document_picker',
                        'placeholder' => 'Pilih dokumen Berlaku, atau ketik manual...',
                        'warn_if_empty' => true,
                        // Memilih dari daftar memecah hasilnya: NOMOR tinggal di
                        // kolom ini, JUDUL turun ke kolom `keterangan` di bawah
                        // (butir 6). Ketikan bebas tak ikut dipecah — hanya di
                        // daftar itulah nomor dan judulnya memang sudah terpisah.
                        'judul_ke' => 'keterangan',
                    ],
                    [
                        // TIDAK wajib — tapi diingatkan saat Kirim bila barisnya
                        // terisi dan keterangannya kosong (`warn_if_empty`).
                        // Peringatan, bukan aturan validasi: pengirim tetap boleh
                        // meneruskan setelah menyadarinya.
                        'key' => 'keterangan', 'label' => 'Judul / Keterangan (opsional)', 'type' => 'textarea',
                        'placeholder' => 'Terisi sendiri saat dokumen dipilih dari daftar. Boleh diubah atau dikosongkan.',
                        'warn_if_empty' => true,
                    ],
                ],
            ] : [
                'key' => 'lampiran', 'nama' => 'LAMPIRAN', 'type' => 'repeatable_group',
                'min_groups' => 0, 'add_button_label' => '+ Tambah Lampiran Baru',
                // Sama seperti LAMPIRAN pada SOP: tak wajib, kekosongannya cukup
                // diingatkan. Tanpa `required: false`, menambah satu baris
                // lampiran di SP/IK langsung mewajibkan SELURUH kolomnya.
                'required' => false,
                'group_fields' => [
                    ['key' => 'judul', 'label' => 'Judul Lampiran', 'type' => 'text', 'placeholder' => 'Judul Lampiran (Contoh: Form Ceklis)'],
                    ['key' => 'keterangan', 'label' => 'Keterangan', 'type' => 'textarea', 'placeholder' => 'Keterangan / caption lampiran (opsional)...', 'warn_if_empty' => true],
                    ['key' => 'gambar', 'label' => 'Foto / Gambar (opsional)', 'type' => 'image', 'image_accept' => 'image/jpeg,image/png', 'image_max_mb' => 2],
                ],
            ],
        ])));

        // Langkah 1 = empat bab teks; langkah 2 = sisanya + pemilih peserta.
        $step1 = array_slice($bab, 0, 4);
        $step2 = array_slice($bab, 4);

        return [
            'doc_type' => $code,
            'doc_type_label' => $label,
            'header' => '_kop',
            'footer' => '_footer',
            'footer_text' => 'Dokumen elektronik ini merupakan dokumen tidak terkendali apabila dicetak.',
            // Halaman pengesahan PINDAH KE DEPAN sebagai halaman COVER
            // (docs/Cover_Depan.docx). `approval_page_layout` di bawah tetap
            // sumber tunggal siapa-menandatangani-apa; yang berubah hanya
            // rupanya. JSA tak ikut — blok TTD-nya menyatu di halaman 1.
            'cover_page' => '_cover',
            'steps' => [
                [
                    'step' => 1,
                    'title' => 'Tujuan, Ruang Lingkup, Referensi & Definisi',
                    'sections' => $step1,
                ],
                [
                    'step' => 2,
                    'title' => $flowchart ? 'Flowchart, Aktivitas, Lampiran & Verifikasi' : 'Aktivitas, Lampiran & Verifikasi',
                    // Judul saat bab opsional langkah ini DIMATIKAN. Dibaca
                    // SchemaService::denganPenomoran(); tanpa flowchart, kunci
                    // ini tak ada dan judulnya memang tak pernah berubah.
                    ...($flowchart ? ['title_tanpa_opsional' => 'Aktivitas, Lampiran & Verifikasi'] : []),
                    'sections' => [...$step2, ...$this->participantSections($flow)],
                ],
            ],
            'approval_page_layout' => $this->approvalLayout($flow),
        ];
    }

    /**
     * Schema IK (Instruksi Kerja) — HANYA tabel Aktivitas & Tanggung Jawab, lalu
     * langsung Halaman Pengesahan (tanpa Tujuan/Ruang Lingkup/Referensi/Definisi/
     * Lampiran). Alur satu SH/DH (dual). Referensi: docs/Instruksi-kerja.docx.
     */
    private function ikSchema(): array
    {
        return [
            'doc_type' => 'IK',
            'doc_type_label' => 'INSTRUKSI KERJA',
            'header' => '_kop',
            'footer' => '_footer',
            'footer_text' => 'Dokumen elektronik ini merupakan dokumen tidak terkendali apabila dicetak.',
            // Halaman pengesahan PINDAH KE DEPAN sebagai halaman COVER
            // (docs/Cover_Depan.docx). `approval_page_layout` di bawah tetap
            // sumber tunggal siapa-menandatangani-apa; yang berubah hanya
            // rupanya. JSA tak ikut — blok TTD-nya menyatu di halaman 1.
            'cover_page' => '_cover',
            'steps' => [
                [
                    'step' => 1,
                    'title' => 'Aktivitas & Pengesahan',
                    'sections' => [
                        [
                            'key' => 'aktivitas', 'label' => 'I. AKTIVITAS DAN TANGGUNG JAWAB',
                            'type' => 'repeatable_group', 'auto_number' => '1.', 'min_groups' => 1,
                            'add_button_label' => '+ Tambah Aktivitas/Tanggung Jawab',
                            'group_fields' => [
                                ['key' => 'sub_judul', 'label' => 'Sub Judul', 'type' => 'text', 'placeholder' => 'Sub Judul (Contoh: Pemeliharaan bulanan)'],
                                ['key' => 'deskripsi', 'label' => 'Deskripsi Aktivitas', 'type' => 'rich_text', 'placeholder' => 'Uraikan langkahnya. Bisa ditebalkan, dibuat daftar bernomor, dikutip, atau disisipi foto.'],
                                ['key' => 'pic', 'label' => 'PIC', 'type' => 'text', 'placeholder' => 'PIC (Contoh: ICT)'],
                            ],
                        ],
                        ...$this->participantSections('dual'),
                    ],
                ],
            ],
            'approval_page_layout' => $this->approvalLayout('dual'),
        ];
    }

    /**
     * Section user_picker langkah akhir per alur.
     * - 'sop'  → Peninjau (SH/DH) + Penyetuju (PJO) TERPISAH.
     * - 'dual' → SATU picker: SH/DH yang meninjau SEKALIGUS menyetujui (SP/IK).
     * Pembuat tambahan selalu ada (opsional).
     */
    private function participantSections(string $flow): array
    {
        $pembuatTambahan = [
            'key' => 'pembuat_tambahan', 'label' => 'Pembuat Tambahan (opsional)',
            'type' => 'user_picker', 'multiple' => true, 'required' => false,
            'hint' => 'Gunakan tombol + jika pembuat lebih dari 1 orang.',
        ];

        if ($flow === 'dual') {
            return [
                $pembuatTambahan,
                [
                    'key' => 'peninjau_penyetuju', 'label' => 'Ditinjau & Disetujui Oleh (SH/DH Dept)',
                    'type' => 'user_picker', 'role_filter' => ['section_head', 'departemen_head'], 'required' => true,
                ],
            ];
        }

        return [
            $pembuatTambahan,
            [
                'key' => 'peninjau', 'label' => 'Ditinjau Oleh (DH/SH)',
                'type' => 'user_picker', 'role_filter' => ['group_leader', 'section_head'], 'required' => true,
            ],
            [
                'key' => 'penyetuju', 'label' => 'Disetujui Oleh',
                'type' => 'user_picker', 'role_filter' => ['pimpinan'], 'required' => true,
            ],
        ];
    }

    /** Tata letak Halaman Pengesahan per alur (3 baris SOP vs 2 baris SP/IK). */
    private function approvalLayout(string $flow): array
    {
        $rows = $flow === 'dual'
            ? [
                ['role_label' => 'Dibuat Oleh', 'role' => 'pembuat'],
                ['role_label' => 'Ditinjau dan Disetujui Oleh', 'role' => 'peninjau_penyetuju'],
            ]
            : [
                ['role_label' => 'Dibuat Oleh', 'role' => 'pembuat'],
                ['role_label' => 'Ditinjau Oleh', 'role' => 'peninjau'],
                ['role_label' => 'Disetujui Oleh', 'role' => 'penyetuju'],
            ];

        return [
            'columns' => ['Nama', 'Jabatan', 'Tanggal', 'Pengesahan'],
            'rows' => $rows,
            'stamp_on_published' => 'APPROVED',
        ];
    }
}
