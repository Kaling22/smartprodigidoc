{{--
    Papan Ketersediaan Peninjau (FITUR-BARU-v4 §6).

    Menggantikan dropdown untuk section `peninjau`, `peninjau_penyetuju`, dan
    `penyetuju`. Semua kandidat berbaris di bawah SATU sumbu waktu, jadi hari
    yang sama sejajar antar-orang: GL bisa memindai satu kolom ke bawah dan
    menyimpulkan "Kamis semua sudah masuk". Tugasnya memang MEMBANDINGKAN,
    bukan membaca satu per satu — itulah yang tak bisa dilakukan dropdown.

    Nama hari sengaja TIDAK ditulis di sini (berbeda dengan kalender di kartu
    dashboard). Empat belas label mungil berjejer justru membuat papan terlihat
    dempet; yang benar-benar dibutuhkan GL hanyalah tahu di mana satu minggu
    berakhir — itu diberikan oleh celah pemisah + penanda awal minggu.

    Kendalinya tetap <input type="radio"> sungguhan, dibungkus <label> selebar
    baris. Konsekuensinya gratis dan tanpa JavaScript: klik di mana saja pada
    baris memilihnya, panah keyboard berpindah antar-kandidat, fokus terlihat,
    pembaca layar membacanya sebagai satu grup pilihan, dan kandidat yang
    penuh/off cukup diberi `disabled` — peramban sendiri yang mencegahnya.

    Butuh: $key, $section, $options (kandidat), $selected,
           $ketersediaan (array dari ReviewerAvailability::untuk()),
           $memblokir (bool — false untuk penyetuju, lihat di bawah)
    Opsional: $nama (nama field radio; bawaannya `sections[$key]` — wizard)
--}}
@php
    $memblokir = $memblokir ?? true;
    // Di luar wizard papan ini dipakai form lain (mis. pengalihan peninjauan
    // JSA) yang tak mengenal `sections[...]`. Namanya bisa ditimpa; bentuk dan
    // penguncian barisnya tetap satu, jadi mustahil kedua tempat berselisih.
    $nama = $nama ?? "sections[{$key}]";
    $required = $section['required'] ?? false;
    $pitaPertama = $options->isNotEmpty() ? ($ketersediaan[$options->first()->id]['pita'] ?? []) : [];

    // Kandidat yang benar-benar bisa dipilih. Pada section penyetuju semua
    // selalu bisa dipilih — lihat catatan $memblokir di bawah.
    $bisaDipilih = fn ($o) => ! $memblokir || ($ketersediaan[$o->id]['tersedia'] ?? true);
    $adaYangTersedia = $options->contains($bisaDipilih);

    // Siapa yang paling cepat bebas — dipakai keadaan buntu di bawah.
    $tercepat = $options
        ->map(fn ($o) => $ketersediaan[$o->id] ?? null)
        ->filter(fn ($a) => $a && $a['kembali'])
        ->sortBy('kembali')->first();

    // Sel ke-0 dan ke-7 memulai minggu baru → diberi celah pemisah.
    $pekanBaru = fn (int $i) => $i > 0 && $i % 7 === 0;

    // Warna pil beban. `normal` sengaja memakai kelas netral yang sama dengan
    // jenis dokumen tanpa ambang — jadi SOP/IK/SP selalu jatuh ke sini.
    $warnaTingkat = ['padat' => 'warning', 'sibuk' => 'danger'];

    // Legenda warna hanya dicetak bila warnanya BENAR-BENAR muncul di papan ini.
    // Menjelaskan kuning/merah di papan SOP yang seluruhnya netral cuma menyuruh
    // orang menghafal bahasa visual yang tak pernah ia lihat.
    $adaTingkat = $memblokir && $options->contains(fn ($o) => isset($warnaTingkat[$ketersediaan[$o->id]['tingkat'] ?? 'normal']));
@endphp

@include('partials._pita-style')

