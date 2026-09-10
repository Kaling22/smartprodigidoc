@extends('layouts.app')
@section('title', 'Pengisian Dokumen')

@php
    $partials = [
        'rich_list' => 'documents.fields._rich_list',
        'reference_picker' => 'documents.fields._rich_list',
        'repeatable_group' => 'documents.fields._repeatable_group',
        'jsa_analysis' => 'documents.fields._jsa_analysis',
        'user_picker' => 'documents.fields._user_picker',
        'text' => 'documents.fields._text',
        'textarea' => 'documents.fields._text',
        'date' => 'documents.fields._text',   // widget tanggal (bukan diketik)
    ];
    // Draft revisi Tipe B punya langkah ekstra "Log Revisi" di akhir (form lembar
    // CATATAN REVISI); Simpan/Kirim pindah ke sana. JSA dikecualikan —
    // lihat Document::usesRevisionLog().
    $totalSteps = $totalSteps ?? $schema->stepCount();
    $isRevLogStep = $document->usesRevisionLog() && $currentStep > $schema->stepCount();
    $isLast = $currentStep >= $totalSteps;
@endphp

@section('content')
    {{-- Kop / header info --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <div>
                    <div class="font-monospace text-primary fw-bold">
                        {{ $document->displayNumber() }}
                        @unless ($document->hasFinalNumber())
                            <span class="badge-soft badge-soft-warning" title="Nomor final dikunci setelah disetujui">sementara</span>
                        @endunless
                    </div>
                    <h1 class="h5 fw-bold mb-1">{{ $document->title }}</h1>
                    <span class="badge-soft">{{ $document->type->code }}</span>
                    <span class="badge-soft">{{ $document->department->code }}</span>
                    <span class="badge-soft badge-soft-secondary">{{ $document->statusLabel() }}</span>
                </div>
                <div class="text-end small text-muted">
                    <div>Pembuat: {{ $document->creator->name ?? '—' }}</div>
                    <div>No. Revisi: {{ $document->no_revisi }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Progress langkah (+ langkah "Log Revisi" khusus draft revisi) --}}
    @php
        $stepTitles = collect($schema->steps())->mapWithKeys(fn ($s) => [$s['step'] => $s['title'] ?? '']);
        if ($document->usesRevisionLog()) $stepTitles->put($schema->stepCount() + 1, 'Log Revisi (Catatan Perubahan)');
    @endphp
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap gap-3">
            @foreach ($stepTitles as $n => $title)
                @php $isCurrent = $n == $currentStep; @endphp
                <div class="d-flex align-items-center">
                    <span class="badge rounded-pill {{ $isCurrent ? 'bg-primary' : ($n < $currentStep ? 'bg-success' : 'bg-light text-muted border') }}"
                          style="width:1.9rem;height:1.9rem;line-height:1.4rem">{{ $n }}</span>
                    <span class="ms-2 small {{ $isCurrent ? 'fw-bold text-primary' : 'text-muted' }}">Langkah {{ $n }}: {{ $title }}</span>
                    @if (! $loop->last)<i class="bi bi-chevron-right text-muted mx-2"></i>@endif
                </div>
            @endforeach
        </div>
    </div>

    @unless ($editable)
        <div class="alert alert-warning"><i class="bi bi-lock"></i> Dokumen berstatus <strong>{{ $document->statusLabel() }}</strong> — mode baca.</div>
    @endunless

    @php $annotations = $annotations ?? collect(); $reviewSummary = $reviewSummary ?? null; @endphp
    @if ($annotations->isNotEmpty() || $reviewSummary)
        <div class="alert alert-danger">
            @if ($reviewSummary)
                {{-- `pre-line`: alasan Ajukan Revisi bisa berisi beberapa baris
                     (masukan lapangan yang diadopsi, satu per baris — §5). --}}
                <div class="mb-2 pb-2 border-bottom border-danger-subtle" style="white-space:pre-line"><i class="bi bi-card-text"></i> <strong>Rangkuman Peninjau:</strong> {{ $reviewSummary }}</div>
            @endif
            @if ($annotations->isNotEmpty())
            <div class="fw-semibold mb-2"><i class="bi bi-chat-left-text"></i> Catatan Peninjau — perbaiki item berikut:</div>
            <ul class="mb-0 small">
                @foreach ($annotations as $sectionKey => $list)
                    @foreach ($list as $a)<li><span class="badge-soft badge-soft-danger">{{ $sectionKey }}</span> {{ $a->comment }}</li>@endforeach
                @endforeach
            </ul>
            @endif
        </div>
    @endif

    {{-- Form (kiri) + Preview panel (kanan) — PRD v3.1 §6 --}}
    <div class="row g-3">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('documents.saveStep', $document) }}" enctype="multipart/form-data"
                  x-data="wizard('{{ route('documents.autosave', $document) }}', '{{ route('documents.pdf', $document) }}', '{{ csrf_token() }}', '{{ $previewV }}')" @submit="onSubmit($event)">
                @csrf
                <input type="hidden" name="step" value="{{ $currentStep }}">

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        @php
                            $judulLangkah = $isRevLogStep ? 'Log Revisi (Catatan Perubahan)' : $schema->stepTitle($currentStep);
                            // Judul ikut menyebut bab yang benar-benar dipakai:
                            // "Flowchart, Aktivitas, …" → "Aktivitas, …".
                            $judulMati = $isRevLogStep ? $judulLangkah : ($judulLangkahMati ?? $judulLangkah);
                        @endphp
                        <span class="fw-bold">Langkah {{ $currentStep }} —
                            <span @if ($judulMati !== $judulLangkah) x-text="$store.bab.on ? @js($judulLangkah) : @js($judulMati)" @endif>{{ ($babAktif ?? true) ? $judulLangkah : $judulMati }}</span>
                        </span>
                        <span class="small text-muted" x-show="savedAt" x-cloak><i class="bi bi-cloud-check"></i> Tersimpan otomatis <span x-text="savedAt"></span></span>
                    </div>
                    <div class="card-body" @input.debounce.1200ms="autosave()">
                        @if ($isRevLogStep)
                            @include('documents.fields._revision_log', ['document' => $document, 'value' => $contentMap['catatan_revisi'] ?? []])
                        @else
                            @foreach ($schema->sectionsForStep($currentStep) as $section)
                                @php
                                    $partial = $partials[$section['type']] ?? 'documents.fields._text';
                                    $val = in_array($section['type'], ['user_picker']) ? ($userValues[$section['key']] ?? null) : ($contentMap[$section['key']] ?? null);
                                @endphp
                                @include($partial, ['section' => $section, 'value' => $val, 'document' => $document, 'candidates' => $candidates ?? [], 'userValues' => $userValues ?? []])
                            @endforeach
                        @endif
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between">
                        <div>
                            @if ($currentStep > 1)
                                @if ($editable)
                                    <button type="submit" name="action" value="back" class="btn btn-light"><i class="bi bi-arrow-left"></i> Kembali</button>
                                @else
                                    <a href="{{ route('documents.edit', ['document' => $document, 'view_step' => $currentStep - 1]) }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Kembali</a>
                                @endif
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            {{-- Preview = AJAX: hanya panel/iframe yang dimuat ulang, halaman
                                 form TIDAK ikut ter-refresh. Foto lampiran sudah tersimpan
                                 saat dipilih (upload langsung), jadi ikut tampil di preview. --}}
                            <button type="button" class="btn btn-outline-secondary" @click="preview()" :disabled="previewing">
                                <span x-show="previewing" class="spinner-border spinner-border-sm" x-cloak></span>
                                <i class="bi bi-eye" x-show="!previewing"></i> Preview
                            </button>
                            {{-- Tombol "Revisi" — HANYA di langkah Log Revisi (butir 7b):
                                 mengisi No. Rev, Tanggal, dan Hal. tiap bab yang berubah.
                                 Duduk di sebelah Preview karena keduanya berbicara tentang
                                 CETAKAN, bukan tentang isian; tapi yang mengisi barisnya
                                 tetap komponen `revisionLog` di dalam kartu — footer ini
                                 di luar cakupannya, jadi ia dipanggil lewat event. --}}
                            @if ($isRevLogStep && $editable)
                                <button type="button" class="btn btn-outline-primary" @click="$dispatch('isi-log-revisi')">
                                    <i class="bi bi-arrow-repeat"></i> Revisi
                                </button>
                            @endif
                            @if ($editable)
                                @unless ($isLast)
                                    <button type="submit" name="action" value="next" class="btn btn-pp">Langkah Berikutnya <i class="bi bi-arrow-right"></i></button>
                                @else
                                    <button type="submit" name="action" value="save" class="btn btn-outline-primary"><i class="bi bi-save"></i> Simpan</button>
                                    <button type="submit" name="action" value="submit" class="btn btn-success" @click.prevent="confirmKirim()"><i class="bi bi-send"></i> Kirim</button>
                                @endunless
                            @elseif (! $isLast)
                                <a href="{{ route('documents.edit', ['document' => $document, 'view_step' => $currentStep + 1]) }}" class="btn btn-pp">Langkah Berikutnya <i class="bi bi-arrow-right"></i></a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Preview panel kanan --}}
        @php
            // Dokumen LAMA yang sedang disalin ke web (Fase H): draft revisi yang
            // dokumen sumbernya berupa berkas unggahan. Berkas itulah rujukan
            // yang diketik ulang, jadi ia berdiri berdampingan dengan pratinjau.
            $rujukan = $document->revisesDocument?->isArsip() ? $document->revisesDocument : null;
        @endphp
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm sticky-top" style="top:1rem" x-data="{ tab: 'pratinjau' }">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                    @if ($rujukan)
                        {{-- Dua tab, bukan dua panel bersanding: layar 1366px tak
                             cukup untuk formulir + dua A4, dan yang dibutuhkan
                             memang bergantian — baca rujukan, lalu ketik. --}}
                        <ul class="nav nav-pills nav-sm gap-1">
                            <li class="nav-item"><button type="button" class="nav-link py-1 px-2 small" :class="tab === 'pratinjau' ? 'active' : 'text-muted'" x-on:click="tab = 'pratinjau'"><i class="bi bi-eye"></i> Pratinjau</button></li>
                            <li class="nav-item"><button type="button" class="nav-link py-1 px-2 small" :class="tab === 'referensi' ? 'active' : 'text-muted'" x-on:click="tab = 'referensi'"><i class="bi bi-file-earmark-pdf"></i> Referensi</button></li>
                        </ul>
                    @else
                        <span class="fw-bold small"><i class="bi bi-eye"></i> Pratinjau PDF</span>
                    @endif
                    <a href="{{ route('documents.pdf', $document) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                </div>
                {{-- Yang ditampilkan adalah PDF SUNGGUHAN, bukan tiruan HTML-nya.
                     Dengan begitu cover, kop, batas antar halaman, dan nomor
                     "Halaman X dari Y" tampil apa adanya — dan pratinjau MUSTAHIL
                     berbeda dari berkas yang nanti diunduh, karena ia berkas itu
                     sendiri. Tinggi 78vh: satu halaman A4 utuh muat di panel
                     selebar ini, menyisakan ruang kosong di sisinya.

                     `src` DIPASANG DI HTML, bukan lewat JS sesudah Alpine hidup:
                     peramban mulai mengambilnya berbarengan dgn halaman, dan URL
                     ber-`?v=<sidik>` yang sama antar langkah dilayani dari cache
                     peramban tanpa permintaan jaringan. Itulah yang menghentikan
                     panel "mati-nyala" tiap pindah langkah — dulu iframe baru
                     diisi sesudah JS jalan, lalu dirender ulang dari nol.

                     Keterangan kosong DITINDIH iframe (bukan disembunyikan lewat
                     kelas): iframe yang belum terisi itu tembus pandang, jadi
                     keterangannya terlihat selama memuat lalu tertutup sendiri
                     saat penampil PDF mengecat — nol pergantian kelas, nol kedip. --}}
                <div class="card-body p-0 position-relative" style="background:#525659;height:78vh">
                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white-50 small">
                        <i class="bi bi-file-earmark-text fs-1 mb-2"></i>
                        Menyiapkan pratinjau…
                    </div>
                    <iframe id="previewFrame" title="Pratinjau PDF"
                            @if ($rujukan) x-show="tab === 'pratinjau'" @endif
                            src="{{ route('documents.pdf', [$document, 'v' => $previewV]) }}#toolbar=0&navpanes=0&view=Fit"
                            class="position-relative" style="width:100%;height:100%;border:0"></iframe>
                    @if ($rujukan)
                        {{-- `src` baru dipasang saat tabnya dibuka: dokumen lama bisa
                             puluhan MB, dan memuatnya untuk tab yang mungkin tak
                             pernah dilihat membuat setiap langkah wizard terasa
                             berat. Sesudah dibuka sekali ia tetap termuat. --}}
                        <iframe title="Berkas dokumen lama" x-show="tab === 'referensi'" x-cloak
                                data-src="{{ route('documents.pdf', $rujukan) }}#toolbar=1&navpanes=0&view=Fit"
                                x-init="$watch('tab', v => { if (v === 'referensi' && ! $el.src) $el.src = $el.dataset.src })"
                                class="position-relative" style="width:100%;height:100%;border:0"></iframe>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- Quill 2 (CDN, sejalan dgn Bootstrap Icons/Alpine/SweetAlert yang sudah dari
     CDN). Sengaja DI SINI, bukan di layouts/app.blade.php: hanya halaman inilah
     yang merender _repeatable_group, dan memuat 50KB editor di halaman masuk &
     dashboard tak ada gunanya. Rupanya disetel ulang ke tema SmartPro di
     layouts/app.blade.php — seluruh aturan di sana diawali `.pp-rt` sehingga
     menang atas berkas CDN ini tanpa !important, apa pun urutan muatnya. --}}
