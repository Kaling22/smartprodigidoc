{{-- Penanda dokumen LAMA (arsip, butir 0).

     Dua lencana yang sengaja dipisah, karena keduanya menjawab pertanyaan yang
     berbeda: "Arsip" = dokumen ini berupa PDF unggahan, bukan susunan wizard —
     tombol PDF-nya menyajikan berkas asli. "Nomor Lama" = nomornya di luar pola
     {PREFIX}-{JENIS}-{DEPT}-{NN} (site.prefix) dan itu memang diterima apa adanya.

     Dokumen arsip bernomor sesuai pola hanya mendapat lencana pertama; dokumen
     SmartPro bernomor manual yang ganjil hanya mendapat yang kedua. --}}
@if ($doc->isArsip())
    <span class="badge-soft badge-soft-secondary" title="Dokumen lama — PDF diunggah apa adanya"><i class="bi bi-archive"></i> Arsip</span>
@endif
@if ($doc->nomorLuarPola())
    <span class="badge-soft badge-soft-warning" title="Nomor di luar pola {{ \App\Models\Pengaturan::prefix() }}-JENIS-DEPT-NN">Nomor Lama</span>
@endif
