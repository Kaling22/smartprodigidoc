{{-- Tombol Rollback (PLAN-REVISI-v6 Fase G) — kembalikan ISI dokumen ke versi
     $v lewat draft revisi biasa.

     Tiga syaratnya sama persis dengan penjaga di
     DocumentRevisionController::rollback(); server memeriksanya ulang, jadi
     baris ini hanya menyembunyikan tombol yang pasti ditolak:
     (a) grupnya masih punya versi Berlaku — itulah yang isinya ditimpa;
     (b) versi ini bukan dokumen yang DIMATIKAN (nomornya sudah dilepas —
         jalannya "Aktifkan", bukan rollback);
     (c) penekannya berhak merevisi dokumen ini (GL: buatannya sendiri). --}}
@if ($adaBerlaku->contains($v->doc_number) && ! $v->nomorDilepas() && $v->pemilikRevisi(auth()->user()))
    <form method="POST" action="{{ route('documents.rollback', $v) }}" class="d-inline"
          data-confirm="Isi Edisi {{ $v->edisi ?? 1 }} Revisi {{ $v->no_revisi ?? 0 }} akan disalin menjadi draft revisi baru. Dokumen yang berlaku sekarang tetap berlaku sampai revisi ini disahkan."
          data-confirm-title="Kembalikan ke Versi Ini?" data-confirm-ok="Ya, buat draft revisi" data-confirm-icon="warning">
        @csrf
        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-counterclockwise"></i> Rollback</button>
    </form>
@endif
