{{--
    | rich_text — kolom berformat (Quill 2 "snow" bertema SmartPro).
    |
    | Dipakai kolom "Deskripsi Aktivitas" pada SOP/SP/IK, dan sengaja dibuat
    | sebagai TIPE FIELD, bukan tipe section: perbedaan antar-jenis dokumen
    | datang dari schema (CLAUDE.md §7), jadi satu kunci schema `rich_text`
    | menyalakan editor ini di ketiga jenis sekaligus — tanpa view/route baru.
    |
    | Nilainya TIDAK dipegang Quill melainkan `row[<key>]` milik komponen
    | `repeatable` induknya, lalu dikirim lewat satu input HIDDEN. Dengan begitu
    | ia terkirim, ter-autosave, dan tersimpan persis seperti kolom biasa.
    |
    | Butuh dari pemanggilnya: $key (kunci section), $f (definisi field),
    | $tandaOpsional, $tandaPeringatan, $document, dan — dari x-for induknya —
    | variabel `row` serta `i`.
--}}
@php $fkey = $f['key']; @endphp

<div x-data="richText(
        '{{ $fkey }}',
        '{{ $key }}',
        '{{ route('documents.uploadAttachment', $document) }}',
        '{{ csrf_token() }}',
        @js($f['placeholder'] ?? '')
     )"
     x-init="pasang()">

    {{-- Pembawa nilai sesungguhnya. `data-pp-rich` dibaca ppValidateRequired
         (layouts/app.blade.php) — hidden dikecualikan dari pemindaian biasa,
         jadi tanpa penanda ini kolom wajib yang kosong akan lolos diam-diam. --}}
    <input type="hidden" data-pp-rich {!! $tandaOpsional !!} {!! $tandaPeringatan !!}
           x-ref="nilai"
           :name="`sections[{{ $key }}][${i}][{{ $fkey }}]`"
           :value="row['{{ $fkey }}'] || ''">

    <div class="pp-rt">
        {{-- Quill MENGGANTI simpul ini dengan .ql-container miliknya, jadi ia
             harus kosong dan tak ber-x-model apa pun. --}}
        <div x-ref="editor"></div>
    </div>

    <div class="form-text text-primary" x-show="mengunggah" x-cloak>
        <span class="spinner-border spinner-border-sm"></span> Mengunggah gambar…
    </div>
    <div class="text-danger small mt-1" x-show="galat" x-text="galat" x-cloak></div>

    {{-- Berkas dipilih lewat input tersembunyi milik komponen, bukan lewat
         dialog bawaan Quill: unggahannya harus melewati documents.uploadAttachment
         supaya foto tersimpan sebagai BERKAS di lampiran/{DEPT}/{JENIS}/ — bukan
         base64 yang menggembungkan value_json dan ikut terbawa tiap autosave. --}}
    <input type="file" class="d-none" x-ref="berkas"
           accept="{{ $f['image_accept'] ?? 'image/jpeg,image/png' }}"
           @change="unggah($event)">
</div>
