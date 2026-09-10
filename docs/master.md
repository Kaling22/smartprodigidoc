# BRIEF MASTER - PERMASALAHAN & RENCANA FITUR LANJUTAN

Berikut adalah kumpulan permasalahan dan rencana fitur yang akan dipecah menjadi beberapa tahap pengerjaan.

## Butir 0: Dokumen Existing (PDF) vs Format SmartPro
Ada dokumen (SOP, IK, SP, JSA) yang sudah jadi lama dalam bentuk PDF, sayangnya karena memiliki format yang berbeda, maka isi PDF tersebut memerlukan waktu untuk dimasukkan secara manual ke format SmartPro. Menurutmu solusinya bagaimana?
a. Tambahkan fitur upload dokumen untuk dokumen yang sudah jadi lama. Isinya ialah: nomor dokumen, judul dokumen, edisi, revisi, tanggal efektif.
   Pertanyaannya: bagaimana jika dokumen tersebut ingin dilakukan revisi? Format SmartPro tidak sama, dan juga dokumennya berbentuk PDF yang tidak bisa diedit.
b. Tambahkan fitur konversi dokumen menggunakan OCR, kemudian isinya dipetakan secara otomatis menggunakan AI ke format SmartPro.
   Pertanyaannya: bagaimana dengan histori revisinya? Apakah konversi isi dari PDF ke format SmartPro akan sesuai penempatannya?
c. Kita membuat format adaptif yang dapat di-custom sesuai dengan format dokumen yang sudah jadi. Format dokumen disimpan di dokumen profile yang memuat beberapa template.
   Pertanyaannya: apakah performanya dan keamanan datanya aman? Apakah dapat tercapai?
d. Lainnya (tolong pikirkan fitur apa dan celah apa dari masing-masing fitur serta potensi dari menggunakan fitur tersebut).

## Butir 1: Grouping Storage & Pembersihan Data
Mari kelompokkan PDF export yang sudah ada pada masing-masing jenis dokumen folder di directory storage, sehingga dokumen SOP akan berada di folder SOP dan berikutnya. Starting from now, untuk dokumen yang sudah ada sekarang dihapus saja sepenuhnya se-datanya.

## Butir 2: Input Dokumen Existing dengan Nomor Manual
Ketika ingin menambahkan dokumen yang sudah lama jadi namun secara manual menggunakan input pengisian, pastinya dokumen yang sudah lama jadi memiliki nomor dokumen yang sudah jadi juga, dan nomor tersebut tetap ingin digunakan juga di SmartPro hingga dokumen berlaku. Bagaimana caranya agar tidak mengganggu logika penomoran kita? Apakah ditambah tombol atau settingan tertentu?

## Butir 3: Menu Informasi (Upload Only, Akses Semua)
Ada menu informasi yang perlu kita tambahkan namun khusus di-upload saja, diakses oleh semua orang, semua departemen, dan semua jabatan. Sub menu informasi tersebut berupa:
- KEBIJAKAN; kolomnya berupa: no kebijakan, judul kebijakan, upload PDF kebijakan, edisi, revisi.
- MEMO External; kolomnya berupa: no memo, judul memo, upload PDF memo.
- MEMO Internal; kolomnya berupa: no memo, judul memo, upload PDF memo.
- INSTRUKSI KTT; kolomnya berupa: no instruksi KTT, judul, upload PDF, edisi, revisi, tanggal efektif.
- POSTER; kolomnya berupa: no poster, judul, dan upload poster.
- MSDS; no MSDS, judul MSDS, upload PDF MSDS.
- BAP; no BAP, judul BAP, upload PDF BAP.
- SERTIFIKAT & SIO; no sertifikat/SIO, judul sertifikat/SIO, upload PDF sertifikat/SIO.
- MOC & MPRP; no MOC/MPRP, judul MOC/MPRP, upload PDF MOC/MPRP.
- IBPR; no IBPR, judul IBPR, revisi, edisi, tanggal efektif, upload PDF.
*(Catatan tambahan: untuk keseluruhan informasi ini, diperlukan nomor dokumen, judul dokumen, tanggal efektif, edisi, dan revisi dari masing-masing dokumen. Perlu diklarifikasi apakah berlaku untuk semua submenu atau mengikuti kolom spesifik di atas).*

## Butir 4: Integrasi Mobile App
Karena web ini nanti akan di-hosting, apakah bisa kita buat dia terhubung ke SmartPro mobile dahulu? Sehingga nanti aplikasi yang berjalan langsung terhubung.

## Butir 5: UI/UX Widget Komentar Lapangan
Untuk tampilan, perbaiki penyusunan widget UI/UX dari komentar lapangan. Banyak ruang kosong di situ, perbaiki agar ruang kosong tersebut tetap enak untuk dilihat. Jangan ubah dimensi card widget-nya. Jika ada banyak komentar, maka buat agar bisa di-scroll. Jika sedikit atau tidak ada, buat agar tetap bagus.

## Butir 6: Format Dokumen Induk Adaptif & Ekspor SH/DH
Mari kita ubah format dokumen induk di mana formatnya seperti di file berikut: `C:\xamppnew\htdocs\smartprorefactor_fresh\docs\template_daftar_induk.xlsx`. Di mana kolomnya adaptif terhadap dokumen yang memiliki revisi terbanyak. Untuk dokumen yang revisinya tidak sebanyak dokumen dengan revisi terbanyak, maka kolomnya diberi strip saja. Kolom tersebut diisi tanggal revisi. Buat agar SH/DH bisa melakukan export no induk dokumen dan yang muncul hanya dokumen di dept itu saja.

## Butir 7: Fitur Revisi Berlaku (Otomatis & Tombol)
Untuk fitur revisi berlaku yang 0 -> 1, apakah bisa dilakukan secara otomatis jika terdeteksi ada ubahan? Apakah bisa tambahkan tombol revisi di samping tombol preview (khusus pembuatan halaman revisi) di mana halaman, nomor, dan tanggal otomatis terisi, untuk isinya tulis secara manual saja?

## Butir 8: Impor Massal via Terminal/Backend
Ketika sub menu informasi sudah jadi, export dokumen induk sudah jadi, upload dokumen sudah jadi, dan fitur revisi sudah jadi, maka akan kita impor dokumen-dokumen PDF yang sudah ada via terminal. Aku memiliki salinan project htdocs yang berisi file PDF dan SQL yang bisa dijadikan sebagai acuan, sehingga tidak perlu bikin manual lagi. Pertanyaannya: apakah kita upload via web entah itu massal atau per 10/20 dsb-nya, atau kita masukkan via backend-nya saja?