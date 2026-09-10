{{-- repeatable_group: repeating block of fields (Aktivitas: sub_judul+deskripsi+PIC; Lampiran: judul+isi) --}}
@php
    $key = $section['key'];
    $prefix = $section['auto_number'] ?? '';
    $fields = $section['group_fields'] ?? $section['fields'] ?? [];
    $rows = is_array($value) ? array_values($value) : [];
    $minItems = $section['min_groups'] ?? $section['min_items'] ?? 1;
    $addLabel = $section['add_button_label'] ?? '+ Tambah';

    /*
     | Bab yang TIDAK WAJIB (`required: false` di schema, mis. FLOWCHART &
     | LAMPIRAN pada SOP).
     |
     | Seluruh kolomnya diberi `data-optional` supaya ppValidateRequired
     | melewatinya — kalau tidak, baris STANDBY yang sengaja disediakan kosong
     | justru MENGHALANGI tombol Kirim, dan penyusun terpaksa mengisi bab yang
     | memang tak dipakainya. Kekosongannya tetap diingatkan saat Kirim lewat
     | `warn_if_empty`; peringatan, bukan penghalang.
     */
    $wajib = $section['required'] ?? true;
    $tandaOpsional = $wajib ? '' : 'data-optional';

    /*
     | BAB OPSIONAL (`toggle_key`, mis. FLOWCHART) & dua varian nomornya.
     |
     | $sakelar  → bab INI punya sakelar sendiri: dapat kepala sakelar + x-show.
     | $babLain  → label/prefix bab ini saat SEMUA bab opsional dimatikan.
     |             Berlaku untuk bab yang IKUT bergeser (AKTIVITAS, LAMPIRAN),
     |             bukan cuma bab yang bersakelar.
     |
     | Nomornya datang dari PHP, sudah jadi, dua-duanya. Alpine hanya memilih —
     | tak ada satu pun angka bab yang dihitung di peramban.
     */
    $sakelar = $section['toggle_key'] ?? null;
    $babLain = ($labelMati ?? [])[$key] ?? null;
    $labelMatiBab = $babLain['label'] ?? null;
    $prefixMati = $babLain['auto_number'] ?? $prefix;
    $labelBeda = $labelMatiBab !== null && $labelMatiBab !== ($section['label'] ?? null);
@endphp