@push('styles')<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet"><style>
    [x-cloak]{display:none!important}

    /* ── Pemilih dokumen lampiran ──────────────────────────────────────────
       Palet & radius mengikuti Soft UI yang sudah terkunci (CLAUDE.md §13):
       sorotan memakai tint oranye yang sama dengan hover sub-menu sidebar.
       Warna diambil dari variabel Bootstrap supaya tema gelap ikut benar
       tanpa aturan @media terpisah. */
    /* ── Bab opsional yang sedang DIMATIKAN ────────────────────────────────
       Isiannya tetap terlihat (dan tetap ikut terkirim), tapi jelas-jelas mati:
       redup, tanpa warna, dan kursornya pun bukan kursor kolom isian.
       `inert` di HTML yang benar-benar mengunci — CSS di sini hanya
       memperlihatkannya. Keduanya perlu: yang satu untuk jari, yang satu untuk
       mata. */
    .pp-bab { transition: opacity .15s ease, filter .15s ease; }
    .pp-bab-mati { opacity: .5; filter: grayscale(1); cursor: not-allowed; }

    .pp-docpick { position: relative; }

    .pp-docpick-menu {
        position: absolute; z-index: 1056; left: 0; right: 0; top: calc(100% + .3rem);
        max-height: 16rem; overflow-y: auto;
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .6rem; padding: .3rem;
        box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .13);
    }
    .pp-docpick-item {
        display: flex; align-items: flex-start; gap: .55rem; width: 100%;
        padding: .45rem .55rem; border: 0; border-radius: .45rem;
        background: transparent; color: inherit; text-align: left; line-height: 1.25;
        transition: background-color .12s ease;
    }
    .pp-docpick-item.sorot { background: rgba(234, 88, 12, .12); }
    /* Chip jenis di pemilih dokumen. Gradasi oranye berteks putihnya dibuang
       (spec v3 R1) — rupanya kini .badge-soft seperti chip jenis di semua tabel;
       yang tersisa di sini cuma penyesuaian ukuran untuk baris yang sempit. */
    .pp-docpick-jenis {
        flex: 0 0 auto; margin-top: .1rem; font-size: .66rem; letter-spacing: .3px;
        padding: .25rem .5rem;
    }
    .pp-docpick-teks { min-width: 0; }
    .pp-docpick-teks > span { font-size: .78rem; overflow-wrap: anywhere; }
    .pp-docpick-kosong { padding: .5rem .6rem; font-size: .78rem; color: var(--bs-secondary-color); }
