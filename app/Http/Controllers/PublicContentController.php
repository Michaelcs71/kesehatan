<?php

namespace App\Http\Controllers;

use App\Repos\EdukasiRepository;
use App\Repos\GaleriRepository;
use App\Repos\PengumumanRepository;
use Illuminate\View\View;

/**
 * Controller untuk halaman publik (landing): Pengumuman, Edukasi, Galeri.
 * Hanya menampilkan konten yang sudah dipublikasikan.
 */
class PublicContentController extends Controller
{
    public function pengumuman(): View
    {
        return view('public.pengumuman', [
            'items' => PengumumanRepository::getPublished(),
        ]);
    }

    public function edukasi(): View
    {
        $items = EdukasiRepository::getPublished();

        return view('public.edukasi', [
            'items' => $items,
            'kategoris' => $items->pluck('kategori')->filter()->unique()->values(),
        ]);
    }

    public function edukasiShow(string $slug): View
    {
        $artikel = EdukasiRepository::findPublishedBySlug($slug);

        abort_if(! $artikel, 404);

        return view('public.edukasi-detail', [
            'artikel' => $artikel,
            'lainnya' => EdukasiRepository::getPublished(6)
                ->where('id', '!=', $artikel->id)
                ->take(3),
        ]);
    }

    public function galery(): View
    {
        return view('public.galery', [
            'items' => GaleriRepository::getPublished(),
        ]);
    }
}
