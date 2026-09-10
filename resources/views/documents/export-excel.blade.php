@php
    // Lebar tabel ikut jumlah blok revisi yang dibangkitkan controller
    // (3 kolom identitas + kolom revisi + 2 kolom tanggal).
    $totalKolom = 3 + collect($kolom)->flatten()->count() + 2;
@endphp
<html>
<head>
<meta charset="utf-8">
<style>
  body  { font-family: Arial, sans-serif; font-size: 10pt; }
  table { border-collapse: collapse; width: 100%; }
  th, td { border: 1px solid #000; padding: 4px 8px; vertical-align: middle; }
  .judul    { font-size: 13pt; font-weight: bold; text-align: center; border: none; background: none; }
  .sub-judul { font-size: 10pt; text-align: center; border: none; background: none; }
  th { background-color: #D6DCE4; font-weight: bold; text-align: center; }
  .center { text-align: center; }
  .kosong { border: none; background: none; }
</style>
</head>
<body>
<table>
  {{-- Header judul --}}
  <tr>
    <td colspan="{{ $totalKolom }}" class="judul">DAFTAR INDUK DOKUMEN {{ $jenis }}</td>
  </tr>
  <tr>
    <td colspan="{{ $totalKolom }}" class="sub-judul">PT PUTRA PERKASA ABADI SITE {{ \App\Models\Pengaturan::namaSite() }}</td>
  </tr>
  <tr>
    <td colspan="{{ $totalKolom }}" class="kosong">&nbsp;</td>
  </tr>

  {{-- Header kolom DUA tingkat: baris atas blok Edisi, baris bawah nomor revisi.
       Kolom identitas & tanggal ber-rowspan 2 supaya sejajar keduanya. --}}
  <thead>
    <tr>
      <th rowspan="2" style="width:40px">No</th>
      <th rowspan="2" style="width:180px">Nomor Dokumen</th>
      <th rowspan="2">Judul Dokumen</th>
      @foreach ($kolom as $edisi => $revisi)
        <th colspan="{{ count($revisi) }}">Edisi {{ $edisi }}</th>
      @endforeach
      <th rowspan="2" style="width:110px">Tanggal Efektif</th>
      <th rowspan="2" style="width:130px">Tanggal Terakhir Revisi</th>
    </tr>
    <tr>
      @foreach ($kolom as $revisi)
        @foreach ($revisi as $rev)
          <th style="width:70px">{{ $rev }}</th>
        @endforeach
      @endforeach
    </tr>
  </thead>

  {{-- Data --}}
  <tbody>
    @forelse ($baris as $i => $b)
      <tr>
        <td class="center">{{ $i + 1 }}</td>
        <td>{{ $b['dok']->displayNumber() }}</td>
        <td>{{ $b['dok']->title }}</td>
        @foreach ($kolom as $edisi => $revisi)
          @foreach ($revisi as $rev)
            {{-- Sel yang tak berlaku diberi strip, bukan dikosongkan (permintaan pemilik). --}}
            <td class="center">{{ ($b['peta'][$edisi][$rev] ?? null)?->format('d/m/Y') ?? '-' }}</td>
          @endforeach
        @endforeach
        <td class="center">{{ $b['efektif']?->format('d/m/Y') ?? '-' }}</td>
        <td class="center">{{ $b['dok']->published_at?->format('d/m/Y') ?? '-' }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="{{ $totalKolom }}" class="center">Belum ada dokumen {{ $jenis }} yang berlaku.</td>
      </tr>
    @endforelse
  </tbody>
</table>
</body>
</html>
