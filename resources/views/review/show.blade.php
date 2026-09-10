@extends('layouts.app')
@section('title', ($judulLayar ?? 'Tinjau') . ': ' . $document->displayNumber())

@php
    // Only content sections are reviewed (skip user_picker verification fields).
    // jsa_analysis WAJIB ikut: analisa JSA (langkah → bahaya → pengendalian)
    // harus bisa ditinjau sampai tingkat nested-nya.
    $reviewTypes = ['rich_list', 'reference_picker', 'repeatable_group', 'jsa_analysis'];
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            {{-- Halaman ini dipakai TIGA layar: peninjauan SH/DH (substansi),
                 peninjauan Management Development (penulisan), dan Masukan
                 Sejawat antar-GL (PLAN-AKSES-v8 Fase 2 — tanpa keputusan).
                 Yang berbeda hanya alamat tujuan, teks petunjuk, label tombol
                 kembali, dan ada-tidaknya panel AI — semuanya lewat variabel di
                 bawah, dengan nilai bawaan = perilaku SH/DH. Disatukan supaya
                 ketiganya mustahil berbeda tampilan. --}}
            <a href="{{ $backUrl ?? route('review.index') }}" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> {{ $backLabel ?? "Kembali ke antrian" }}</a>
            <h1 class="h5 fw-bold mb-0 mt-1">{{ $document->title }}</h1>
            <span class="font-monospace text-primary small">{{ $document->displayNumber() }}</span>
            <span class="badge-soft">{{ $document->department->code }}</span>
            {{-- Yang ditampilkan adalah EDISI & REVISI DOKUMEN — angka yang sama
                 dengan yang tercetak di kop PDF (`_kop.blade.php` / `_kop_jsa`).
                 Sebelumnya di sini terpasang `revision_round`, yaitu penghitung
                 berapa kali dokumen DIKEMBALIKAN selama peninjauan — angka yang
                 sama sekali lain. Akibatnya dokumen Edisi 1 Revisi 2 terbaca
                 "Revisi ke-0" oleh peninjaunya, dan ia tak punya cara tahu versi
                 mana yang sedang ia nilai. --}}
            <span class="badge-soft badge-soft-info">Edisi {{ $document->edisi ?? 1 }} · Revisi {{ $document->no_revisi }}</span>
            {{-- Putaran peninjauan tetap berguna ("ini kali kedua saya melihatnya"),
                 tapi diberi namanya sendiri dan hanya muncul bila memang pernah
                 dikembalikan. --}}
            @if ($document->revision_round > 0)
                <span class="badge-soft badge-soft-warning">Putaran tinjauan ke-{{ $document->revision_round + 1 }}</span>
            @endif
        </div>
        <div class="d-flex gap-2">
            {{-- Alihkan (butir 3) — hanya JSA yang benar-benar ada di tangan
                 orang ini, dan hanya bila ada peninjau lain yang bisa
                 menerimanya. Syaratnya dihitung controller (kandidatAlih), jadi
                 layar tak punya salinan aturan yang bisa menyimpang. --}}
            @if (($alihKandidat ?? null) && $alihKandidat->isNotEmpty())
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAlihkan{{ $document->id }}">
                    <i class="bi bi-arrow-left-right"></i> Alihkan
                </button>
            @endif
            <a href="{{ route('documents.pdf', $document) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> Lihat PDF</a>
        </div>
    </div>

    {{-- Alasan pengalihan (PLAN B / B4). Lonceng sengaja tetap ringkas dan
         alasannya hanya ikut di email, jadi tanpa kotak ini penerima kehilangan
         sebab pengalihan begitu notifikasinya ditandai terbaca. Dibaca ulang
         dari audit log, sehingga ia bertahan sekeras jejaknya.
         `?? null` karena halaman ini dipakai bersama tahap MD, yang tak
         mengenal pengalihan sama sekali. --}}
    @if ($pengalihan ?? null)
        <div class="alert alert-info small d-flex gap-2">
            <i class="bi bi-arrow-left-right mt-1"></i>
            <div>
                <strong>Dialihkan oleh {{ $pengalihan->meta_json['dari'] ?? 'peninjau sebelumnya' }}</strong>
                <span class="text-muted">· {{ $pengalihan->created_at?->format('d/m/Y H:i') }} WITA</span>
                <div style="white-space:pre-line">{{ $pengalihan->meta_json['alasan'] ?? '' }}</div>
            </div>
        </div>
    @endif

    <div class="alert alert-info small">
        <i class="bi bi-info-circle"></i>
        {!! $petunjuk ?? 'Beri catatan pada item yang perlu diperbaiki. Kosongkan bila item sudah sesuai. Bila ada satu saja catatan, pilih <strong>Kembalikan untuk Revisi</strong>.' !!}
    </div>

    {{-- AI Review Assist (§9). AI hanya membantu; keputusan tetap di peninjau.
         Panel ini HILANG SELURUHNYA bila $aiUrl null — dipakai saat Admin
         mematikan bantuan AI untuk akun MD. Sengaja dihilangkan, bukan dikelabukan:
         tombol mati hanya mengundang orang menekannya berulang kali. --}}
    {{-- $aiUrl WAJIB dikirim controller. Sengaja tanpa nilai bawaan `??`:
         operator itu tak bisa membedakan "tidak dikirim" dari "sengaja null",
         sehingga panel AI tetap muncul walau Admin sudah mematikannya. --}}
    @if ($aiUrl ?? null)
    <div class="card border-0 shadow-sm mb-3" x-data="aiReview('{{ $aiUrl }}', '{{ csrf_token() }}')">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-robot"></i> AI Review Assist <span class="text-muted small fw-normal">— saran, bukan keputusan</span></span>
                <button type="button" class="btn btn-sm btn-outline-primary" @click="analyze()" :disabled="loading">
                    <span x-show="!loading"><i class="bi bi-stars"></i> Analisis dengan AI</span>
                    <span x-show="loading" x-cloak><span class="spinner-border spinner-border-sm"></span> Menganalisis… <span x-text="detik + 'd'"></span></span>
                </button>
            </div>
            {{-- Penantiannya MEMANG lama (model gratis "reasoning": 20–40 detik).
                 Detik berjalan + perkiraan waktu ditulis apa adanya supaya peninjau
                 tak menyangka aplikasinya hang lalu menekan tombolnya berulang. --}}
            <div x-show="loading" x-cloak class="small text-muted mt-2">
                <i class="bi bi-hourglass-split"></i> AI sedang membaca seluruh dokumen — biasanya 1–2 menit untuk JSA. Jangan tutup halaman ini.
            </div>
            <template x-if="summary"><div class="alert alert-light border mt-3 mb-2 small"><strong>Ringkasan AI:</strong> <span x-text="summary"></span></div></template>
            <template x-if="findings.length">
                <div class="mt-2">
                    <div class="small text-muted mb-2">Temuan — <strong>Adopsi</strong> untuk memasukkan ke catatan (boleh diedit dulu), atau <strong>Tolak</strong>:</div>
                    <template x-for="(f, i) in findings" :key="i">
                        <div class="border rounded p-2 mb-2 bg-body-tertiary">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge-soft" x-text="f.section_key + labelRef(f.item_ref)"></span>
                                <span class="badge-soft" :class="sevClass(f.severity)" x-text="f.severity"></span>
                            </div>
                            {{-- Temuan "info" = catatan/kekuatan (bukan masalah) → label beda (#1). --}}
                            <div class="small mt-1"><strong x-text="f.severity === 'info' ? 'Catatan:' : 'Masalah:'"></strong> <span x-text="f.issue"></span></div>
                            <label class="small text-muted mt-1 mb-0"><strong x-text="f.severity === 'info' ? 'Keterangan' : 'Saran perbaikan'"></strong> (boleh diedit):</label>
                            <textarea class="form-control form-control-sm" rows="4" x-model="f.suggestion"></textarea>
                            <div class="mt-1 d-flex gap-1 justify-content-end">
                                <button type="button" class="btn btn-sm btn-success" @click="adopt(i)"><i class="bi bi-check"></i> Adopsi</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="reject(i)"><i class="bi bi-x"></i> Tolak</button>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
            <div x-show="analyzed && !findings.length && !error" x-cloak class="small text-success mt-2"><i class="bi bi-check-circle"></i> Tidak ada temuan signifikan dari AI.</div>
            <div x-show="error" x-cloak class="small text-danger mt-2"><i class="bi bi-exclamation-triangle"></i> <span x-text="error"></span></div>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ $formAction ?? route('review.store', $document) }}">
        @csrf
        @foreach ($schema->allSections() as $section)
            @php $type = $section['type'] ?? 'text'; @endphp
            @continue(! in_array($type, $reviewTypes))
            @php
                $val = $contentMap[$section['key']] ?? [];
                $val = is_array($val) ? $val : [];
                $prior = $priorAnnotations[$section['key']] ?? collect();
            @endphp

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light fw-bold">{{ $section['label'] ?? $section['key'] }}</div>
                <div class="card-body">
                    @if ($type === 'jsa_analysis')
                        {{-- Analisa JSA ditinjau SAMPAI nested: tiap Langkah Kerja,
                             tiap Bahaya & Risiko, dan tiap Tindakan Pengendalian
                             punya kotak catatan sendiri.
                             CATATAN: tanda ✓/✗ per pengendalian DIHAPUS (permintaan
                             pemilik) — pemberian tanda dilakukan MANUAL di lembar
                             cetak. Kolom "Beri tanda" pada PDF tetap ada, kotaknya
                             selalu kosong agar bisa dicentang tangan. --}}
                        @forelse ($val as $li => $step)
                            @php $ref = "L{$li}"; @endphp
                            <div class="border rounded p-2 mb-3">
                                <div class="row g-2 mb-2 pb-2 border-bottom">
                                    <div class="col-md-7">
                                        <div class="small text-muted">Langkah Kerja {{ $li + 1 }}</div>
                                        <div class="fw-semibold">{{ $li + 1 }}. {{ $step['langkah'] ?? '' }}</div>
                                        @foreach ($prior->where('item_ref', $ref) as $a)
                                            <div class="small text-danger mt-1"><i class="bi bi-chat-left-text"></i> Catatan sebelumnya: {{ $a->comment }}</div>
                                        @endforeach
                                    </div>
                                    <div class="col-md-5">
                                        <textarea name="annotations[{{ $section['key'] }}][{{ $ref }}]" data-annot="{{ $section['key'] }}-{{ $ref }}" class="form-control form-control-sm" rows="2" placeholder="Catatan untuk langkah kerja ini (opsional)"></textarea>
                                        <input type="hidden" name="annotations_ai[{{ $section['key'] }}][{{ $ref }}]" value="0" data-ai="{{ $section['key'] }}-{{ $ref }}">
                                    </div>
                                </div>

                                @foreach (($step['bahaya'] ?? []) as $bi => $b)
                                    @php $refB = "L{$li}-B{$bi}"; @endphp
                                    <div class="row g-2 mb-2 ps-3">
                                        <div class="col-md-7">
                                            <div class="small text-danger"><i class="bi bi-exclamation-triangle"></i> Bahaya &amp; Risiko {{ $li + 1 }}.{{ $bi + 1 }}</div>
                                            <div>{{ $b['risiko'] ?? '' }}</div>
                                            @foreach ($prior->where('item_ref', $refB) as $a)
                                                <div class="small text-danger mt-1"><i class="bi bi-chat-left-text"></i> Catatan sebelumnya: {{ $a->comment }}</div>
                                            @endforeach

                                            {{-- Penanda borongan satu bahaya. Sebagian besar bahaya
                                                 pengendaliannya sesuai semua; memaksa peninjau menekan
                                                 tiap baris satu-satu hanya menambah ketukan tanpa
                                                 menambah ketelitian. Tanda per baris TETAP ada dan tetap
                                                 wajib — tombol ini cuma mengisinya sekaligus, dan
                                                 peninjau bebas mengubah baris mana pun sesudahnya.

                                                 Muncul hanya bila pengendaliannya lebih dari satu; untuk
                                                 bahaya berpengendalian tunggal ia cuma menduplikasi
                                                 tombol yang sudah ada tepat di bawahnya. --}}
                                            @if (($pakaiVerdict ?? false) && count($b['pengendalian'] ?? []) > 1)
                                                <div class="d-flex align-items-center flex-wrap gap-2 mt-2">
                                                    <span class="small text-muted">Tandai {{ count($b['pengendalian']) }} pengendalian sekaligus:</span>
                                                    <div class="btn-group btn-group-sm" role="group" aria-label="Tandai semua pengendalian bahaya {{ $li + 1 }}.{{ $bi + 1 }}">
                                                        <button type="button" class="btn btn-outline-success" data-borongan="{{ $refB }}" data-nilai="sesuai"><i class="bi bi-check-all"></i> Semua Sesuai</button>
                                                        <button type="button" class="btn btn-outline-danger" data-borongan="{{ $refB }}" data-nilai="perlu_revisi"><i class="bi bi-x-octagon"></i> Semua Perlu Revisi</button>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-md-5">
                                            <textarea name="annotations[{{ $section['key'] }}][{{ $refB }}]" data-annot="{{ $section['key'] }}-{{ $refB }}" class="form-control form-control-sm" rows="2" placeholder="Catatan untuk bahaya ini (opsional)"></textarea>
                                            <input type="hidden" name="annotations_ai[{{ $section['key'] }}][{{ $refB }}]" value="0" data-ai="{{ $section['key'] }}-{{ $refB }}">
                                        </div>
                                    </div>

                                    @foreach (($b['pengendalian'] ?? []) as $pi => $p)
                                        @php $refP = "L{$li}-B{$bi}-P{$pi}"; @endphp
                                        <div class="row g-2 mb-2 ps-5">
                                            <div class="col-md-7">
                                                <div class="small text-success"><i class="bi bi-shield-check"></i> Tindakan Pengendalian {{ $li + 1 }}.{{ $bi + 1 }}.{{ $pi + 1 }}</div>
                                                <div>{{ $p }}</div>
                                                @foreach ($prior->where('item_ref', $refP) as $a)
                                                    <div class="small text-danger mt-1"><i class="bi bi-chat-left-text"></i> Catatan sebelumnya: {{ $a->comment }}</div>
                                                @endforeach

                                                {{-- Tanda ✓/✗ — HANYA di tingkat Tindakan Pengendalian.
                                                     Di sinilah penilaian K3 sebenarnya terjadi; Langkah &
                                                     Bahaya cukup diberi catatan.

                                                     Mulai KOSONG dan `required`: peninjau harus benar-benar
                                                     menilai tiap pengendalian, bukan menekan Kirim atas
                                                     centang bawaan. Server memeriksa ulang kelengkapannya
                                                     (ReviewDecision::verdictSah) — layar bisa dilewati,
                                                     server tidak. --}}
                                                @if ($pakaiVerdict ?? false)
                                                    <div class="btn-group btn-group-sm mt-2 pp-verdict" role="group" aria-label="Penilaian pengendalian {{ $li + 1 }}.{{ $bi + 1 }}.{{ $pi + 1 }}">
                                                        <input type="radio" class="btn-check" name="verdicts[{{ $refP }}]" value="sesuai"
                                                               id="v-{{ $refP }}-ok" autocomplete="off" required
                                                               data-verdict="sesuai" data-bahaya="{{ $refB }}">
                                                        <label class="btn btn-outline-success" for="v-{{ $refP }}-ok"><i class="bi bi-check-lg"></i> Sesuai</label>

                                                        <input type="radio" class="btn-check" name="verdicts[{{ $refP }}]" value="perlu_revisi"
                                                               id="v-{{ $refP }}-no" autocomplete="off"
                                                               data-verdict="perlu_revisi" data-bahaya="{{ $refB }}">
                                                        <label class="btn btn-outline-danger" for="v-{{ $refP }}-no"><i class="bi bi-x-lg"></i> Perlu Revisi</label>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="col-md-5">
                                                <textarea name="annotations[{{ $section['key'] }}][{{ $refP }}]" data-annot="{{ $section['key'] }}-{{ $refP }}" class="form-control form-control-sm" rows="2" placeholder="Catatan untuk pengendalian ini (opsional)"></textarea>
                                                <input type="hidden" name="annotations_ai[{{ $section['key'] }}][{{ $refP }}]" value="0" data-ai="{{ $section['key'] }}-{{ $refP }}">
                                            </div>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        @empty
                            <div class="text-muted small">(Belum ada analisa bahaya)</div>
                        @endforelse
                    @else
                    {{-- Kolom bertipe `rich_text` (Deskripsi Aktivitas) menyimpan HTML.
                         Kalau ia ikut di-escape seperti kolom lain, peninjau membaca
                         "<p><strong>…" alih-alih kalimatnya, dan foto yang disisipkan
                         penyusun tak terlihat sama sekali — persis bagian yang paling
                         perlu ditinjau.

                         DISARING LAGI di sini meski sudah disaring saat simpan. Bukan
                         mubazir: inilah SATU-SATUNYA layar yang mencetak isi
                         document_contents sebagai HTML kepada orang yang wewenangnya
                         LEBIH TINGGI daripada penulisnya. Baris yang sudah telanjur
                         tersimpan sebelum sanitasi ada, dan jalur tulis apa pun yang
                         kelak lupa membersihkan, berhenti di sini. --}}
                    @php $richKeys = array_column(array_filter($section['group_fields'] ?? $section['fields'] ?? [], fn ($f) => ($f['type'] ?? '') === 'rich_text'), 'key'); @endphp
                    @forelse ($val as $i => $item)
                        <div class="row g-2 mb-3 pb-2 border-bottom">
                            <div class="col-md-7">
                                <div class="small text-muted">Item {{ $i + 1 }}</div>
                                @if (is_array($item))
                                    @foreach ($item as $k => $v)
                                        @if (in_array($k, $richKeys, true))
                                            @if (filled($v))<div><span class="text-muted small">{{ $k }}:</span><div class="pp-rt-isi">{!! \App\Services\RichText\PembersihHtml::bersihkan($v) !!}</div></div>@endif
                                        @elseif($v && $k !== 'isi' || (is_string($v) && !str_starts_with($v,'lampiran/')))<div><span class="text-muted small">{{ $k }}:</span> {{ is_string($v) ? $v : '' }}</div>@endif
                                    @endforeach
                                @else
                                    <div>{{ $item }}</div>
                                @endif
                                @foreach ($prior->where('item_ref', (string) $i) as $a)
                                    <div class="small text-danger mt-1"><i class="bi bi-chat-left-text"></i> Catatan sebelumnya: {{ $a->comment }}</div>
                                @endforeach
                            </div>
                            <div class="col-md-5">
                                <textarea name="annotations[{{ $section['key'] }}][{{ $i }}]" data-annot="{{ $section['key'] }}-{{ $i }}" class="form-control form-control-sm" rows="2" placeholder="Catatan peninjau untuk item ini (opsional)"></textarea>
                                <input type="hidden" name="annotations_ai[{{ $section['key'] }}][{{ $i }}]" value="0" data-ai="{{ $section['key'] }}-{{ $i }}">
                                <div class="small text-info mt-1 d-none" data-ai-badge="{{ $section['key'] }}-{{ $i }}"><i class="bi bi-robot"></i> Diadopsi dari saran AI</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted small">(Kosong)</div>
                    @endforelse
                    @endif
                </div>
            </div>
        @endforeach

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <label class="form-label small fw-semibold">Ringkasan / Catatan Umum (opsional)</label>
                <textarea name="summary" class="form-control mb-3" rows="2" placeholder="Ringkasan hasil tinjauan..."></textarea>

                @if ($pakaiVerdict ?? false)
                    {{-- JSA: keputusan DITURUNKAN dari tanda, bukan dipilih.
                         Satu ✗ saja berarti dokumen dikembalikan — jadi dua tombol
                         "Loloskan"/"Kembalikan" hanya akan bertentangan dengan
                         tanda yang baru saja dipasang peninjau. Yang ditampilkan
                         di sini adalah AKIBATNYA, dihitung ulang tiap kali sebuah
                         tanda berubah. --}}
                    {{-- `.window` WAJIB: radionya berada di kartu-kartu di ATAS blok
                         ini, jadi event-nya tak pernah melewati elemen ini. `change`
                         menggelembung sampai window, di situlah ia ditangkap. --}}
                    <div x-data="tinjauJsa()" x-init="hitung()" @change.window="hitung()">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="small">
                                <span :class="dinilai < total ? 'text-warning' : 'text-success'">
                                    <i class="bi" :class="dinilai < total ? 'bi-hourglass-split' : 'bi-check-circle'"></i>
                                    <span x-text="`${dinilai} dari ${total} pengendalian dinilai`"></span>
                                </span>
                                <template x-if="revisi > 0">
                                    <span class="text-danger ms-2"><i class="bi bi-x-octagon"></i> <span x-text="`${revisi} perlu revisi`"></span></span>
                                </template>
                            </div>
                            <button type="submit" class="btn" :class="revisi > 0 ? 'btn-warning' : 'btn-success'" :disabled="dinilai < total"
                                    :data-confirm="revisi > 0
                                        ? `${revisi} pengendalian ditandai Perlu Revisi. Dokumen akan dikembalikan ke pembuat.`
                                        : 'Semua pengendalian sesuai. Loloskan dokumen ke tahap persetujuan?'"
                                    data-confirm-title="Kirim Hasil Tinjauan?" data-confirm-ok="Ya, kirim">
                                <i class="bi bi-send"></i> Kirim Hasil Tinjauan
                            </button>
                        </div>
                        <div class="form-text" x-show="dinilai < total" x-cloak>
                            Tombol aktif setelah semua tindakan pengendalian ditandai.
                        </div>
                    </div>
                @elseif ($tanpaKeputusan ?? false)
                    {{-- Masukan sejawat (PLAN-AKSES-v8 Fase 2): layar yang sama,
                         TANPA keputusan. Pemberinya bukan peninjau — ia tak
                         boleh meloloskan maupun mengembalikan dokumen, jadi
                         yang tersisa hanya Batal & Kirim. --}}
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ $backUrl }}" class="btn btn-light">Batal</a>
                        <button type="submit" class="btn btn-primary"
                                data-confirm="Kirim masukan Anda kepada pembuat dokumen? Status dokumen tidak berubah."
                                data-confirm-title="Kirim Masukan?" data-confirm-ok="Ya, kirim"><i class="bi bi-send"></i> Kirim</button>
                    </div>
                @else
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="submit" name="decision" value="reject" class="btn btn-warning"
                                data-confirm="{{ $konfirmasiTolak ?? 'Kembalikan dokumen ke pembuat untuk revisi?' }}"
                                data-confirm-title="Kembalikan untuk Revisi?" data-confirm-icon="warning" data-confirm-ok="Ya, kembalikan"><i class="bi bi-arrow-counterclockwise"></i> {{ $labelTolak ?? 'Kembalikan untuk Revisi' }}</button>
                        <button type="submit" name="decision" value="approve" class="btn btn-success"
                                data-confirm="{{ $konfirmasiLolos ?? 'Loloskan dokumen ke tahap persetujuan?' }}"
                                data-confirm-title="Loloskan Dokumen?" data-confirm-ok="Ya, loloskan"><i class="bi bi-check-lg"></i> {{ $labelLolos ?? 'Loloskan' }}</button>
                    </div>
                @endif
            </div>
        </div>
    </form>

    {{-- Foto lampiran + komentar (v3.1 §6.2) --}}
    @if ($imageAttachments->isNotEmpty())
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white fw-bold"><i class="bi bi-images"></i> Foto Lampiran &amp; Komentar</div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach ($imageAttachments as $att)
                        <div class="col-md-6">
                            <div class="border rounded p-2 h-100">
                                <img src="{{ asset('storage/'.$att->path) }}" class="img-fluid rounded mb-2" style="max-height:220px" alt="{{ $att->original_name }}">
                                <div class="small mb-2">
                                    @forelse ($att->comments as $c)
                                        <div class="border-start border-3 border-info ps-2 mb-1"><strong>{{ $c->user->name ?? '—' }}</strong>: {{ $c->comment }} <span class="text-muted">· {{ $c->created_at->diffForHumans() }}</span></div>
                                    @empty
                                        <span class="text-muted">Belum ada komentar.</span>
                                    @endforelse
                                </div>
                                <form method="POST" action="{{ route('attachments.comment', $att) }}" class="input-group input-group-sm">
                                    @csrf
                                    <input type="text" name="comment" class="form-control" placeholder="Komentari foto ini..." required maxlength="1000">
                                    <button class="btn btn-outline-primary"><i class="bi bi-send"></i></button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Di LUAR form tinjauan: radio papan ber-`required`, dan bila modalnya
         tersarang di sana, form utama tak akan pernah bisa dikirim. --}}
    @if (($alihKandidat ?? null) && $alihKandidat->isNotEmpty())
        @include('review._modal-alihkan')
    @elseif (request('alih'))
        {{-- Datang dari tombol Alihkan di antrian, tapi ternyata tak ada peninjau
             lain yang bisa menerima. Tombol di sana sengaja hanya memeriksa syarat
             murah, jadi sebabnya harus dijelaskan DI SINI — kalau tidak, tombolnya
             tampak rusak: diklik, halaman berganti, tak terjadi apa-apa. --}}
        <div class="alert alert-warning small"><i class="bi bi-info-circle"></i>
            Pengalihan tidak tersedia untuk dokumen ini — tidak ada peninjau lain yang berwenang dan sedang bisa menerimanya.
        </div>
    @endif
