<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Pengurus (Admin) & Warga
        $admin = User::updateOrCreate([
            'email' => 'admin@take.local',
        ], [
            'name' => 'Pak Hendra (Ketua RW)',
            'password' => Hash::make('password123'),
            'phone' => '081234567801',
            'address' => 'Blok A No. 01, RT 02 / RW 05',
            'role' => 'admin',
        ]);

        $warga1 = User::updateOrCreate([
            'email' => 'sari@take.local',
        ], [
            'name' => 'Bu Sari',
            'password' => Hash::make('password123'),
            'phone' => '081234567802',
            'address' => 'Blok B No. 14, RT 02 / RW 05',
            'role' => 'warga',
        ]);

        $warga2 = User::updateOrCreate([
            'email' => 'raka@take.local',
        ], [
            'name' => 'Raka Pratama (Karang Taruna)',
            'password' => Hash::make('password123'),
            'phone' => '081234567803',
            'address' => 'Blok C No. 08, RT 03 / RW 05',
            'role' => 'warga',
        ]);

        $warga3 = User::updateOrCreate([
            'email' => 'dedi@take.local',
        ], [
            'name' => 'Kang Dedi',
            'password' => Hash::make('password123'),
            'phone' => '081234567804',
            'address' => 'Blok A No. 19, RT 01 / RW 05',
            'role' => 'warga',
        ]);

        // 2. Kategori Inventaris Warga
        $categoriesData = [
            ['name' => 'Tenda & Kursi', 'icon' => 'tent', 'description' => 'Perlengkapan hajatan, rapat warga, dan acara luar ruang.'],
            ['name' => 'Audio & Multimedia', 'icon' => 'speaker', 'description' => 'Sound system portable, mic wireless, dan proyektor balai warga.'],
            ['name' => 'Alat Kerja Bakti', 'icon' => 'shovel', 'description' => 'Peralatan kebersihan lingkungan, selang air, dan potong rumput.'],
            ['name' => 'Peralatan Dapur Bersama', 'icon' => 'utensils', 'description' => 'Kompor gas besar, dandang, dan termos untuk kegiatan warga.'],
        ];

        $categories = [];
        foreach ($categoriesData as $cat) {
            $categories[$cat['name']] = Category::updateOrCreate([
                'slug' => Str::slug($cat['name']),
            ], [
                'name' => $cat['name'],
                'icon' => $cat['icon'],
                'description' => $cat['description'],
            ]);
        }

        // 3. Daftar Aset Warga
        $assetsData = [
            ['Tenda & Kursi', 'TAKE-AST-001', 'Tenda Kerucut 3x3m', 'Tenda lipat rangka besi kuat untuk bazar, posko, atau hajatan kecil.', 4, 2, 'baik'],
            ['Tenda & Kursi', 'TAKE-AST-002', 'Kursi Lipat Plastik', 'Kursi lipat serbaguna untuk rapat RT, pengajian, dan pertemuan warga.', 60, 35, 'baik'],
            ['Tenda & Kursi', 'TAKE-AST-003', 'Meja Lipat Panjang', 'Meja konsumsi dan administrasi kegiatan warga.', 8, 8, 'baik'],
            ['Audio & Multimedia', 'TAKE-AST-004', 'Portable Speaker + 2 Mic', 'Speaker aktif beroda dengan 2 mikrofon wireless untuk pengumuman & acara.', 2, 1, 'baik'],
            ['Audio & Multimedia', 'TAKE-AST-005', 'Proyektor HDMI + Kabel', 'Proyektor balai warga lengkap dengan kabel HDMI panjang 5 meter.', 1, 1, 'baik'],
            ['Alat Kerja Bakti', 'TAKE-AST-006', 'Cangkul & Sekop Taman', 'Alat gali dan pembersih saluran air saat kerja bakti mingguan.', 10, 10, 'baik'],
            ['Alat Kerja Bakti', 'TAKE-AST-007', 'Selang Air 30 Meter', 'Selang gulung untuk penyiraman taman dan pembersihan jalan.', 3, 2, 'perbaikan'],
            ['Alat Kerja Bakti', 'TAKE-AST-008', 'Gerobak Sorong (Arco)', 'Pengangkut sampah ranting, tanah, dan material gotong royong.', 3, 3, 'baik'],
            ['Peralatan Dapur Bersama', 'TAKE-AST-009', 'Kompor Gas Tekanan Tinggi', 'Kompor dua tungku untuk memasak konsumsi acara besar warga.', 2, 2, 'baik'],
            ['Peralatan Dapur Bersama', 'TAKE-AST-010', 'Dandang Nasi Besar (30L)', 'Dandang aluminium kapasitas besar untuk acara syukuran atau 17-an.', 2, 2, 'baik'],
        ];

        $assets = [];
        foreach ($assetsData as [$catName, $code, $name, $desc, $total, $avail, $cond]) {
            $assets[$code] = Asset::updateOrCreate([
                'code' => $code,
            ], [
                'category_id' => $categories[$catName]->id,
                'name' => $name,
                'description' => $desc,
                'total_stock' => $total,
                'available_stock' => $avail,
                'condition' => $cond,
            ]);
        }

        // 4. Contoh Data Peminjaman
        Borrowing::updateOrCreate([
            'booking_code' => 'TAKE-20261123-001',
        ], [
            'user_id' => $warga1->id,
            'asset_id' => $assets['TAKE-AST-002']->id,
            'quantity' => 25,
            'borrow_date' => now()->subDays(1)->toDateString(),
            'return_date' => now()->addDays(2)->toDateString(),
            'purpose' => 'Arisan & Pengajian Ibu-Ibu RT 02',
            'status' => 'borrowed',
        ]);

        Borrowing::updateOrCreate([
            'booking_code' => 'TAKE-20261123-002',
        ], [
            'user_id' => $warga2->id,
            'asset_id' => $assets['TAKE-AST-001']->id,
            'quantity' => 2,
            'borrow_date' => now()->subDays(5)->toDateString(),
            'return_date' => now()->subDays(1)->toDateString(), // sengaja terlambat untuk uji detektor overdue
            'purpose' => 'Turnamen Futsal Karang Taruna',
            'status' => 'borrowed',
        ]);

        Borrowing::updateOrCreate([
            'booking_code' => 'TAKE-20261123-003',
        ], [
            'user_id' => $warga3->id,
            'asset_id' => $assets['TAKE-AST-004']->id,
            'quantity' => 1,
            'borrow_date' => now()->addDays(1)->toDateString(),
            'return_date' => now()->addDays(2)->toDateString(),
            'purpose' => 'Rapat Koordinasi Keamanan Malam',
            'status' => 'pending',
        ]);

        Borrowing::updateOrCreate([
            'booking_code' => 'TAKE-20261123-004',
        ], [
            'user_id' => $warga1->id,
            'asset_id' => $assets['TAKE-AST-007']->id,
            'quantity' => 1,
            'borrow_date' => now()->subDays(10)->toDateString(),
            'return_date' => now()->subDays(8)->toDateString(),
            'returned_at' => now()->subDays(8),
            'purpose' => 'Bersih-Bersih Taman Blok B',
            'status' => 'returned',
            'return_condition' => 'perbaikan',
            'admin_note' => 'Sambungan kran sedikit longgar, perlu ganti seal.',
        ]);
    }
}