<div class="mb-4">
    <label class="form-label fw-semibold">
        {{ $section['label'] ?? $key }}@if ($required) <span class="text-danger">*</span>@endif
    </label>
    @isset($section['hint'])<div class="form-text mb-2">{{ $section['hint'] }}</div>@endisset

    @if ($options->isEmpty())
        <div class="alert alert-warning py-2 small mb-0">
            <i class="bi bi-exclamation-triangle"></i> Belum ada kandidat untuk peran ini.
        </div>
    @else
        <div class="pp-papan{{ $memblokir ? ' pp-papan-beban' : '' }}">
            {{-- Sumbu waktu: penanda awal tiap minggu. Sejajar dengan pita tiap
                 baris karena keduanya memakai grid & jumlah sel yang sama. --}}
            <div class="pp-papan-baris pp-papan-head">
                <span class="text-muted" style="font-size:.7rem">Pilih satu baris</span>
                <span class="pp-pita-label" aria-hidden="true">
                    @foreach ($pitaPertama as $i => $sel)
                        <span class="{{ $pekanBaru($i) ? 'pp-sel-pekan-baru ' : '' }}{{ $i >= 7 ? 'pp-hari-lanjut' : '' }}">
                            @if ($i === 0 || $pekanBaru($i))
                                <span class="text-nowrap{{ $i === 0 ? ' fw-semibold text-primary' : '' }}">{{ $sel['tanggal']->translatedFormat('j M') }}</span>
                            @endif
                        </span>
                    @endforeach
                </span>
                @if ($memblokir)
                    <span class="pp-papan-beban-sel text-muted" style="font-size:.7rem">Beban</span>
                @endif
            </div>

            @foreach ($options as $o)
                @php
                    $a = $ketersediaan[$o->id] ?? ['beban' => 0, 'batas' => 0, 'tersedia' => true, 'off' => false, 'penuh' => false, 'tingkat' => 'normal', 'alasan' => '', 'pita' => []];
                    $terkunci = ! $bisaDipilih($o);
                @endphp
                <label class="pp-kandidat{{ $terkunci ? ' pp-kandidat-terkunci' : '' }}">
                    <input type="radio" class="form-check-input pp-kandidat-input"
                           name="{{ $nama }}" value="{{ $o->id }}"
                           @checked($selected == $o->id) @disabled($terkunci)
                           @if ($required) required @endif>

                    <span class="pp-kandidat-isi">
                        <span class="pp-papan-baris">
                            <span class="pp-kandidat-orang">
                                <span class="d-block fw-semibold small text-truncate">{{ $o->name }}</span>
                                <span class="d-block text-muted text-truncate" style="font-size:.75rem">
                                    {{ $o->jabatanLabel() }} · {{ $o->department->code ?? '-' }} · {{ $o->nrp }}
                                </span>
                                <span class="d-block small mt-1">
                                    @php
                                        // "Penuh" tak berlaku di section penyetuju — di sana yang
                                        // relevan hanya jadwalnya, dan itu pun sekadar keterangan.
                                        $teks = (! $memblokir && $a['penuh'] && ! $a['off']) ? 'Tersedia hari ini' : $a['alasan'];
                                    @endphp
                                    <span class="{{ $terkunci || $a['off'] ? 'text-warning-emphasis fw-semibold' : 'text-muted' }}">{{ $teks }}</span>
                                </span>
                            </span>

                            {{-- Pita elastis: selnya `flex: 1 1 0`, jadi melebar
                                 mengikuti ruang yang ada alih-alih tersisa kurus
                                 di tengah kolom yang kosong. --}}
                            <span class="pp-pita">
                                @foreach ($a['pita'] as $i => $sel)
                                    <span class="pp-sel pp-av-{{ $sel['status'] }}{{ $pekanBaru($i) ? ' pp-sel-pekan-baru' : '' }}{{ $i >= 7 ? ' pp-hari-lanjut' : '' }}"
                                          title="{{ $o->name }} — {{ $sel['label'] }}"></span>
                                @endforeach
                            </span>

                            {{-- Beban peninjauan sebagai ANGKA, kolom tersendiri rata kanan
                                 (keputusan pemilik A1/A3): angka semua kandidat berbaris
                                 vertikal sehingga terbandingkan sekali lihat — mustahil bila
                                 diselipkan ke baris meta yang lebarnya ikut panjang jabatan.
                                 Nol tetap dicetak: kekosongan yang eksplisit lebih terbaca.
                                 TIDAK dirender di section penyetuju — di sana angka ini
                                 mengukur beban PENINJAUAN, tak ada hubungannya dengan
                                 tugas menyetujui. --}}
                            @if ($memblokir)
                                <span class="pp-papan-beban-sel">
                                    {{-- `role="img"` bukan hiasan: tanpanya `aria-label`
                                         pada <span> polos diabaikan banyak pembaca layar,
                                         dan yang terdengar tinggal "5 dok" telanjang. --}}
                                    <span class="badge-soft badge-soft-{{ $warnaTingkat[$a['tingkat']] ?? 'secondary' }}"
                                          data-tingkat="{{ $a['tingkat'] }}"
                                          role="img"
                                          title="{{ $a['beban'] }} dokumen sedang ditinjau"
                                          aria-label="{{ $a['beban'] }} dokumen sedang ditinjau">
                                        {{ $a['beban'] }} <span class="fw-normal opacity-75">dok</span>
                                    </span>
                                </span>
                            @endif
                        </span>
                    </span>
                </label>
            @endforeach
        </div>

        <div class="pp-av-legenda mt-2">
            <span><i class="pp-av-off"></i> cuti/off</span>
            <span><i class="pp-av-tersedia"></i> tersedia</span>
            {{-- "Kantor tutup" tak punya entri karena tak punya rupa sendiri:
                 akhir pekan tampil sama persis dengan hari kerja. Keterangannya
                 ada di `title` tiap sel. --}}
            @if ($adaTingkat)
                <span><span class="badge-soft badge-soft-warning" style="padding:.1rem .4rem">padat</span> {{ config('smartpro.peninjau.ambang.padat') }}–{{ config('smartpro.peninjau.ambang.sibuk') - 1 }} dokumen</span>
                <span><span class="badge-soft badge-soft-danger" style="padding:.1rem .4rem">sibuk</span> {{ config('smartpro.peninjau.ambang.sibuk') }} dokumen atau lebih</span>
            @endif
            <span>Sumbu waktu mulai hari ini.</span>
        </div>

        @if ($memblokir && ! $adaYangTersedia)
            {{-- Buntu: bisa terjadi di departemen yang hanya punya satu SH.
                 Papan tetap ditampilkan di atas — GL berhak melihat SEBABNYA —
                 lalu diberi tahu apa yang bisa ia lakukan sekarang. --}}
            <div class="alert alert-warning mt-2 mb-0 py-2 small">
                {{-- Tanpa menyebut SEBABNYA: sejak kuota dimatikan (Fase A) yang
                     mengunci baris tinggal jadwal off — tapi kuota bisa dihidupkan
                     lagi lewat config, dan kalimat yang menyebut satu sebab akan
                     berbohong begitu itu terjadi. Sebab per orang sudah tertulis
                     di barisnya masing-masing. --}}
                <div class="fw-semibold"><i class="bi bi-hourglass-split"></i> Belum ada kandidat yang bisa dipilih sekarang.</div>
                Simpan dokumen ini sebagai draft dulu.
                {{-- Cabang @else lama ("Papan ini memperbarui sendiri …") dibuang
                     (PLAN C §C5): itu menerangkan cara kerja papan, bukan apa yang
                     harus dilakukan pembacanya. --}}
                @if ($tercepat)
                    Peninjau berikutnya bisa menerima dokumen lagi pada
                    <strong>{{ $tercepat['kembali']->translatedFormat('l, j F') }}</strong>.
                @endif
            </div>
        @endif
    @endif
</div>