@endsection

@push('styles')<style>
    [x-cloak]{display:none!important}

    /*
     | Tombol Sesuai/Perlu Revisi harus berubah warna SEKETIKA saat diklik.
     |
     | Tanpa aturan di bawah, warnanya baru muncul setelah kursor menjauh —
     | sehingga orang menekan berulang kali padahal pilihannya sudah masuk.
     | Penyebabnya BUKAN Bootstrap, melainkan Soft UI:
     |
     |     .btn-outline-success:hover:not(.active) { background: transparent; opacity: .75 }
     |
     | Spesifisitasnya sama dengan `.btn-check:checked + .btn` tapi letaknya
     | LEBIH BELAKANG, jadi ia menang; dan `.active` tak pernah terpasang karena
     | `btn-check` memang bekerja lewat `:checked`, bukan lewat kelas.
     |
     | Diperbaiki dengan menaikkan spesifisitas (lima kelas), DIBATASI pada
     | `.pp-verdict` supaya tombol lain di aplikasi tak ikut berubah perilaku.
     | Nilainya diambil dari variabel Bootstrap sendiri, bukan warna tetap, agar
     | tetap benar di tema gelap.
     */
    .pp-verdict .btn-check:checked + .btn,
    .pp-verdict .btn-check:checked + .btn:hover {
        color: #fff;
        opacity: 1;
        font-weight: 600;
    }

    /*
     | Warna terpilih ditulis TEGAS, tidak mewarisi `--bs-btn-active-*` bawaan.
     |
     | Palet Soft UI memberi outline-success dan outline-danger dua hijau/merah
     | yang terangnya hampir sama (#22c55e vs #ef4444), dan pada tombol sekecil
     | ini keduanya sulit dibedakan sekilas — padahal justru inilah keputusan
     | yang harus terbaca dari jauh, sering di layar silau.
     |
     | Jadi keduanya dibedakan di TIGA sumbu sekaligus, bukan hanya rona:
     |   • Rona     — hijau tua vs merah PPA
     |   • Terang   — Sesuai lebih gelap & padat, Perlu Revisi lebih menyala
     |   • Bentuk   — Perlu Revisi mendapat cincin merah muda di luar tombol,
     |                sehingga tetap terlihat oleh mata yang sulit membedakan
     |                merah-hijau (kebutaan warna merah-hijau adalah yang paling
     |                umum, dan ini formulir keselamatan).
     */
    .pp-verdict .btn-check[data-verdict="sesuai"]:checked + .btn,
    .pp-verdict .btn-check[data-verdict="sesuai"]:checked + .btn:hover {
        background-color: #12805a;
        border-color: #12805a;
    }
    .pp-verdict .btn-check[data-verdict="perlu_revisi"]:checked + .btn,
    .pp-verdict .btn-check[data-verdict="perlu_revisi"]:checked + .btn:hover {
        background-color: #e0202a;
        border-color: #e0202a;
        box-shadow: 0 0 0 .18rem rgba(224, 32, 42, .28);
    }

    /* Baris yang bertanda ✗ diberi penanda di tepi kiri, supaya saat menggulir
       JSA panjang terlihat DI MANA temuannya tanpa membaca tiap tombol.
       `:has()` tak dipakai — peramban PC lapangan belum tentu mendukungnya;
       kelasnya dipasang skrip di bawah. */
    .pp-baris-revisi { border-left: 3px solid #e0202a; border-radius: .25rem; }
</style>@endpush
@push('scripts')
<script>
    /*
     | Penanda borongan satu bahaya: mengisi seluruh radio di bawahnya sekaligus.
     |
     | Satu listener terdelegasi untuk seluruh halaman, bukan satu per tombol —
     | sebuah JSA panjang bisa punya puluhan bahaya, dan memasang puluhan
     | listener hanya membebani tanpa manfaat.
     |
     | Sesudah radionya diisi, `change` dilepas ke window supaya penghitung
     | "n dari m dinilai" ikut memperbarui diri. Menyetel `.checked` lewat skrip
     | TIDAK memicu event apa pun sendiri — tanpa baris itu tombol Kirim akan
     | tetap mati padahal semuanya sudah tertandai.
     */
    document.addEventListener('click', function (e) {
        const tombol = e.target.closest('[data-borongan]');
        if (!tombol) return;

        document.querySelectorAll(
            `input[data-bahaya="${tombol.dataset.borongan}"][data-verdict="${tombol.dataset.nilai}"]`
        ).forEach(radio => { radio.checked = true; });

        window.dispatchEvent(new Event('change'));
    });

    /*
     | Penanda tepi kiri pada baris yang ditandai Perlu Revisi.
     |
     | Pada JSA panjang, tombol-tombolnya terlalu kecil untuk memberitahu DI MANA
     | temuannya saat menggulir. Batang merah di tepi baris menjawab itu tanpa
     | menambah satu kata pun ke layar.
     |
     | Dijalankan ulang pada tiap `change` — termasuk yang dilepas tombol
     | borongan di atas, sehingga penandaan sekaligus ikut terlihat.
     */
    function ppTandaiBarisRevisi() {
        document.querySelectorAll('input[data-verdict="perlu_revisi"]').forEach(radio => {
            radio.closest('.row')?.classList.toggle('pp-baris-revisi', radio.checked);
        });
    }
    window.addEventListener('change', ppTandaiBarisRevisi);
    document.addEventListener('DOMContentLoaded', ppTandaiBarisRevisi);

    document.addEventListener('alpine:init', () => {
        /*
         | Penghitung tanda JSA. Sengaja MEMBACA DOM, bukan menyimpan salinan
         | keadaan sendiri: radio-nya sudah menjadi kebenaran (ia yang terkirim
         | ke server), dan salinan kedua hanya menciptakan peluang keduanya
         | berbeda. Hitung ulang dipicu satu listener `@change` di pembungkus —
         | tak ada pengikatan per-item.
         */
        Alpine.data('tinjauJsa', () => ({
            total: 0, dinilai: 0, revisi: 0,
            hitung() {
                const semua = document.querySelectorAll('input[data-verdict][value="sesuai"]');
                this.total = semua.length;
                this.dinilai = document.querySelectorAll('input[data-verdict]:checked').length;
                this.revisi = document.querySelectorAll('input[data-verdict][value="perlu_revisi"]:checked').length;
            },
        }));

        Alpine.data('aiReview', (url, token) => ({
            loading: false, analyzed: false, summary: '', findings: [], error: '',
            detik: 0, jam: null,
            // Hentikan penghitung di SATU tempat: tiga cabang selesai (sukses,
            // AI mati, galat) semuanya harus mematikan interval, dan yang
            // terlupa akan terus menghitung diam-diam di latar.
            selesai() { this.loading = false; clearInterval(this.jam); },
            analyze() {
                this.loading = true; this.error = ''; this.detik = 0;
                this.jam = setInterval(() => this.detik++, 1000);
                fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(d => {
                        this.selesai(); this.analyzed = true;
                        this.summary = d.summary || '';
                        this.findings = d.findings || [];
                        if (d.enabled === false) this.error = d.summary;
                    })
                    .catch(() => { this.selesai(); this.error = 'Gagal memanggil AI. Lanjutkan tinjauan manual.'; });
            },
            // "L0-B1-P2" → " › Langkah 1 › Bahaya 2 › Kendali 3". Penandanya
            // berguna bagi mesin, tak terbaca oleh peninjau; nomornya digeser ke
            // basis 1 supaya cocok dengan yang tercetak di formulir.
            labelRef(ref) {
                if (!ref) return '';
                const m = String(ref).match(/^L(\d+)(?:-B(\d+))?(?:-P(\d+))?$/);
                if (!m) return ` › item ${Number(ref) + 1}`;
                const n = ['Langkah', 'Bahaya', 'Kendali'];
                return m.slice(1).reduce((s, v, i) => v === undefined ? s : `${s} › ${n[i]} ${Number(v) + 1}`, '');
            },
            // Kotak tujuan sebuah temuan. `item_ref` menunjuk baris tertentu
            // (JSA: L0 / L0-B1 / L0-B1-P2; section lain: nomor item). Ada dua
            // jalan mundur, dan keduanya dipakai sungguhan: AI bisa memberi
            // penanda berbentuk sah yang barisnya tak ada, dan bisa pula sengaja
            // mengosongkannya untuk temuan setingkat section.
            kotak(f) {
                for (const key of [f.item_ref ? `${f.section_key}-${f.item_ref}` : null, `${f.section_key}-0`]) {
                    const el = key && document.querySelector(`[data-annot="${key}"]`);
                    if (el) return { el, key };
                }
                // Section yang item pertamanya bukan bernomor 0 (analisa JSA
                // dimulai dari "L0", bukan "0") tetap punya kotak — ambil yang
                // pertama, daripada temuan jatuh ke Ringkasan dan hilang konteks.
                const el = document.querySelector(`[data-annot^="${f.section_key}-"]`);
                return el ? { el, key: el.getAttribute('data-annot') } : null;
            },
            adopt(i) {
                const f = this.findings[i];
                const tujuan = this.kotak(f);
                const el = tujuan?.el, key = tujuan?.key;
                if (el) {
                    el.value = (el.value ? el.value + '\n' : '') + f.suggestion;
                    const m = document.querySelector(`[data-ai="${key}"]`); if (m) m.value = '1';
                    const b = document.querySelector(`[data-ai-badge="${key}"]`); if (b) b.classList.remove('d-none');
                    // Analisa JSA panjang: kotak tujuannya hampir selalu di luar
                    // layar, sehingga tanpa ini adopsi terasa seperti temuan yang
                    // lenyap begitu saja.
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    el.classList.add('border-info');
                    setTimeout(() => el.classList.remove('border-info'), 2000);
                } else {
                    const s = document.querySelector('[name="summary"]'); if (s) s.value = (s.value ? s.value + '\n' : '') + f.suggestion;
                }
                this.findings.splice(i, 1);
            },
            reject(i) { this.findings.splice(i, 1); },
            sevClass(s) { return { info: 'badge-soft-info', minor: 'badge-soft-secondary', major: 'badge-soft-warning', critical: 'badge-soft-danger' }[s] || 'badge-soft-secondary'; },
        }));
    });
</script>
@endpush
