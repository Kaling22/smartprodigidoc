{{-- Langkah "Log Revisi" (hanya draft revisi Tipe B) — mengisi lembar CATATAN
     REVISI (docs/Lembar Revisi.docx). Referensi form: docs/lembar revisi/*.png.
     Baris salinan revisi lama membawa no_rev-nya; baris baru diberi no_revisi
     dokumen ini di server (persistRevisionLog). --}}
@php
    $rows = collect(is_array($value ?? null) ? $value : [])->values()->all();
    // Nomor yang akan BERLAKU saat dokumen dikirim (butir 7a): selama draft
    // revisi disusun, kolom edisi/no_revisi masih memikul angka versi terbit.
    [$edisiKirim, $revisiKirim] = $document->revisiSaatKirim();
@endphp

<div x-data="revisionLog(@js($rows), @js(route('documents.revisi.usulan', $document)), @js($revisiKirim))"
     @isi-log-revisi.window="isiOtomatis()">
    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Edisi</label>
            <input type="number" min="1" name="edisi" value="{{ (int) ($document->edisi ?: 1) }}" class="form-control">
            <div class="form-text">Otomatis (roll-over) — boleh diubah manual.</div>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Revisi</label>
            <input type="number" min="0" name="no_revisi" value="{{ (int) $document->no_revisi }}" class="form-control">
            <div class="form-text">Otomatis — boleh diubah manual.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Judul Dokumen</label>
            <input type="text" class="form-control" value="{{ $document->title }}" readonly disabled>
        </div>
    </div>

    {{-- Nomor revisi baru naik saat dokumen DIKIRIM, jadi kedua kolom di atas
         masih memperlihatkan angka versi terbit. Tanpa keterangan ini, angkanya
         terbaca seperti kesalahan. --}}
    @if ($revisiKirim !== (int) $document->no_revisi || $edisiKirim !== (int) ($document->edisi ?: 1))
        <div class="alert alert-info py-2 small">
            <i class="bi bi-info-circle"></i>
            Setelah dikirim, dokumen ini menjadi <strong>Edisi {{ $edisiKirim }} Revisi {{ $revisiKirim }}</strong>.
            Angka di atas masih angka versi yang berlaku sekarang.
        </div>
    @endif

    <label class="form-label fw-semibold">Catatan Perubahan (Revisi)</label>
    {{-- Daftar kolomnya dibuang (PLAN C §C5): tiap kolom sudah berlabel tepat di
         bawah kalimat ini. --}}
    <div class="form-text mb-2">Satu baris = satu catatan pada lembar CATATAN REVISI; baris revisi terdahulu ikut tercetak. Tombol <strong>Revisi</strong> mengisi No. Rev, Tanggal, dan Hal.; catatannya Anda ketik sendiri.</div>

    <template x-if="pesan">
        <div class="alert alert-warning py-2 small" x-text="pesan"></div>
    </template>

    <template x-for="(row, i) in rows" :key="i">
        <div class="border rounded p-3 mb-2 bg-body-tertiary">
            <div class="row g-2 align-items-start">
                <div class="col-md-2">
                    <label class="form-label small mb-1">No. Rev</label>
                    <input type="number" min="0" class="form-control" :name="`sections[catatan_revisi][${i}][no_rev]`" x-model="row.no_rev" placeholder="{{ $revisiKirim }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Tanggal Rev</label>
                    <input type="date" class="form-control" :name="`sections[catatan_revisi][${i}][tanggal]`" x-model="row.tanggal">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Hal.</label>
                    <input type="text" class="form-control" placeholder="mis. 1-4" :name="`sections[catatan_revisi][${i}][halaman]`" x-model="row.halaman">
                </div>
                <div class="col-md-5">
                    <label class="form-label small mb-1">Catatan Revisi</label>
                    {{-- Nama bab yang berubah dipakai sebagai placeholder, bukan
                         diisikan: yang otomatis hanya nomor/tanggal/halaman. --}}
                    <textarea class="form-control" rows="2" :placeholder="row.bab ? `Apa yang berubah pada ${row.bab}?` : 'mis. Perubahan detail aktivitas...'" :name="`sections[catatan_revisi][${i}][catatan]`" x-model="row.catatan"></textarea>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger mt-2" @click="remove(i)"><i class="bi bi-trash"></i> Hapus Poin Catatan</button>
        </div>
    </template>

    <button type="button" class="btn btn-sm btn-outline-primary w-100" @click="add()"><i class="bi bi-plus-lg"></i> Tambah Catatan Per Halaman</button>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('revisionLog', (initial = [], urlUsulan = '', noRev = 0) => ({
            rows: initial.length ? initial : [{ no_rev: null, tanggal: '', halaman: '', catatan: '' }],
            urlUsulan, noRev, memuat: false, pesan: '',
            add() { this.rows.push({ no_rev: null, tanggal: '', halaman: '', catatan: '' }); },
            remove(i) { this.rows.splice(i, 1); if (!this.rows.length) this.add(); },
            /*
             | Tombol "Revisi" (di samping Preview): server membandingkan isi draft
             | dengan versi yang direvisinya, lalu mengukur halaman tiap bab yang
             | berubah dari PDF yang benar-benar dirender.
             |
             | Baris yang CATATANNYA masih kosong dibuang lebih dulu — itu membuat
             | tombolnya boleh ditekan berkali-kali tanpa menumpuk duplikat,
             | sekaligus menjaga baris revisi terdahulu (catatannya sudah terisi)
             | tetap utuh.
             */
            isiOtomatis() {
                if (this.memuat) return;
                this.memuat = true; this.pesan = '';
                fetch(this.urlUsulan, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.ok ? r.json() : Promise.reject(r))
                    .then(j => {
                        if (!j.baris.length) {
                            this.pesan = 'Belum ada bab yang berbeda dari versi yang berlaku — isi dokumen dulu, lalu tekan Revisi lagi.';
                            return;
                        }
                        const tetap = this.rows.filter(r => String(r.catatan || '').trim() !== '');
                        this.rows = tetap.concat(j.baris.map(b => ({
                            no_rev: j.no_rev, tanggal: j.tanggal, halaman: b.halaman, catatan: '', bab: b.bab,
                        })));
                    })
                    .catch(() => { this.pesan = 'Gagal menyusun catatan revisi. Coba lagi.'; })
                    .finally(() => { this.memuat = false; });
            },
        }));
    });
</script>
@endpush
