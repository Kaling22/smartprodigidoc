{{-- user_picker: choose user(s) showing Nama + NRP + Dept (PRD v2 §2.2). --}}
@php
    $key = $section['key'];
    $multiple = $section['multiple'] ?? false;
    $required = $section['required'] ?? false;
    $options = ($candidates[$key] ?? collect());
    $selected = $value;

    /*
    | Peninjau & penyetuju memakai PAPAN KETERSEDIAAN, bukan dropdown
    | (FITUR-BARU-v4 §6): siapa yang bisa menangani sekarang, dan yang tidak —
    | kapan ia kembali. Section lain (pembuat_tambahan) tetap dropdown; jadwal
    | seorang penyusun tak mengubah keputusan siapa pun.
    |
    | Dipilih berdasarkan KEY section, bukan tipe baru di schema — sehingga
    | `document_types.schema_json` di database tak perlu disentuh sama sekali.
    |
    | Daftarnya dibaca dari ReviewerAvailability::PAPAN, bukan ditulis ulang di
    | sini: selama Blade & React hidup berdampingan (Fase 9–13), dua salinan
    | aturan yang sama pasti menyimpang.
    */
    $pakaiPapan = array_key_exists($key, \App\Services\ReviewerAvailability::PAPAN)
        && ! $multiple
        && isset($ketersediaan);

    /*
    | Kandidat yang sedang cuti sudah DIBUANG dari daftar oleh
    | DocumentWizard::coAuthorCandidates(). Yang masih tersisa di sini hanyalah
    | orang yang SUDAH tercatat pada dokumen ini lalu mengajukan cuti — ia
    | sengaja ditahan supaya namanya tak lenyap dari halaman pengesahan.
    | Keterangannya ditulis agar GL tahu sebabnya, bukan mengira daftarnya keliru.
    */
    $ketOpsi = $ketersediaan[$key] ?? [];
@endphp

@if ($pakaiPapan)
    {{-- `memblokir = false` untuk penyetuju. Dua akibatnya sekaligus: batas beban
         tak berlaku (PJO hanya satu orang — memblokirnya menghentikan seluruh SOP
         di 7 departemen), dan kolom angka beban tak dirender (angka itu mengukur
         beban PENINJAUAN, bukan tugas menyetujui). Di sana ketersediaan tampil
         sebagai keterangan saja. --}}
    @include('documents.fields._papan-ketersediaan', [
        'options' => $options,
        'ketersediaan' => $ketersediaan[$key] ?? [],
        'memblokir' => \App\Services\ReviewerAvailability::PAPAN[$key],
    ])
@else

<div class="mb-4">
    <label class="form-label fw-semibold">{{ $section['label'] ?? $key }}@if($required) <span class="text-danger">*</span>@endif</label>
    @isset($section['hint'])<div class="form-text mb-2">{{ $section['hint'] }}</div>@endisset

    @if ($multiple)
        <div x-data="userPicker(@js(is_array($selected) ? array_values($selected) : []))">
            <template x-for="(sel, i) in items" :key="i">
                <div class="d-flex gap-2 mb-2">
                    {{-- Pembuat tambahan TIDAK wajib → data-optional (bila section tak required)
                         agar tak memicu validasi "kolom belum diisi" saat dibiarkan kosong (#2). --}}
                    <select class="form-select flex-grow-1" :name="`sections[{{ $key }}][]`" x-model="items[i]" @unless($required) data-optional @endunless>
                        <option value="">— Pilih —</option>
                        @foreach ($options as $o)
                            @php $jenisOff = ($ketOpsi[$o->id]['off'] ?? false) ? $ketOpsi[$o->id]['jenis'] : null; @endphp
                            <option value="{{ $o->id }}">{{ $o->name }} — {{ $o->nrp }} — {{ $o->department->code ?? '-' }}@if($jenisOff) · sedang {{ \Illuminate\Support\Str::lower($jenisOff) }}@endif</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-outline-danger btn-field" @click="remove(i)"><i class="bi bi-x-lg"></i></button>
                </div>
            </template>
            <button type="button" class="btn btn-sm btn-outline-primary" @click="add()"><i class="bi bi-plus-lg"></i> Tambah Pembuat</button>
        </div>
    @else
        <select class="form-select" name="sections[{{ $key }}]" @if($required) required @endif>
            <option value="">— Pilih —</option>
            @foreach ($options as $o)
                <option value="{{ $o->id }}" @selected($selected == $o->id)>{{ $o->name }} — {{ $o->nrp }} — {{ $o->department->code ?? '-' }}</option>
            @endforeach
        </select>
        @if ($options->isEmpty())
            <div class="form-text text-warning"><i class="bi bi-exclamation-triangle"></i> Belum ada kandidat untuk peran ini.</div>
        @endif
    @endif
</div>
@endif