</style>@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
    /*
     | Ikon toolbar ditukar ke BOOTSTRAP ICONS (CLAUDE.md §13: ikon Bootstrap,
     | nol emoji). Quill menyimpan ikonnya sebagai potongan HTML di satu peta,
     | jadi menukarnya cukup menimpa peta itu — SEKALI, sebelum instans mana pun
     | dibuat. Tanpa ini toolbar memakai SVG bawaan Quill yang bergaya lain
     | sendiri di tengah aplikasi.
     */
    if (window.Quill) {
        const ikon = Quill.import('ui/icons');
        ikon.bold = '<i class="bi bi-type-bold"></i>';
        ikon.italic = '<i class="bi bi-type-italic"></i>';
        ikon.underline = '<i class="bi bi-type-underline"></i>';
        ikon.blockquote = '<i class="bi bi-quote"></i>';
        ikon.image = '<i class="bi bi-image"></i>';
        ikon.clean = '<i class="bi bi-eraser"></i>';
        ikon.list.ordered = '<i class="bi bi-list-ol"></i>';
        ikon.list.bullet = '<i class="bi bi-list-ul"></i>';
    }

    function emptyRow(fields) { const o = {}; (fields || []).forEach(f => o[f.key] = ''); return o; }
    document.addEventListener('alpine:init', () => {
        Alpine.data('richList', (initial = [], prefix = '') => ({
            items: initial.length ? initial : [''], prefix,
            add() { this.items.push(''); },
            remove(i) { this.items.splice(i, 1); if (!this.items.length) this.items.push(''); },
            label(i) { return this.prefix + (i + 1); },
        }));
        /*
         | Keadaan bab OPSIONAL (hari ini: FLOWCHART pada SOP) — satu sakelar
         | untuk satu halaman. Nilainya datang dari PHP; yang dilakukan JS cuma
         | memilih salah satu dari dua label yang SUDAH dihitung PHP.
         |
         | ponytail: satu boolean, bukan peta per-kunci. Begitu ada DUA bab
         | opsional dalam satu jenis, store ini harus jadi { [toggle_key]: bool }
         | dan bab yang bergeser perlu tahu sakelar mana yang memengaruhinya.
         */
        Alpine.store('bab', { on: @js($babAktif ?? true) });

        Alpine.data('repeatable', (initial = [], fields = [], min = 1, prefix = '', prefixMati = '') => ({
            rows: initial.length ? initial : (min > 0 ? [emptyRow(fields)] : []), fields, prefix, prefixMati,
            /*
             | Kunci STABIL per baris untuk <template x-for>.
             |
             | Kuncinya dulu INDEKS (`:key="i"`). Menghapus baris ke-1 membuat
             | Alpine MEMAKAI ULANG DOM baris berikutnya; untuk <input> biasa itu
             | tak apa-apa karena x-model mengikat ulang, tapi Quill memegang
             | DOM-nya sendiri — instansnya akan tetap menampilkan tulisan baris
             | yang salah, dan penyusun kehilangan tulisannya TANPA peringatan.
             |
             | uids hidup di komponen saja dan tak pernah masuk ke `rows`, jadi
             | tak ada nilai asing yang ikut tersimpan ke value_json.
             */
            uids: [], _uid: 0,
            init() { this.uids = this.rows.map(() => ++this._uid); },
            add() { this.rows.push(emptyRow(this.fields)); this.uids.push(++this._uid); },
            remove(i) { this.rows.splice(i, 1); this.uids.splice(i, 1); },
            // Lencana baris: "6.1" saat bab opsional menyala, "5.1" saat mati.
            // Keduanya string jadi dari PHP — di sini cuma dipilih.
            label(i) { return (Alpine.store('bab').on ? this.prefix : (this.prefixMati || this.prefix)) + (i + 1); },
        }));
        /*
         | Kolom RICH TEXT (Deskripsi Aktivitas SOP/SP/IK) — pembungkus Quill.
         |
         | Quill TIDAK memegang nilainya. Sumber kebenarannya tetap `row[fkey]`
         | milik komponen `repeatable` di atas, dan komponen ini hanya menyalin
         | ke sana tiap kali isinya berubah. Karena itu kolom ini terkirim,
         | ter-autosave, dan tervalidasi persis seperti <textarea> yang
         | digantikannya — tak ada jalur penyimpanan kedua yang perlu diurus.
         */
        Alpine.data('richText', (fkey, section, uploadUrl, token, placeholder = '') => ({
            fkey, quill: null, mengunggah: false, menyapu: false, galat: '',

            pasang() {
                this.quill = new Quill(this.$refs.editor, {
                    theme: 'snow',
                    placeholder,
                    modules: {
                        toolbar: {
                            // Sependek ini dengan sengaja: tiap tombol WAJIB punya
                            // padanan di jalur cetak DomPDF (PembersihHtml +
                            // ActivityPrintLayout). Warna, ukuran huruf, dan tabel
                            // tak dipasang karena tak pernah sampai ke PDF.
                            container: [
                                ['bold', 'italic', 'underline'],
                                ['blockquote'],
                                [{ list: 'ordered' }, { list: 'bullet' }],
                                ['image'],
                                ['clean'],
                            ],
                            handlers: { image: () => this.$refs.berkas.click() },
                        },
                    },
                });

                this.muat(this.row[this.fkey] || '');
                this.quill.on('text-change', () => this.sinkron());
            },

            /*
             | Isi awal.
             |
             | Dokumen LAMA tersimpan sebagai teks polos bernewline. Kalau ia
             | ditempel sebagai HTML, newline-nya lenyap dan seluruh langkah
             | menyatu jadi satu paragraf — tata letak SOP berjalan berubah hanya
             | karena dokumennya dibuka. setText() mempertahankan barisnya.
             */
            muat(nilai) {
                if (! nilai) return;
                if (/<\/?(p|br|strong|em|u|b|i|ol|ul|li|img|blockquote)\b/i.test(nilai)) {
                    this.quill.clipboard.dangerouslyPasteHTML(nilai, 'silent');
                } else {
                    this.quill.setText(nilai, 'silent');
                }
            },

            /*
             | Salin isi editor ke row + input hidden, lalu KIRIM `input` sendiri.
             |
             | Peristiwanya dikirim manual karena Quill menyisipkan gambar dan
             | menerapkan format lewat API: perubahan DOM programatik tak memicu
             | `input` bawaan peramban, jadi autosave 1200ms di .card-body takkan
             | pernah menyala untuk foto yang baru disisipkan.
             |
             | Editor yang dikosongkan menghasilkan '<p><br></p>'; itu dinolkan di
             | sini juga (bukan hanya di server) supaya penanda wajib-diisi ikut
             | menyala seketika.
             */
            sinkron() {
                const kosong = this.quill.getText().trim() === '' && ! this.quill.root.querySelector('img');
                this.row[this.fkey] = kosong ? '' : this.quill.root.innerHTML;
                this.$nextTick(() => this.$refs.nilai.dispatchEvent(new Event('input', { bubbles: true })));
                this.sapuTempelan();
            },

            /*
             | Foto yang DITEMPEL (Ctrl+V) atau diseret ke dalam editor.
             |
             | Quill menyisipkannya sebagai `data:image/png;base64,…` — bukan
             | berkas. Kalau dibiarkan, fotonya HILANG dari PDF: sanitizer hanya
             | menerima src yang menunjuk lampiran/{DEPT}/{JENIS}/, jadi gambar
             | base64 dibuang sebelum sempat dicetak. Itulah yang membuat foto
             | tampak baik di editor tapi lenyap di pratinjau.
             |
             | Melonggarkan sanitizer BUKAN jawabannya: base64 akan ikut terkirim
             | di SETIAP autosave dan menggembungkan value_json berlipat-lipat —
             | satu tangkapan layar bisa 400KB, dan ia dikirim ulang tiap 1,2
             | detik selama penyusun mengetik.
             |
             | Jadi gambarnya ditukar jadi berkas sungguhan lewat jalur yang sama
             | dengan tombol gambar. Satu jaring ini menangkap tempel, seret-jatuh,
             | dan tempelan HTML dari Word sekaligus — tak perlu tiga penangan
             | peristiwa yang berbeda.
             */
            async sapuTempelan() {
                if (this.menyapu) return;

                if (! this.quill.root.querySelector('img[src^="data:"]')) return;

                this.menyapu = true;
                try {
                    /*
                     | Diambil SATU per satu, bukan sebagai daftar yang disnapshot
                     | di muka: setiap penukaran menggeser indeks Quill DAN membuat
                     | Quill menggambar ulang barisnya, sehingga acuan <img> ke-2
                     | dan seterusnya di daftar tadi sudah basi — Quill.find()
                     | mengembalikan null dan gambarnya jatuh ke jalur cadangan.
                     | Batas 20 putaran menjaga dari gelung tak berujung bila ada
                     | gambar yang tak bisa disingkirkan.
                     */
                    let img;
                    let jaga = 0;

                    while ((img = this.quill.root.querySelector('img[src^="data:"]')) && jaga++ < 20) {
                        const berkas = this.keBerkas(img.getAttribute('src'));
                        const jalur = berkas ? await this.kirim(berkas) : null;

                        // Ditukar lewat API Quill, bukan setAttribute: model
                        // internal Quill masih memegang data URI-nya, dan suntingan
                        // berikutnya akan menggambar ulang dari model itu —
                        // mengembalikan base64 yang barusan kita singkirkan.
                        const blot = window.Quill.find(img);
                        const di = blot ? this.quill.getIndex(blot) : null;

                        if (di === null) {
                            // Tak dikenali Quill (mis. tempelan HTML mentah) —
                            // DOM-nya saja yang disentuh.
                            if (jalur) img.setAttribute('src', '/storage/' + jalur); else img.remove();
                            continue;
                        }

                        this.quill.deleteText(di, 1, 'silent');
                        if (jalur) this.sisipkan('/storage/' + jalur, di, 'silent');
                    }
                } finally {
                    this.menyapu = false;
                }

                this.sinkron();
            },

            /** data URI → File, supaya bisa diunggah seperti berkas pilihan biasa. */
            keBerkas(src) {
                const m = /^data:(image\/(?:png|jpe?g|gif|webp|bmp));base64,([\s\S]+)$/i.exec(src || '');
                if (! m) return null;

                try {
                    const bin = atob(m[2]);
                    const buf = new Uint8Array(bin.length);
                    for (let i = 0; i < bin.length; i++) buf[i] = bin.charCodeAt(i);

                    return new File([buf], 'tempelan', { type: m[1] });
                } catch (_) {
                    return null;
                }
            },

            /*
             | Unggah foto lewat documents.uploadAttachment — route yang SUDAH
             | dipakai field `image`. Yang disisipkan ke editor adalah URL berkas,
             | bukan base64: base64 akan ikut terbawa di SETIAP autosave dan
             | menggembungkan value_json sampai berlipat.
             */
            /** Tombol gambar di toolbar. */
            async unggah(e) {
                const berkas = e.target.files[0];
                e.target.value = '';
                if (! berkas) return;

                const jalur = await this.kirim(berkas);
                if (! jalur) return;

                this.sisipkan('/storage/' + jalur);
            },

            /*
             | Sisipkan gambar sebagai BARISNYA SENDIRI.
             |
             | Blot `image` bawaan Quill bersifat INLINE — disisipkan begitu saja,
             | ia duduk berdampingan dengan kalimat di sekitarnya, dan di dalam
             | tabel AKTIVITAS yang sempit hasilnya berantakan. Permintaan pemilik
             | tegas: gambar selalu di bawah tulisan, tak pernah di sampingnya.
             |
             | Maka barisnya dipotong lebih dulu bila kursor sedang di tengah
             | kalimat, lalu SATU baris kosong ditambahkan sesudah gambar dan
             | kursor ditaruh di sana — penyusun bisa langsung mengetik di bawah
             | fotonya tanpa perlu memindahkan kursor sendiri.
             |
             | Jalur cetak sudah sejalan dengan ini: PembersihHtml::blok() memang
             | menjadikan tiap gambar satu baris tabel tersendiri.
             */
            sisipkan(src, di = null, sumber = 'user') {
                let at = di === null
                    ? (this.quill.getSelection(true) || { index: this.quill.getLength() - 1 }).index
                    : di;

                /*
                 | Format BLOK baris tempat gambar disisipkan — dan inilah yang
                 | dulu salah.
                 |
                 | Di Quill, format blok (list/blockquote/indent) TIDAK menempel
                 | pada barisnya melainkan pada karakter '\n' PENUTUP baris itu.
                 | '\n' polos yang kita sisipkan sesudah gambar karena itu menjadi
                 | penutup baris si gambar — tanpa format — dan butir daftar tempat
                 | foto ditempel kehilangan nomornya: menempel tangkapan layar di
                 | butir ke-4 membuat "4." lenyap seketika.
                 |
                 | Jadi kedua '\n' sisipan HARUS memikul format yang sama. Format
                 | sebaris (tebal/miring) sengaja disaring keluar — yang diwariskan
                 | hanya bentuk barisnya.
                 */
                const semua = this.quill.getFormat(at);
                const blok = {};
                ['list', 'indent', 'blockquote', 'header', 'align', 'direction'].forEach((k) => {
                    if (semua[k] !== undefined) blok[k] = semua[k];
                });

                if (at > 0 && this.quill.getText(at - 1, 1) !== '\n') {
                    this.quill.insertText(at, '\n', blok, sumber);
                    at += 1;
                }

                this.quill.insertEmbed(at, 'image', src, sumber);
                this.quill.insertText(at + 1, '\n', blok, sumber);
                this.quill.setSelection(at + 2, 0);
            },

            /**
             | SATU-SATUNYA jalur unggah — dipakai tombol gambar maupun jaring
             | tempelan, supaya keduanya tak pernah berbeda perlakuan.
             |
             | @returns {Promise<?string>} jalur `lampiran/…`, atau null bila gagal.
             */
            async kirim(berkas) {
                this.galat = ''; this.mengunggah = true;
                try {
                    const fd = new FormData();
                    fd.append('image', await this.kecilkan(berkas));
                    fd.append('section', section);

                    const r = await fetch(uploadUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        body: fd,
                    });
                    const j = await r.json();
                    if (r.ok && j.path) return j.path;

                    this.galat = j.message || 'Gagal mengunggah gambar.';
                } catch (_) {
                    this.galat = 'Gagal mengunggah gambar.';
                } finally {
                    this.mengunggah = false;
                }

                return null;
            },

            /*
             | Perkecil sebelum dikirim.
             |
             | Servernya menerima JPG/PNG maksimal 2MB (uploadAttachment), dan
             | tangkapan layar 4K menembus batas itu dengan mudah — tanpa langkah
             | ini penyusun cuma melihat "Gagal mengunggah gambar" tanpa tahu
             | sebabnya. Gambar yang sudah kecil DIBIARKAN apa adanya supaya PNG
             | tajam (tangkapan layar berteks) tak dirusak kompresi JPEG
             | sia-sia.
             |
             | 1600px cukup: di PDF gambarnya toh dibatasi .akt-img (maks 220pt
             | tinggi, selebar kolom AKTIVITAS), jadi piksel di atas itu tak
             | pernah terlihat — hanya membebani penyimpanan.
             */
            kecilkan(berkas) {
                const MAKS = 1600;
                const sudahBenar = /^image\/(png|jpe?g)$/i.test(berkas.type);

                return new Promise((selesai) => {
                    const url = URL.createObjectURL(berkas);
                    const img = new Image();

                    img.onload = () => {
                        URL.revokeObjectURL(url);

                        if (sudahBenar && img.width <= MAKS && berkas.size <= 1500000) {
                            return selesai(berkas);
                        }

                        const skala = Math.min(1, MAKS / img.width);
                        const c = document.createElement('canvas');
                        c.width = Math.round(img.width * skala);
                        c.height = Math.round(img.height * skala);
                        const ctx = c.getContext('2d');
                        // Latar putih: PNG transparan yang jadi JPEG akan hitam pekat.
                        ctx.fillStyle = '#fff';
                        ctx.fillRect(0, 0, c.width, c.height);
                        ctx.drawImage(img, 0, 0, c.width, c.height);

                        c.toBlob(
                            (b) => selesai(b ? new File([b], 'gambar.jpg', { type: 'image/jpeg' }) : berkas),
                            'image/jpeg',
                            0.9
                        );
                    };

                    // Berkas yang tak bisa dibaca peramban dikirim apa adanya —
                    // biarkan server yang menolak dengan pesannya sendiri.
                    img.onerror = () => { URL.revokeObjectURL(url); selesai(berkas); };
                    img.src = url;
                });
            },
        }));
        /*
         | Pemilih dokumen lampiran (bab VII SOP).
         |
         | Sengaja TIDAK menyimpan nilainya sendiri: nilai tetap milik
         | `row[field]` di komponen `repeatable` induknya. Komponen ini hanya
         | MENYARING & MENGISIKAN, sehingga ketikan bebas pengguna tak pernah
         | tertimpa dan kolomnya tetap terkirim seperti input biasa.
         */
        Alpine.data('docPicker', (opsi = []) => ({
            opsi, open: false, sorot: 0,
            label(o) { return o.nomor + ' — ' + o.judul; },
            cocok(q) {
                const s = (q || '').toLowerCase().trim();
                if (!s) return this.opsi;
                return this.opsi.filter(o => (o.jenis + ' ' + o.nomor + ' ' + o.judul).toLowerCase().includes(s));
            },
            turun(q) { this.open = true; this.sorot = Math.min(this.sorot + 1, this.cocok(q).length - 1); },
            naik() { this.sorot = Math.max(this.sorot - 1, 0); },
            // Enter memilih baris yang tersorot; kalau tak ada yang cocok,
            // ketikan pengguna dikembalikan apa adanya.
            pilih(q) { const o = this.cocok(q)[this.sorot]; return o ? this.label(o) : q; },
        }));
        Alpine.data('userPicker', (initial = []) => ({
            items: initial.length ? initial : [''],
            add() { this.items.push(''); },
            remove(i) { this.items.splice(i, 1); if (!this.items.length) this.items.push(''); },
        }));
        // JSA: analisa bahaya nested (Langkah Kerja → Bahaya → Pengendalian)
        const jsaBahaya = () => ({ risiko: '', pengendalian: [''] });
        const jsaStep = () => ({ langkah: '', bahaya: [jsaBahaya()] });
        Alpine.data('jsaAnalysis', (initial = []) => ({
            steps: (Array.isArray(initial) && initial.length) ? initial.map(s => ({
                langkah: s.langkah || '',
                bahaya: (Array.isArray(s.bahaya) && s.bahaya.length ? s.bahaya : [jsaBahaya()]).map(b => ({
                    risiko: b.risiko || '',
                    pengendalian: (Array.isArray(b.pengendalian) && b.pengendalian.length) ? b.pengendalian : [''],
                })),
            })) : [jsaStep()],
            addStep() { this.steps.push(jsaStep()); },
            removeStep(li) { this.steps.splice(li, 1); if (!this.steps.length) this.steps.push(jsaStep()); },
            addBahaya(li) { this.steps[li].bahaya.push(jsaBahaya()); },
            removeBahaya(li, bi) { this.steps[li].bahaya.splice(bi, 1); if (!this.steps[li].bahaya.length) this.steps[li].bahaya.push(jsaBahaya()); },
            addKendali(li, bi) { this.steps[li].bahaya[bi].pengendalian.push(''); },
            removeKendali(li, bi, pi) { const p = this.steps[li].bahaya[bi].pengendalian; p.splice(pi, 1); if (!p.length) p.push(''); },
        }));
        Alpine.data('wizard', (autosaveUrl, previewUrl, token, previewV) => ({
            savedAt: '', submitting: false, previewing: false, previewUrl, previewV,
            /*
             | Iframe pratinjau sudah punya `src` dari HTML (lihat panel kanan),
             | jadi TIDAK dimuat lagi di sini — memuatnya ulang saat Alpine hidup
             | justru yang dulu membuat panel berkedip tiap pindah langkah.
             |
             | `view=Fit` menyuruh penampil PDF memuat SATU HALAMAN PENUH ke dalam
             | bingkai: halamannya mengecil sampai muat seluruhnya dan menyisakan
             | ruang kosong, bukan terpotong. Karena skalanya dihitung ulang
             | terhadap ukuran bingkai, zoom peramban tak mengubah tampilannya.
             | `toolbar=0&navpanes=0`: nomor halaman sudah tercetak di kop.
             |
             | `v` = sidik isi dokumen dari server. Isi TETAP → URL tetap →
             | peramban menyajikan dari cache-nya sendiri (tak ada permintaan,
             | tak ada kedip). Isi BERUBAH → sidik berubah → dimuat segar.
             */
            loadPreview(v) {
                const f = document.getElementById('previewFrame');
                if (!f) { this.previewing = false; return; }
                if (v) this.previewV = v;
                f.onload = () => { this.previewing = false; };
                const src = this.previewUrl + '?v=' + this.previewV + '#toolbar=0&navpanes=0&view=Fit';
                if (f.src.endsWith(src) || f.src === src) { this.previewing = false; return; }
                f.src = src;
            },
            _post() {
                const data = new FormData(this.$root); data.append('autosave', '1');
                return fetch(autosaveUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }, body: data }).then(r => r.ok ? r.json() : null);
            },
            autosave() { if (this.submitting) return; this._post().then(j => { if (j && j.saved_at) this.savedAt = j.saved_at; }).catch(() => {}); },
            // Simpan teks dulu (AJAX) lalu muat ulang HANYA iframe preview — form tak reload.
            // Sidik baru datang dari respons autosave; kalau isinya tak berubah,
            // sidiknya sama dan iframe dibiarkan apa adanya.
            preview() { this.previewing = true; this._post().then(j => this.loadPreview(j && j.v)).catch(() => this.loadPreview()); },
            // Validasi hanya saat MAJU (next) atau kirim — Simpan/Kembali boleh parsial.
            onSubmit(e) {
                if (e.submitter && e.submitter.value === 'next' && !ppValidateRequired(this.$root)) {
                    e.preventDefault();
                    return;
                }
                this.submitting = true;
            },
            /*
             | Field TIDAK WAJIB yang dibiarkan kosong padahal BARISNYA terisi.
             |
             | Aturan "barisnya terisi" sengaja sama dengan DocumentWizard::cleanValue()
             | di server: baris yang seluruh kolomnya kosong memang dibuang saat
             | disimpan, jadi memperingatkan baris standby yang tak disentuh hanya
             | akan mengganggu tanpa sebab.
             |
             | @returns {Array<{el: Element, label: string}>}
             */
            warnKosong() {
                const terisi = (el) => el.type !== 'file' && String(el.value).trim() !== '';

                // (a) Kolom kosong pada baris yang SEBAGIAN sudah terisi.
                const perKolom = Array.from(this.$root.querySelectorAll('[data-pp-warn]')).filter(el => {
                    if (String(el.value).trim() !== '') return false;

                    const baris = el.closest('.border.rounded');
                    if (!baris) return false;

                    return Array.from(baris.querySelectorAll('input, textarea'))
                        .some(lain => lain !== el && terisi(lain));
                }).map(el => ({ el, label: el.getAttribute('data-pp-warn') }));

                // (b) Bab tak wajib yang dilewatkan BULAT-BULAT. Tanpa ini ia lolos
                // tanpa peringatan: aturan (a) hanya menyala pada baris yang sudah
                // tersentuh, dan bab yang kosong seluruhnya tak punya baris seperti itu.
                const perBab = Array.from(this.$root.querySelectorAll('[data-pp-warn-bab]'))
                    .filter(bab => !Array.from(bab.querySelectorAll('input, textarea')).some(terisi))
                    .map(bab => ({
                        el: bab.querySelector('input, textarea'),
                        label: bab.getAttribute('data-pp-warn-bab') + ' (belum diisi sama sekali)',
                    }))
                    // Bab yang sakelarnya DIMATIKAN tak diperingatkan: pembuatnya
                    // sudah menyatakan tak memakainya, dan babnya memang tak
                    // akan tercetak. `[inert]` = bab mati, offsetParent null =
                    // bab yang tersembunyi karena sebab lain.
                    .filter(w => w.el && w.el.offsetParent !== null && ! w.el.closest('[inert]'));

                return [...perBab, ...perKolom];
            },
            confirmKirim() {
                // Validasi dulu: semua field wajib harus terisi (arahkan ke yg kosong).
                if (!ppValidateRequired(this.$root)) return;

                const kosong = this.warnKosong();
                if (kosong.length) {
                    this.peringatanKosong(kosong);
                    return;
                }

                this.kirimSekarang();
            },
            /*
             | Peringatan DUA LANGKAH untuk field yang boleh kosong.
             |
             | Langkah pertama menyebut apa saja yang kosong dan menawarkan
             | "Isi dulu". Yang memilih "Tetap kirim" ditanya SEKALI LAGI —
             | dokumen yang sudah dikirim tak bisa disunting lagi, jadi tombol
             | yang meloloskan kekosongan tak boleh sedekat satu klik dari niat
             | asal-asalan.
             */
            peringatanKosong(kosong) {
                const daftar = [...new Set(kosong.map(k => k.label))]
                    .map(l => `<li>${l}</li>`).join('');

                Swal.fire({
                    title: 'Ada isian yang masih kosong',
                    html: `<div class="text-start">Kolom berikut belum diisi:<ul class="mb-0 mt-2">${daftar}</ul>
                           <p class="mt-2 mb-0 small text-muted">Kolom ini tidak wajib — dokumen tetap bisa dikirim tanpanya.</p></div>`,
                    icon: 'info', showCancelButton: true,
                    confirmButtonText: 'Isi dulu', cancelButtonText: 'Tetap kirim',
                    confirmButtonColor: '#ea580c', cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                }).then((r) => {
                    if (r.isConfirmed) {
                        // "Isi dulu" → antarkan ke kolom kosong yang pertama.
                        kosong[0].el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        kosong[0].el.focus({ preventScroll: true });
                        return;
                    }
                    if (r.dismiss !== Swal.DismissReason.cancel) return;   // ESC / klik luar = batal

                    Swal.fire({
                        title: 'Kirim tanpa mengisinya?',
                        text: 'Dokumen tidak bisa diedit setelah dikirim (masih bisa Ditarik selama belum ditinjau).',
                        icon: 'warning', showCancelButton: true,
                        confirmButtonText: 'Ya, kirim apa adanya', cancelButtonText: 'Batal',
                        confirmButtonColor: '#ea580c', cancelButtonColor: '#6c757d',
                    }).then((r2) => { if (r2.isConfirmed) this.kirim(); });
                });
            },
            kirimSekarang() {
                Swal.fire({
                    title: 'Kirim Dokumen?',
                    text: 'Kirim dokumen untuk ditinjau? Dokumen tidak bisa diedit setelah dikirim (masih bisa Ditarik selama belum ditinjau).',
                    icon: 'warning', showCancelButton: true,
                    confirmButtonText: 'Ya, kirim', cancelButtonText: 'Batal',
                    confirmButtonColor: '#ea580c', cancelButtonColor: '#6c757d',
                }).then((r) => { if (r.isConfirmed) this.kirim(); });
            },
            /* Pengiriman sesungguhnya — dipanggil sesudah konfirmasi mana pun. */
            kirim() {
                this.submitting = true;
                this.$root.querySelector('input[name=action]')?.remove();
                const h = document.createElement('input'); h.type = 'hidden'; h.name = 'action'; h.value = 'submit'; this.$root.appendChild(h);
                this.$root.submit();
            },
        }));
    });
</script>
@endpush