<div class="mb-4" x-data="repeatable(@js($rows), @js($fields), {{ $minItems }}, '{{ $prefix }}', '{{ $prefixMati }}')">
    {{-- Kepala bab: judul di kiri, sakelar di kanan. Sakelarnya sengaja DI LUAR
         pembungkus isian di bawah — pembungkus itu dimatikan saat sakelarnya
         mati, dan sakelar yang ikut mati tak bisa dinyalakan lagi. --}}
    <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
        {{-- Teks dari SERVER = keadaan sekarang; x-text hanya menukarnya saat
             sakelarnya digeser. Nomor bab tak berkedip saat halaman dimuat, dan
             tetap benar walau JS gagal dimuat. --}}
        <label class="form-label fw-semibold mb-0"
               @if ($labelBeda) x-text="$store.bab.on ? @js($section['label']) : @js($labelMatiBab)" @endif
        >{{ $labelBeda && ! ($babAktif ?? true) ? $labelMatiBab : ($section['label'] ?? $key) }}</label>

        @if ($sakelar)
            <div class="form-check form-switch mb-0 flex-shrink-0">
                <input class="form-check-input" type="checkbox" role="switch" id="sw-{{ $key }}" x-model="$store.bab.on">
                <label class="form-check-label small" for="sw-{{ $key }}">{{ $section['toggle_label'] ?? 'Gunakan bab ini' }}</label>
                <input type="hidden" name="sections[{{ $sakelar }}]" :value="$store.bab.on ? '1' : '0'">
            </div>
        @endif
    </div>
    @isset($section['help'])<div class="form-text mb-2">{{ $section['help'] }}</div>@endisset

    {{-- `data-pp-warn-bab` = bab tak wajib yang kekosongannya SELURUHNYA tetap
         diingatkan saat Kirim. Peringatan per-kolom di dalamnya hanya menyala
         bila barisnya sudah terisi sebagian, jadi tanpa penanda bab ini bab
         yang dilewatkan bulat-bulat akan lolos tanpa sepatah pun peringatan.

         Bab bersakelar yang DIMATIKAN: isiannya tetap dirender, hanya diredupkan
         dan dibuat `inert` — tak bisa diklik, tak bisa di-tab, tak dibaca
         pembaca layar. Sengaja BUKAN `disabled`: kolom disabled tidak ikut
         terkirim, jadi sekali disimpan isian yang sudah diketik akan hilang.
         `inert` tetap mengirimkannya. --}}
    @php
        // Keadaan awal dari SERVER (`inert` + kelas redup), supaya bab yang
        // memang dimatikan tak tampil hidup sekejap sebelum Alpine jalan.
        $babMati = $sakelar && ! ($babAktif ?? true);
    @endphp
    <div class="pp-bab{{ $babMati ? ' pp-bab-mati' : '' }}" {{ $babMati ? 'inert' : '' }}
         @unless ($wajib) @if ($section['warn_if_empty'] ?? false) data-pp-warn-bab="{{ $section['label'] ?? $key }}" @endif @endunless
         @if ($sakelar) x-effect="$el.inert = ! $store.bab.on" :class="{ 'pp-bab-mati': ! $store.bab.on }" @endif>

    {{-- :key WAJIB uids[i], bukan i — lihat komentar `uids` di edit.blade.php:
         dgn kunci indeks, menghapus satu baris membuat Alpine memakai ulang DOM
         baris berikutnya dan editor Quill di dalamnya menampilkan tulisan yang
         salah. --}}
    <template x-for="(row, i) in rows" :key="uids[i]">
        <div class="border rounded p-3 mb-3 bg-body-tertiary position-relative">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-primary font-monospace" x-text="label(i)" x-show="'{{ $prefix }}'"></span>
                <button type="button" class="btn btn-sm btn-outline-danger" @click="remove(i)"><i class="bi bi-trash"></i> Hapus</button>
            </div>
            @foreach ($fields as $f)
                @php
                    $ftype = $f['type'] ?? 'text';
                    /*
                     | Field TIDAK WAJIB yang tetap sebaiknya diisi (`warn_if_empty`
                     | di schema). Penandanya dibaca confirmKirim() di edit.blade.php,
                     | yang memperingatkan saat barisnya terisi tapi field ini kosong.
                     | Ia hanya mengingatkan — servernya tetap menerima yang kosong.
                     */
                    $tandaPeringatan = ($f['warn_if_empty'] ?? false)
                        ? 'data-pp-warn="'.e($f['label'] ?? $f['key']).'"'
                        : '';
                @endphp
                <div class="mb-2">
                    <label class="form-label small fw-semibold mb-1">{{ $f['label'] ?? $f['key'] }}</label>
                    @if ($ftype === 'textarea')
                        <textarea class="form-control" rows="3" {!! $tandaOpsional !!} {!! $tandaPeringatan !!} :name="`sections[{{ $key }}][${i}][{{ $f['key'] }}]`" x-model="row['{{ $f['key'] }}']" placeholder="{{ $f['placeholder'] ?? '' }}"></textarea>
                    @elseif ($ftype === 'document_picker')
                        {{-- Combobox: SATU kolom yang bisa dipilih dari daftar
                             dokumen Berlaku ATAU diketik sendiri. Kolomnya sendiri
                             yang menyimpan nilai, jadi ketikan bebas tak pernah
                             hilang — daftar hanya MENGISIKAN teks ke kolom itu.
                             `datalist` bawaan peramban sempat dipakai tapi tak
                             bisa menampilkan lencana jenis & nomor bergaya
                             monospace, dan rupanya beda-beda tiap peramban. --}}
                        @php $fk = $f['key']; @endphp
                        <div class="pp-docpick" x-data="docPicker(@js($dokumenBerlaku ?? []))"
                             @click.outside="open = false" @keydown.escape="open = false">
                            {{-- Susunan kolom + tombol PERSIS seperti "Pembuat Tambahan":
                                 `d-flex gap-2` dengan kolom `flex-grow-1` dan tombol
                                 `.btn-field` (2.7rem, align-self:stretch) → tingginya
                                 mengikuti kolom di sampingnya. `input-group` sempat
                                 dipakai dan tombolnya jadi tak sepadan dgn kolom. --}}
                            <div class="d-flex gap-2">
                                <input type="text" class="form-control flex-grow-1" autocomplete="off" role="combobox"
                                       {!! $tandaOpsional !!} {!! $tandaPeringatan !!}
                                       :aria-expanded="open" aria-autocomplete="list"
                                       :name="`sections[{{ $key }}][${i}][{{ $fk }}]`"
                                       x-model="row['{{ $fk }}']"
                                       @focus="open = true" @input="open = true; sorot = 0"
                                       @keydown.arrow-down.prevent="turun(row['{{ $fk }}'])"
                                       @keydown.arrow-up.prevent="naik()"
                                       @keydown.enter.prevent="row['{{ $fk }}'] = pilih(row['{{ $fk }}']); open = false"
                                       placeholder="{{ $f['placeholder'] ?? '' }}">
                                <button type="button" class="btn btn-outline-secondary btn-field" tabindex="-1"
                                        @click="open = ! open" :aria-label="open ? 'Tutup daftar' : 'Buka daftar dokumen'">
                                    <i class="bi" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                                </button>
                            </div>

                            <div class="pp-docpick-menu" x-show="open" x-cloak x-transition.opacity.duration.120ms>
                                <template x-if="! cocok(row['{{ $fk }}']).length">
                                    <div class="pp-docpick-kosong">
                                        <i class="bi bi-pencil"></i>
                                        Tak ada dokumen yang cocok — teks yang Anda ketik dipakai apa adanya.
                                    </div>
                                </template>
                                <template x-for="(o, oi) in cocok(row['{{ $fk }}'])" :key="o.nomor">
                                    <button type="button" class="pp-docpick-item" :class="{ 'sorot': oi === sorot }"
                                            @mouseenter="sorot = oi"
                                            @click="row['{{ $fk }}'] = label(o); open = false">
                                        <span class="badge-soft badge-soft-primary pp-docpick-jenis" x-text="o.jenis"></span>
                                        <span class="pp-docpick-teks">
                                            <span class="font-monospace fw-semibold d-block" x-text="o.nomor"></span>
                                            <span class="text-muted" x-text="o.judul"></span>
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div class="form-text">
                            <i class="bi bi-list-ul"></i>
                            {{ count($dokumenBerlaku ?? []) }} dokumen Berlaku di departemen Anda. Bisa juga diketik sendiri bila belum ada di sistem.
                        </div>
                    @elseif ($ftype === 'image')
                        {{-- Gambar diunggah LANGSUNG saat dipilih (AJAX) → path disimpan di
                             hidden input. Preview cukup memuat ulang iframe (tanpa reload
                             halaman) & foto tak hilang saat pindah langkah. --}}
                        <div x-data="{
                            fkey: '{{ $f['key'] }}', uploading: false, err: '',
                            get hasImg() { return this.row[this.fkey] && String(this.row[this.fkey]).startsWith('lampiran/'); },
                            async up(e) {
                                const file = e.target.files[0]; if (!file) return;
                                this.err = ''; this.uploading = true;
                                const fd = new FormData(); fd.append('image', file); fd.append('section', '{{ $key }}');
                                try {
                                    const r = await fetch('{{ route('documents.uploadAttachment', $document) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd });
                                    const j = await r.json();
                                    if (r.ok && j.path) { this.row[this.fkey] = j.path; } else { this.err = j.message || 'Gagal mengunggah gambar.'; }
                                } catch (_) { this.err = 'Gagal mengunggah gambar.'; }
                                this.uploading = false; e.target.value = '';
                            }
                        }">
                            <input type="hidden" :name="`sections[{{ $key }}][${i}][{{ $f['key'] }}]`" :value="row[fkey]">
                            <template x-if="hasImg">
                                <div class="mb-2">
                                    <img :src="'/storage/' + row[fkey]" class="img-thumbnail" style="max-height:160px">
                                    <button type="button" class="btn btn-sm btn-outline-danger ms-2" @click="row[fkey]=''"><i class="bi bi-x"></i> Hapus gambar</button>
                                </div>
                            </template>
                            <input type="file" accept="{{ $f['image_accept'] ?? 'image/jpeg,image/png' }}" class="form-control" @change="up($event)" :disabled="uploading">
                            <div class="form-text" x-show="!uploading">Format JPG/PNG, maks {{ $f['image_max_mb'] ?? 2 }}MB. Foto akan muncul di preview &amp; PDF.</div>
                            <div class="form-text text-primary" x-show="uploading" x-cloak><span class="spinner-border spinner-border-sm"></span> Mengunggah…</div>
                            <div class="text-danger small mt-1" x-show="err" x-text="err" x-cloak></div>
                        </div>
                    @elseif ($ftype === 'rich_text')
                        {{-- Kolom berformat (Quill). Satu kunci schema, tiga jenis
                             dokumen ikut — tanpa view/route baru (CLAUDE.md §3/§7). --}}
                        @include('documents.fields._rich_text')
                    @else
                        <input type="text" class="form-control" {!! $tandaOpsional !!} {!! $tandaPeringatan !!} :name="`sections[{{ $key }}][${i}][{{ $f['key'] }}]`" x-model="row['{{ $f['key'] }}']" placeholder="{{ $f['placeholder'] ?? '' }}">
                    @endif
                </div>
            @endforeach
        </div>
    </template>

        <button type="button" class="btn btn-sm btn-outline-primary" @click="add()"><i class="bi bi-plus-lg"></i> {{ $addLabel }}</button>
    </div>
</div>
