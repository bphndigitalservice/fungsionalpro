<?php

namespace Database\Seeders;

use App\Enums\UkomDocumentPack;
use App\Models\UkomDocumentType;
use Illuminate\Database\Seeder;

class UkomDocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->types() as $index => $type) {
            UkomDocumentType::query()->updateOrCreate(
                ['pack' => $type['pack'], 'slug' => $type['slug']],
                [
                    'label' => $type['label'],
                    'is_required' => $type['is_required'],
                    'required_if_claims_new_degree' => $type['required_if_claims_new_degree'] ?? false,
                    'sort' => $index + 1,
                ],
            );
        }
    }

    /**
     * @return list<array{pack: UkomDocumentPack, slug: string, label: string, is_required: bool, required_if_claims_new_degree?: bool}>
     */
    public function types(): array
    {
        return [
            ['pack' => UkomDocumentPack::CalonJf, 'slug' => 'usulan_ppk_kepegawaian', 'label' => 'Surat usulan resmi dari Pejabat Penilai Kepegawaian (Kepala Biro SDM / Sekda / Kepala BKD) ditujukan kepada Kepala BPHN', 'is_required' => true],
            ['pack' => UkomDocumentPack::CalonJf, 'slug' => 'sk_pengangkatan_pns', 'label' => 'Salinan SK Pengangkatan PNS', 'is_required' => true],
            ['pack' => UkomDocumentPack::CalonJf, 'slug' => 'sk_jabatan_terakhir', 'label' => 'Salinan SK Jabatan Terakhir', 'is_required' => true],
            ['pack' => UkomDocumentPack::CalonJf, 'slug' => 'sk_pangkat_terakhir', 'label' => 'Salinan SK Pangkat Terakhir (atau SK CPNS jika belum pernah naik pangkat)', 'is_required' => true],
            ['pack' => UkomDocumentPack::CalonJf, 'slug' => 'ijazah_terakhir', 'label' => 'Salinan Ijazah Pendidikan Terakhir (disertai persetujuan pencantuman gelar BKN jika ada peningkatan pendidikan)', 'is_required' => true],
            ['pack' => UkomDocumentPack::CalonJf, 'slug' => 'bkn_gelar', 'label' => 'Dokumen persetujuan pencantuman gelar BKN', 'is_required' => false, 'required_if_claims_new_degree' => true],
            ['pack' => UkomDocumentPack::CalonJf, 'slug' => 'skp_2_tahun', 'label' => 'Salinan SKP / Dokumen Hasil Evaluasi Kinerja 2 (dua) tahun terakhir', 'is_required' => true],
            ['pack' => UkomDocumentPack::CalonJf, 'slug' => 'sk_tidak_hukuman', 'label' => 'Surat Keterangan pimpinan unit kerja (minimal JPT Pratama/Eselon II) yang menyatakan tidak sedang dihukum disiplin, tidak tugas belajar, dan tidak cuti di luar tanggungan negara', 'is_required' => true],
            ['pack' => UkomDocumentPack::CalonJf, 'slug' => 'sehat_jasmani_rohani', 'label' => 'Surat Keterangan Sehat Jasmani & Rohani dari unit pelayanan kesehatan pemerintah (berlaku maks. 1 bulan)', 'is_required' => true],
            ['pack' => UkomDocumentPack::CalonJf, 'slug' => 'pengalaman_2_tahun', 'label' => 'Surat Keterangan Pengalaman Kerja minimal 2 tahun dari pimpinan unit kerja (minimal JPT Pratama)', 'is_required' => true],
            ['pack' => UkomDocumentPack::CalonJf, 'slug' => 'pendukung_pengalaman', 'label' => 'Dokumen pendukung pengalaman kerja (SK Tim Kerja/Surat Tugas/Surat Perintah)', 'is_required' => true],
            ['pack' => UkomDocumentPack::CalonJf, 'slug' => 'formasi_panrb', 'label' => 'Dokumen persetujuan kebutuhan formasi dari Kementerian PANRB', 'is_required' => true],
            ['pack' => UkomDocumentPack::CalonJf, 'slug' => 'uji_kompetensi_68', 'label' => 'Dokumen hasil Uji Kompetensi Manajerial dan Sosial Kultural (min. 68% JPM)', 'is_required' => true],
            ['pack' => UkomDocumentPack::CalonJf, 'slug' => 'sertifikat_pelatihan', 'label' => 'Sertifikat pelatihan teknis/fungsional terkait', 'is_required' => false],

            ['pack' => UkomDocumentPack::Client, 'slug' => 'usulan_ppk', 'label' => 'Surat usulan resmi dari Pejabat Pembina Kepegawaian ditujukan kepada Kepala BPHN', 'is_required' => true],
            ['pack' => UkomDocumentPack::Client, 'slug' => 'sk_jabatan_terakhir', 'label' => 'Salinan SK Jabatan Terakhir', 'is_required' => true],
            ['pack' => UkomDocumentPack::Client, 'slug' => 'sk_pangkat_terakhir', 'label' => 'Salinan SK Pangkat Terakhir', 'is_required' => true],
            ['pack' => UkomDocumentPack::Client, 'slug' => 'skp_sejak_pengangkatan', 'label' => 'Dokumen SKP dan Hasil Evaluasi Kinerja terhitung sejak pengangkatan/kenaikan pangkat terakhir', 'is_required' => true],
            ['pack' => UkomDocumentPack::Client, 'slug' => 'konversi_ak', 'label' => 'Dokumen Konversi Predikat Kinerja ke Angka Kredit terhitung sejak pengangkatan/kenaikan pangkat terakhir', 'is_required' => true],
            ['pack' => UkomDocumentPack::Client, 'slug' => 'akumulasi_ak', 'label' => 'Dokumen Akumulasi Angka Kredit Pejabat Fungsional', 'is_required' => true],
            ['pack' => UkomDocumentPack::Client, 'slug' => 'pak_terakhir', 'label' => 'Penetapan Angka Kredit (PAK) Terakhir yang merekomendasikan Kenaikan Jenjang Jabatan', 'is_required' => true],
            ['pack' => UkomDocumentPack::Client, 'slug' => 'riwayat_pak', 'label' => 'Riwayat Penetapan Angka Kredit (PAK) yang telah diterbitkan sebelumnya sejak pengangkatan/kenaikan pangkat terakhir', 'is_required' => true],
            ['pack' => UkomDocumentPack::Client, 'slug' => 'bkn_gelar', 'label' => 'Dokumen persetujuan/pencantuman gelar BKN', 'is_required' => false, 'required_if_claims_new_degree' => true],
            ['pack' => UkomDocumentPack::Client, 'slug' => 'formasi_jf', 'label' => 'Dokumen persetujuan formasi Jabatan Fungsional [target] dari Kementerian PANRB', 'is_required' => true],
            ['pack' => UkomDocumentPack::Client, 'slug' => 'uji_kompetensi_68', 'label' => 'Dokumen hasil Uji Kompetensi Manajerial dan Sosial Kultural (min. 68% JPM)', 'is_required' => true],
            ['pack' => UkomDocumentPack::Client, 'slug' => 'sertifikat_pelatihan', 'label' => 'Sertifikat pelatihan fungsional/teknis terkait', 'is_required' => false],
        ];
    }
}
