{{--
    Isi formulir profil akses — dipakai DUA kali: modal "Profil Baru" dan modal
    "Ubah". Aturannya sama persis (StoreAccessProfileRequest melayani keduanya),
    jadi menuliskannya dua kali hanya menciptakan dua daftar kotak centang yang
    suatu hari menyimpang — dan yang menyimpang diam-diam adalah daftar JENIS.

    @param App\Models\AccessProfile|null $profile  null = pembuatan baru
    @param array<int, string>            $jenis    DocumentType::kode()
--}}
@php
    $profile ??= null;
    $terpilih = $profile?->jenis_dibolehkan ?? [];
    // Sufiks id: modal baru & tiap modal ubah hidup bersamaan di satu halaman,
    // jadi `for`/`id` yang kembar akan membuat label salah tunjuk.
    $sufiks = $profile?->id ?? 'baru';
@endphp

<div class="mb-3">
    <label class="form-label small fw-semibold" for="nama-{{ $sufiks }}">Nama Profil</label>
    <input type="text" name="nama" id="nama-{{ $sufiks }}" class="form-control" required maxlength="255"
           value="{{ old('nama', $profile?->nama) }}" placeholder="Penyusun SOP &amp; IK">
</div>

<div class="mb-3">
    <label class="form-label small fw-semibold" for="ket-{{ $sufiks }}">
        Keterangan <span class="text-muted fw-normal">(opsional)</span>
    </label>
    <input type="text" name="keterangan" id="ket-{{ $sufiks }}" class="form-control" maxlength="255"
           value="{{ old('keterangan', $profile?->keterangan) }}"
           placeholder="Untuk GL yang hanya menyusun prosedur">
</div>

<div class="mb-3">
    <label class="form-label small fw-semibold d-block">Jenis dokumen yang boleh disusun</label>
    <div class="d-flex flex-wrap gap-3">
        @foreach ($jenis as $kode)
            @php [$ikon, $warna] = App\Models\DocumentType::rupa($kode); @endphp
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="jenis_dibolehkan[]"
                       value="{{ $kode }}" id="jn-{{ $sufiks }}-{{ $kode }}"
                       @checked(in_array($kode, $terpilih, true))>
                <label class="form-check-label" for="jn-{{ $sufiks }}-{{ $kode }}">
                    <i class="bi {{ $ikon }}" style="color: {{ $warna }}"></i> {{ $kode }}
                </label>
            </div>
        @endforeach
    </div>
    <div class="form-text">Tak satu pun dicentang = profil ini tidak memberi wewenang menyusun.</div>
</div>

<div class="form-check">
    <input class="form-check-input" type="checkbox" name="boleh_review_jsa" value="1"
           id="jsa-{{ $sufiks }}" @checked($profile?->boleh_review_jsa)>
    <label class="form-check-label" for="jsa-{{ $sufiks }}">
        Boleh <strong>meninjau</strong> dokumen JSA
    </label>
    <div class="form-text">
        GL departemen SHE sudah berwenang otomatis &mdash; centang ini untuk GL departemen lain.
    </div>
</div>
