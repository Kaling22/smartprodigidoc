<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        // Kode lama yang sudah diganti. Wajib di-rename LEBIH DULU: updateOrCreate
        // mencocokkan berdasarkan `code`, jadi tanpa langkah ini kode baru dianggap
        // departemen baru dan barisnya jadi KEMBAR (yang lama tertinggal).
        Department::where('code', 'FWA')->update(['code' => 'FAW-SCM']);

        // Tujuh departemen PT PPA site Adaro (PRD §1.4) [TERKUNCI]
        $departments = [
            ['code' => 'SHE', 'name' => 'Safety, Health & Environment', 'alias' => null],
            ['code' => 'PLANT', 'name' => 'Plant', 'alias' => null],
            ['code' => 'HCGA', 'name' => 'Human Capital & General Affairs', 'alias' => null],
            ['code' => 'FAW-SCM', 'name' => 'Finance, Accounting & Warehouse — Supply Chain Management', 'alias' => 'FALOG'],
            ['code' => 'ICTMD', 'name' => 'ICT & Management Development', 'alias' => null],
            ['code' => 'PRODUKSI', 'name' => 'Produksi', 'alias' => null],
            ['code' => 'ENGINEERING', 'name' => 'Engineering', 'alias' => null],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(['code' => $dept['code']], $dept);
        }
    }
}
