<?php

namespace Database\Seeders;

use App\Models\PtnProgram;
use Illuminate\Database\Seeder;

class PtnProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            ['Universitas Indonesia (UI)', 'UI', 'Pendidikan Dokter', 'S1', 'SAINTEK', 742.00, 75, 2950],
            ['Universitas Indonesia (UI)', 'UI', 'Ilmu Komputer', 'S1', 'SAINTEK', 720.00, 90, 2400],
            ['Universitas Indonesia (UI)', 'UI', 'Manajemen', 'S1', 'SOSHUM', 698.00, 120, 3100],
            ['Universitas Indonesia (UI)', 'UI', 'Ilmu Hukum', 'S1', 'SOSHUM', 690.00, 165, 3400],
            ['Institut Teknologi Bandung (ITB)', 'ITB', 'STEI - Komputasi', 'S1', 'SAINTEK', 735.00, 140, 3800],
            ['Institut Teknologi Bandung (ITB)', 'ITB', 'FTSL - Teknik Sipil & Lingkungan', 'S1', 'SAINTEK', 682.00, 180, 2600],
            ['Institut Teknologi Bandung (ITB)', 'ITB', 'Sekolah Bisnis dan Manajemen (SBM)', 'S1', 'SOSHUM', 705.00, 110, 2900],
            ['Universitas Gadjah Mada (UGM)', 'UGM', 'Kedokteran', 'S1', 'SAINTEK', 738.00, 80, 3200],
            ['Universitas Gadjah Mada (UGM)', 'UGM', 'Teknologi Informasi', 'S1', 'SAINTEK', 708.00, 70, 2100],
            ['Universitas Gadjah Mada (UGM)', 'UGM', 'Psikologi', 'S1', 'SOSHUM', 688.00, 100, 2800],
            ['Universitas Gadjah Mada (UGM)', 'UGM', 'Ilmu Komunikasi', 'S1', 'SOSHUM', 684.00, 65, 2500],
            ['Institut Teknologi Sepuluh Nopember (ITS)', 'ITS', 'Teknik Informatika', 'S1', 'SAINTEK', 695.00, 130, 3100],
            ['Institut Teknologi Sepuluh Nopember (ITS)', 'ITS', 'Sistem Informasi', 'S1', 'SAINTEK', 668.00, 95, 1950],
            ['Universitas Airlangga (UNAIR)', 'UNAIR', 'Kedokteran', 'S1', 'SAINTEK', 725.00, 95, 3050],
            ['Universitas Airlangga (UNAIR)', 'UNAIR', 'Akuntansi', 'S1', 'SOSHUM', 658.00, 110, 1850],
            ['Universitas Padjadjaran (UNPAD)', 'UNPAD', 'Ilmu Komunikasi', 'S1', 'SOSHUM', 665.00, 90, 2600],
            ['Universitas Padjadjaran (UNPAD)', 'UNPAD', 'Farmasi', 'S1', 'SAINTEK', 662.00, 85, 1900],
            ['Universitas Diponegoro (UNDIP)', 'UNDIP', 'Teknik Sipil', 'S1', 'SAINTEK', 652.00, 120, 2100],
            ['Universitas Diponegoro (UNDIP)', 'UNDIP', 'Hukum', 'S1', 'SOSHUM', 645.00, 210, 3300],
            ['IPB University', 'IPB', 'Ilmu Komputer', 'S1', 'SAINTEK', 672.00, 85, 1950],
            ['IPB University', 'IPB', 'Bisnis', 'S1', 'SOSHUM', 642.00, 95, 2200],
            ['Universitas Brawijaya (UB)', 'UB', 'Teknik Informatika', 'S1', 'SAINTEK', 655.00, 115, 2900],
            ['Universitas Sebelas Maret (UNS)', 'UNS', 'Pendidikan Dokter', 'S1', 'SAINTEK', 695.00, 90, 2750],
            ['Universitas Negeri Jakarta (UNJ)', 'UNJ', 'Manajemen', 'S1', 'SOSHUM', 618.00, 90, 2400],
        ];

        foreach ($programs as [$uni, $code, $prog, $degree, $cat, $score, $quota, $applicants]) {
            PtnProgram::updateOrCreate(
                ['university_name' => $uni, 'program_name' => $prog, 'degree' => $degree],
                [
                    'university_code' => $code,
                    'category' => $cat,
                    'passing_score' => $score,
                    'quota' => $quota,
                    'applicants' => $applicants,
                ]
            );
        }
    }
}
