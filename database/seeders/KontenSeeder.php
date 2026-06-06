<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Edukasi;
use App\Models\Galeri;
use App\Models\Pengumuman;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KontenSeeder extends Seeder
{
    /**
     * Seed contoh data konten publik: Pengumuman, Edukasi, Galeri.
     * Gambar galeri/edukasi dibuat sebagai placeholder SVG di public disk.
     */
    public function run(): void
    {
        $creator = User::where('role', UserRole::SUPERADMIN->value)->first()
            ?? User::where('role', UserRole::ADMIN->value)->first()
            ?? User::first();

        if (! $creator) {
            $this->command->warn('  [SKIP] Tidak ada user untuk created_by, jalankan UserSeeder dulu');

            return;
        }

        $this->seedPengumuman($creator->id);
        $this->seedEdukasi($creator->id);
        $this->seedGaleri($creator->id);

        $this->command->info('  [OK] Konten publik (pengumuman, edukasi, galeri) seeded');
    }

    private function seedPengumuman(string $creatorId): void
    {
        $data = [
            [
                'judul' => 'Pembukaan Layanan Konsultasi Diabetes Gratis',
                'ringkasan' => 'Mulai bulan ini, tersedia layanan konsultasi diabetes gratis setiap hari kerja.',
                'konten' => "Kami dengan senang hati mengumumkan pembukaan layanan konsultasi diabetes gratis bagi seluruh pasien terdaftar.\n\nLayanan tersedia setiap Senin–Jumat pukul 08.00–14.00 WIB. Silakan datang ke fasilitas kesehatan terdekat atau hubungi PMO Anda untuk informasi lebih lanjut.",
            ],
            [
                'judul' => 'Jadwal Pemeriksaan Gula Darah Massal',
                'ringkasan' => 'Pemeriksaan gula darah massal akan diadakan akhir pekan ini di balai warga.',
                'konten' => "Dalam rangka meningkatkan kesadaran masyarakat akan pentingnya kontrol gula darah, kami mengadakan pemeriksaan gula darah massal secara gratis.\n\nAcara akan berlangsung pada hari Sabtu mendatang pukul 07.00 WIB di Balai Warga. Peserta diharapkan berpuasa minimal 8 jam sebelum pemeriksaan.",
            ],
            [
                'judul' => 'Pembaruan Aplikasi Pengingat Minum Obat',
                'ringkasan' => 'Fitur pengingat minum obat kini lebih akurat dan mudah digunakan.',
                'konten' => "Aplikasi Kesehatan telah diperbarui dengan fitur pengingat minum obat yang lebih baik.\n\nSekarang Anda dapat mengatur jadwal minum obat dengan lebih fleksibel, serta menerima notifikasi tepat waktu. Pastikan aplikasi Anda selalu dalam versi terbaru.",
            ],
        ];

        foreach ($data as $d) {
            Pengumuman::firstOrCreate(
                ['slug' => Str::slug($d['judul'])],
                array_merge($d, [
                    'is_published' => true,
                    'published_at' => now(),
                    'created_by' => $creatorId,
                ])
            );
        }
    }

    private function seedEdukasi(string $creatorId): void
    {
        $data = [
            [
                'judul' => 'Mengenal Diabetes Melitus dan Gejalanya',
                'kategori' => 'Diabetes',
                'ringkasan' => 'Pahami apa itu diabetes melitus, penyebab, dan gejala awal yang perlu diwaspadai.',
                'konten' => "Diabetes melitus adalah penyakit kronis yang ditandai dengan kadar gula darah yang tinggi.\n\nGejala umum meliputi sering haus, sering buang air kecil, mudah lelah, dan luka yang sulit sembuh. Deteksi dini sangat penting untuk mencegah komplikasi. Konsultasikan dengan tenaga kesehatan jika Anda mengalami gejala tersebut.",
            ],
            [
                'judul' => 'Pola Makan Sehat untuk Penderita Diabetes',
                'kategori' => 'Nutrisi',
                'ringkasan' => 'Panduan praktis mengatur pola makan agar gula darah tetap terkontrol.',
                'konten' => "Pola makan yang tepat berperan besar dalam mengontrol gula darah.\n\nUtamakan makanan tinggi serat seperti sayur dan buah, batasi gula dan karbohidrat sederhana, serta makan dalam porsi kecil namun sering. Hindari minuman manis dan perbanyak minum air putih.",
            ],
            [
                'judul' => 'Pentingnya Olahraga Rutin bagi Penderita Diabetes',
                'kategori' => 'Gaya Hidup',
                'ringkasan' => 'Aktivitas fisik teratur membantu tubuh menggunakan insulin lebih efektif.',
                'konten' => "Olahraga rutin membantu menurunkan kadar gula darah dan meningkatkan sensitivitas insulin.\n\nDisarankan melakukan aktivitas fisik ringan hingga sedang seperti jalan kaki 30 menit setiap hari. Selalu konsultasikan jenis dan intensitas olahraga dengan dokter Anda.",
            ],
            [
                'judul' => 'Cara Menyimpan dan Menggunakan Insulin dengan Benar',
                'kategori' => 'Pengobatan',
                'ringkasan' => 'Tips menyimpan insulin agar tetap efektif dan aman digunakan.',
                'konten' => "Insulin harus disimpan pada suhu yang tepat agar tetap efektif.\n\nSimpan insulin yang belum dibuka di dalam lemari pendingin (2–8°C), jangan dibekukan. Insulin yang sedang digunakan dapat disimpan pada suhu ruang hingga 28 hari. Selalu periksa tanggal kedaluwarsa sebelum digunakan.",
            ],
        ];

        foreach ($data as $i => $d) {
            $gambar = $this->makePlaceholder('edukasi', 'edu-'.($i + 1), $d['kategori'], '#3b82f6', '#06b6d4');

            Edukasi::firstOrCreate(
                ['slug' => Str::slug($d['judul'])],
                array_merge($d, [
                    'gambar_path' => $gambar,
                    'is_published' => true,
                    'published_at' => now(),
                    'created_by' => $creatorId,
                ])
            );
        }
    }

    private function seedGaleri(string $creatorId): void
    {
        $data = [
            ['judul' => 'Penyuluhan Kesehatan', 'deskripsi' => 'Kegiatan penyuluhan diabetes bersama warga.', 'c1' => '#10b981', 'c2' => '#3b82f6'],
            ['judul' => 'Pemeriksaan Gula Darah', 'deskripsi' => 'Pemeriksaan gula darah gratis untuk masyarakat.', 'c1' => '#f59e0b', 'c2' => '#ef4444'],
            ['judul' => 'Senam Sehat Bersama', 'deskripsi' => 'Senam pagi rutin untuk menjaga kebugaran.', 'c1' => '#06b6d4', 'c2' => '#10b981'],
            ['judul' => 'Konsultasi dengan Dokter', 'deskripsi' => 'Sesi konsultasi langsung dengan tenaga medis.', 'c1' => '#8b5cf6', 'c2' => '#3b82f6'],
            ['judul' => 'Edukasi Nutrisi', 'deskripsi' => 'Demonstrasi menu sehat untuk penderita diabetes.', 'c1' => '#ec4899', 'c2' => '#f59e0b'],
            ['judul' => 'Pelatihan PMO', 'deskripsi' => 'Pelatihan bagi Pendamping Minum Obat.', 'c1' => '#3b82f6', 'c2' => '#06b6d4'],
        ];

        foreach ($data as $i => $d) {
            $gambar = $this->makePlaceholder('galeri', 'galeri-'.($i + 1), $d['judul'], $d['c1'], $d['c2']);

            Galeri::firstOrCreate(
                ['judul' => $d['judul']],
                [
                    'deskripsi' => $d['deskripsi'],
                    'gambar_path' => $gambar,
                    'is_published' => true,
                    'created_by' => $creatorId,
                ]
            );
        }
    }

    /**
     * Buat placeholder SVG sederhana di public disk, kembalikan path-nya.
     */
    private function makePlaceholder(string $folder, string $name, string $label, string $c1, string $c2): string
    {
        $path = "{$folder}/{$name}.svg";
        $label = htmlspecialchars($label, ENT_QUOTES);

        if (! Storage::disk('public')->exists($path)) {
            $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="500" viewBox="0 0 800 500">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$c1}"/>
      <stop offset="100%" stop-color="{$c2}"/>
    </linearGradient>
  </defs>
  <rect width="800" height="500" fill="url(#g)"/>
  <text x="400" y="260" font-family="Arial, sans-serif" font-size="34" font-weight="bold"
        fill="#ffffff" text-anchor="middle">{$label}</text>
</svg>
SVG;
            Storage::disk('public')->put($path, $svg);
        }

        return $path;
    }
}
