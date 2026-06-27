<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\JadwalCgdController;
use App\Http\Controllers\JadwalMinumObatController;
use App\Http\Controllers\KonfirmasiPengingatController;
use App\Http\Controllers\KontenEdukasiController;
use App\Http\Controllers\KontenGaleriController;
use App\Http\Controllers\KontenPengumumanController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MasterKategoriObatController;
use App\Http\Controllers\MasterObatController;
use App\Http\Controllers\MasterPasienController;
use App\Http\Controllers\MasterSatuanObatController;
use App\Http\Controllers\PasienController;
use App\Http\Controllers\PasienPmoController;
use App\Http\Controllers\PengaturanPengingatController;
use App\Http\Controllers\PengingatCgdLogController;
use App\Http\Controllers\PengingatMoLogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicContentController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| KESEHATAN — Routes
|--------------------------------------------------------------------------
*/

// PUBLIK
Route::get('/', function () {
    return view('landing.index');
})->name('home');

Route::get('/pengumuman', [PublicContentController::class, 'pengumuman'])->name('public.pengumuman');
Route::get('/edukasi', [PublicContentController::class, 'edukasi'])->name('public.edukasi');
Route::get('/edukasi/{slug}', [PublicContentController::class, 'edukasiShow'])->name('public.edukasi.show');
Route::get('/galery', [PublicContentController::class, 'galery'])->name('public.galery');

// AUTH ROUTER
Route::get('/dashboard', function () {
    return redirect()->route(auth()->user()->homeRoute());
})->middleware(['auth', 'verified'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| MASTER KATEGORI OBAT
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('master-kategori-obat')->name('master-kategori-obat.')->group(function () {

        // Endpoint options (untuk dropdown - aksesibel oleh siapapun yang bisa create master-obat)
        Route::get('/options', [MasterKategoriObatController::class, 'options'])->name('options');

        Route::middleware('permission:master-kategori-obat.index')->group(function () {
            Route::get('/', [MasterKategoriObatController::class, 'index'])->name('index');
            Route::get('/data', [MasterKategoriObatController::class, 'getData'])->name('data');
        });

        Route::middleware('permission:master-kategori-obat.create')->group(function () {
            Route::get('/create', [MasterKategoriObatController::class, 'create'])->name('create');
            Route::post('/', [MasterKategoriObatController::class, 'store'])->name('store');
        });

        Route::middleware('permission:master-kategori-obat.show')->group(function () {
            Route::get('/{id}', [MasterKategoriObatController::class, 'show'])->name('show');
            Route::get('/{id}/data', [MasterKategoriObatController::class, 'showData'])->name('show-data');
        });

        Route::middleware('permission:master-kategori-obat.edit')->group(function () {
            Route::get('/{id}/edit', [MasterKategoriObatController::class, 'edit'])->name('edit');
            Route::put('/{id}', [MasterKategoriObatController::class, 'update'])->name('update');
            Route::patch('/{id}', [MasterKategoriObatController::class, 'update']);
        });

        Route::delete('/{id}', [MasterKategoriObatController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:master-kategori-obat.delete');
    });
});

/*
|--------------------------------------------------------------------------
| MASTER SATUAN OBAT
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('master-satuan-obat')->name('master-satuan-obat.')->group(function () {

        Route::get('/options', [MasterSatuanObatController::class, 'options'])->name('options');

        Route::middleware('permission:master-satuan-obat.index')->group(function () {
            Route::get('/', [MasterSatuanObatController::class, 'index'])->name('index');
            Route::get('/data', [MasterSatuanObatController::class, 'getData'])->name('data');
        });

        Route::middleware('permission:master-satuan-obat.create')->group(function () {
            Route::get('/create', [MasterSatuanObatController::class, 'create'])->name('create');
            Route::post('/', [MasterSatuanObatController::class, 'store'])->name('store');
        });

        Route::middleware('permission:master-satuan-obat.show')->group(function () {
            Route::get('/{id}', [MasterSatuanObatController::class, 'show'])->name('show');
            Route::get('/{id}/data', [MasterSatuanObatController::class, 'showData'])->name('show-data');
        });

        Route::middleware('permission:master-satuan-obat.edit')->group(function () {
            Route::get('/{id}/edit', [MasterSatuanObatController::class, 'edit'])->name('edit');
            Route::put('/{id}', [MasterSatuanObatController::class, 'update'])->name('update');
            Route::patch('/{id}', [MasterSatuanObatController::class, 'update']);
        });

        Route::delete('/{id}', [MasterSatuanObatController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:master-satuan-obat.delete');
    });
});

/*
|--------------------------------------------------------------------------
| MASTER OBAT
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('master-obat')->name('master-obat.')->group(function () {

        Route::middleware('permission:master-obat.index')->group(function () {
            Route::get('/', [MasterObatController::class, 'index'])->name('index');
            Route::get('/data', [MasterObatController::class, 'getData'])->name('data');
        });

        Route::middleware('permission:master-obat.create')->group(function () {
            Route::get('/create', [MasterObatController::class, 'create'])->name('create');
            Route::post('/', [MasterObatController::class, 'store'])->name('store');
        });

        Route::middleware('permission:master-obat.show')->group(function () {
            Route::get('/{id}', [MasterObatController::class, 'show'])->name('show');
            Route::get('/{id}/data', [MasterObatController::class, 'showData'])->name('show-data');
        });

        Route::middleware('permission:master-obat.edit')->group(function () {
            Route::get('/{id}/edit', [MasterObatController::class, 'edit'])->name('edit');
            Route::put('/{id}', [MasterObatController::class, 'update'])->name('update');
            Route::patch('/{id}', [MasterObatController::class, 'update']);
        });

        Route::delete('/{id}', [MasterObatController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:master-obat.delete');

        Route::post('/{id}/verify', [MasterObatController::class, 'verify'])
            ->name('verify')
            ->middleware('permission:master-obat.verify');
    });
});

/*
|--------------------------------------------------------------------------
| MASTER USER
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('master-user')->name('master-user.')->group(function () {

        Route::middleware('permission:master-user.index')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/data', [UserController::class, 'getData'])->name('data');
            Route::get('/available-roles', [UserController::class, 'availableRoles'])->name('available-roles');
        });

        Route::middleware('permission:master-user.create')->group(function () {
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
        });

        Route::middleware('permission:master-user.show')->group(function () {
            Route::get('/{id}', [UserController::class, 'show'])->name('show');
            Route::get('/{id}/data', [UserController::class, 'showData'])->name('show-data');
        });

        Route::middleware('permission:master-user.edit')->group(function () {
            Route::get('/{id}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{id}', [UserController::class, 'update'])->name('update');
            Route::patch('/{id}', [UserController::class, 'update']);
        });

        // Reset password (admin/superadmin only) - butuh permission master-user.edit
        Route::post('/{id}/reset-password', [UserController::class, 'resetPassword'])
            ->name('reset-password')
            ->middleware('permission:master-user.edit');

        Route::delete('/{id}', [UserController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:master-user.delete');

        Route::middleware('role:superadmin')->group(function () {
            Route::get('/{id}/permissions', [UserController::class, 'getPermissions'])
                ->name('permissions.get');
            Route::post('/{id}/permissions', [UserController::class, 'updatePermissions'])
                ->name('permissions.update');
            Route::post('/{id}/permissions/reset', [UserController::class, 'resetPermissions'])
                ->name('permissions.reset');
        });
    });

    Route::prefix('master-user/import')->name('master-user.import.')->group(function () {

        // Download template (siapa saja yang punya akses master-user.create)
        Route::get('/template', [UserImportController::class, 'downloadTemplate'])
            ->name('template')
            ->middleware('permission:master-user.create');

        // Preview upload (parse Excel, tidak save)
        Route::post('/preview', [UserImportController::class, 'preview'])
            ->name('preview')
            ->middleware('permission:master-user.create');

        // Re-validate satu row (saat edit di modal)
        Route::post('/validate-row', [UserImportController::class, 'validateRow'])
            ->name('validate-row')
            ->middleware('permission:master-user.create');

        // Konfirmasi import (insert ke DB)
        Route::post('/confirm', [UserImportController::class, 'confirm'])
            ->name('confirm')
            ->middleware('permission:master-user.create');
    });
});

/*
|--------------------------------------------------------------------------
| PASIEN AREA
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:pasien'])
    ->prefix('pasien')->name('pasien.')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'pasien'])->name('dashboard');

        Route::get('/jadwal-cgd', [PasienController::class, 'jadwalCgd'])->name('jadwal.cgd');
        Route::get('/pengingat-mo', [PasienController::class, 'pengingatMo'])->name('pengingat.mo');
        Route::get('/pengingat-cgd', [PasienController::class, 'pengingatCgd'])->name('pengingat.cgd');
        Route::get('/riwayat', [PasienController::class, 'riwayat'])->name('riwayat');
    });

/*
|--------------------------------------------------------------------------
| PMO AREA
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:pmo'])
    ->prefix('pmo')->name('pmo.')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'pmo'])->name('dashboard');
    });

/*
|--------------------------------------------------------------------------
| PASIEN PMO MAPPING
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('pasien-pmo')->name('pasien-pmo.')->group(function () {

        // Options endpoints (untuk dropdown di form) - butuh permission create/edit
        Route::middleware('permission:pasien-pmo.create|pasien-pmo.edit')->group(function () {
            Route::get('/options/pmo', [PasienPmoController::class, 'pmoOptions'])->name('options.pmo');
            Route::get('/options/pasien', [PasienPmoController::class, 'pasienOptions'])->name('options.pasien');
        });

        // INDEX (list)
        Route::middleware('permission:pasien-pmo.index')->group(function () {
            Route::get('/', [PasienPmoController::class, 'index'])->name('index');
            Route::get('/data', [PasienPmoController::class, 'getData'])->name('data');
        });

        // CREATE
        Route::middleware('permission:pasien-pmo.create')->group(function () {
            Route::get('/create', [PasienPmoController::class, 'create'])->name('create');
            Route::post('/', [PasienPmoController::class, 'store'])->name('store');
        });

        // SHOW
        Route::middleware('permission:pasien-pmo.show')->group(function () {
            Route::get('/{id}', [PasienPmoController::class, 'show'])->name('show')
                ->where('id', '[0-9a-f\-]+');
            Route::get('/{id}/data', [PasienPmoController::class, 'showData'])->name('show-data')
                ->where('id', '[0-9a-f\-]+');
        });

        // EDIT + UPDATE + ACTIVATE/DEACTIVATE
        Route::middleware('permission:pasien-pmo.edit')->group(function () {
            Route::get('/{id}/edit', [PasienPmoController::class, 'edit'])->name('edit')
                ->where('id', '[0-9a-f\-]+');
            Route::put('/{id}', [PasienPmoController::class, 'update'])->name('update')
                ->where('id', '[0-9a-f\-]+');
            Route::patch('/{id}', [PasienPmoController::class, 'update'])
                ->where('id', '[0-9a-f\-]+');

            Route::post('/{id}/deactivate', [PasienPmoController::class, 'deactivate'])
                ->name('deactivate')->where('id', '[0-9a-f\-]+');
            Route::post('/{id}/activate', [PasienPmoController::class, 'activate'])
                ->name('activate')->where('id', '[0-9a-f\-]+');
        });

        // DELETE
        Route::delete('/{id}', [PasienPmoController::class, 'destroy'])
            ->name('destroy')
            ->where('id', '[0-9a-f\-]+')
            ->middleware('permission:pasien-pmo.delete');
    });
});

/*
|--------------------------------------------------------------------------
| JADWAL MINUM OBAT
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('jadwal-mo')->name('jadwal-mo.')->group(function () {

        // Options endpoints (untuk dropdown di form)
        Route::middleware('permission:jadwal-mo.create|jadwal-mo.edit')->group(function () {
            Route::get('/options/pasien-pmo', [JadwalMinumObatController::class, 'pasienPmoOptions'])->name('options.pasien-pmo');
            Route::get('/options/obat', [JadwalMinumObatController::class, 'obatOptions'])->name('options.obat');
            Route::get('/options/satuan', [JadwalMinumObatController::class, 'satuanOptions'])->name('options.satuan');

            // Quick-create obat (untuk modal di form jadwal)
            Route::post('/quick-create-obat', [JadwalMinumObatController::class, 'quickCreateObat'])->name('quick-create-obat');
        });

        // INDEX (list)
        Route::middleware('permission:jadwal-mo.index')->group(function () {
            Route::get('/', [JadwalMinumObatController::class, 'index'])->name('index');
            Route::get('/data', [JadwalMinumObatController::class, 'getData'])->name('data');
        });

        // CREATE
        Route::middleware('permission:jadwal-mo.create')->group(function () {
            Route::get('/create', [JadwalMinumObatController::class, 'create'])->name('create');
            Route::post('/', [JadwalMinumObatController::class, 'store'])->name('store');
        });

        // SHOW
        Route::middleware('permission:jadwal-mo.show')->group(function () {
            Route::get('/{id}', [JadwalMinumObatController::class, 'show'])->name('show')
                ->where('id', '[0-9a-f\-]+');
            Route::get('/{id}/data', [JadwalMinumObatController::class, 'showData'])->name('show-data')
                ->where('id', '[0-9a-f\-]+');
        });

        // EDIT + UPDATE + STATUS TOGGLES
        Route::middleware('permission:jadwal-mo.edit')->group(function () {
            Route::get('/{id}/edit', [JadwalMinumObatController::class, 'edit'])->name('edit')
                ->where('id', '[0-9a-f\-]+');
            Route::put('/{id}', [JadwalMinumObatController::class, 'update'])->name('update')
                ->where('id', '[0-9a-f\-]+');
            Route::patch('/{id}', [JadwalMinumObatController::class, 'update'])
                ->where('id', '[0-9a-f\-]+');

            Route::post('/{id}/deactivate', [JadwalMinumObatController::class, 'deactivate'])
                ->name('deactivate')->where('id', '[0-9a-f\-]+');
            Route::post('/{id}/activate', [JadwalMinumObatController::class, 'activate'])
                ->name('activate')->where('id', '[0-9a-f\-]+');
            Route::post('/{id}/mark-selesai', [JadwalMinumObatController::class, 'markSelesai'])
                ->name('mark-selesai')->where('id', '[0-9a-f\-]+');
        });

        // DELETE
        Route::delete('/{id}', [JadwalMinumObatController::class, 'destroy'])
            ->name('destroy')
            ->where('id', '[0-9a-f\-]+')
            ->middleware('permission:jadwal-mo.delete');
    });
});

/*
|--------------------------------------------------------------------------
| JADWAL CEK GULA DARAH (CGD)
|--------------------------------------------------------------------------
| Akses:
| - INDEX & SHOW: semua role (info publik)
| - CREATE/EDIT/DELETE: admin & superadmin saja
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('jadwal-cgd')->name('jadwal-cgd.')->group(function () {

        // INDEX (list) - semua role bisa lihat
        Route::middleware('permission:jadwal-cgd.index')->group(function () {
            Route::get('/', [JadwalCgdController::class, 'index'])->name('index');
            Route::get('/data', [JadwalCgdController::class, 'getData'])->name('data');
        });

        // CREATE - admin only
        Route::middleware('permission:jadwal-cgd.create')->group(function () {
            Route::get('/create', [JadwalCgdController::class, 'create'])->name('create');
            Route::post('/', [JadwalCgdController::class, 'store'])->name('store');
            Route::get('/options/pasien-pmo', [JadwalCgdController::class, 'pasienPmoOptions'])->name('options.pasien-pmo');
        });

        // SHOW - semua role bisa lihat detail
        Route::middleware('permission:jadwal-cgd.show')->group(function () {
            Route::get('/{id}', [JadwalCgdController::class, 'show'])->name('show')
                ->where('id', '[0-9a-f\-]+');
            Route::get('/{id}/data', [JadwalCgdController::class, 'showData'])->name('show-data')
                ->where('id', '[0-9a-f\-]+');
        });

        // EDIT + UPDATE + STATUS TOGGLES - admin only
        Route::middleware('permission:jadwal-cgd.edit')->group(function () {
            Route::get('/{id}/edit', [JadwalCgdController::class, 'edit'])->name('edit')
                ->where('id', '[0-9a-f\-]+');
            Route::put('/{id}', [JadwalCgdController::class, 'update'])->name('update')
                ->where('id', '[0-9a-f\-]+');
            Route::patch('/{id}', [JadwalCgdController::class, 'update'])
                ->where('id', '[0-9a-f\-]+');

            Route::post('/{id}/deactivate', [JadwalCgdController::class, 'deactivate'])
                ->name('deactivate')->where('id', '[0-9a-f\-]+');
            Route::post('/{id}/activate', [JadwalCgdController::class, 'activate'])
                ->name('activate')->where('id', '[0-9a-f\-]+');
            Route::post('/{id}/mark-selesai', [JadwalCgdController::class, 'markSelesai'])
                ->name('mark-selesai')->where('id', '[0-9a-f\-]+');
        });

        // DELETE - admin only
        Route::delete('/{id}', [JadwalCgdController::class, 'destroy'])
            ->name('destroy')
            ->where('id', '[0-9a-f\-]+')
            ->middleware('permission:jadwal-cgd.delete');
    });
});

/*
|--------------------------------------------------------------------------
| PENGATURAN PENGINGAT
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->prefix('pengaturan-pengingat')->name('pengaturan-pengingat.')->group(function () {
    Route::middleware('permission:pengaturan-pengingat.index')
        ->get('/', [PengaturanPengingatController::class, 'index'])->name('index');

    Route::middleware('permission:pengaturan-pengingat.update')
        ->match(['put', 'patch'], '/', [PengaturanPengingatController::class, 'update'])->name('update');
});

/*
|--------------------------------------------------------------------------
| IMPERSONATE / POV SWITCHER
|--------------------------------------------------------------------------
*/
Route::prefix('impersonate')->name('impersonate.')->group(function () {
    // leave: HANYA 'auth' (tanpa 'verified') agar superadmin selalu bisa keluar
    // walau user target belum terverifikasi.
    Route::middleware('auth')
        ->post('/leave', [ImpersonationController::class, 'kembali'])->name('leave');

    // Otorisasi superadmin/operator dilakukan di controller (saat ber-POV current
    // user bukan superadmin, jadi tak bisa pakai middleware role:superadmin di sini).
    Route::middleware('auth')
        ->post('/{role}', [ImpersonationController::class, 'mulai'])->name('start')
        ->where('role', 'admin|pmo|pasien');
});

/*
|--------------------------------------------------------------------------
| PENGINGAT MINUM OBAT (LOG KONFIRMASI)
|--------------------------------------------------------------------------
| Akses:
| - Pasien & PMO: full CRUD (konfirmasi minum obat)
| - Admin/Superadmin: monitoring (view all)
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('pengingat-mo')->name('pengingat-mo.')->group(function () {

        // Options untuk dropdown form
        Route::middleware('permission:pengingat-mo.create|pengingat-mo.edit')->group(function () {
            Route::get('/options/jadwal', [PengingatMoLogController::class, 'jadwalOptions'])->name('options.jadwal');
        });

        // INDEX
        Route::middleware('permission:pengingat-mo.index')->group(function () {
            Route::get('/', [PengingatMoLogController::class, 'index'])->name('index');
            Route::get('/data', [PengingatMoLogController::class, 'getData'])->name('data');
        });

        // CREATE
        Route::middleware('permission:pengingat-mo.create')->group(function () {
            Route::get('/create', [PengingatMoLogController::class, 'create'])->name('create');
            Route::post('/', [PengingatMoLogController::class, 'store'])->name('store');
        });

        // SHOW
        Route::middleware('permission:pengingat-mo.show')->group(function () {
            Route::get('/{id}', [PengingatMoLogController::class, 'show'])->name('show')
                ->where('id', '[0-9a-f\-]+');
            Route::get('/{id}/data', [PengingatMoLogController::class, 'showData'])->name('show-data')
                ->where('id', '[0-9a-f\-]+');
        });

        // EDIT + UPDATE + STATUS
        Route::middleware('permission:pengingat-mo.edit')->group(function () {
            Route::get('/{id}/edit', [PengingatMoLogController::class, 'edit'])->name('edit')
                ->where('id', '[0-9a-f\-]+');
            Route::post('/{id}/update', [PengingatMoLogController::class, 'update'])->name('update')
                ->where('id', '[0-9a-f\-]+');

            Route::post('/{id}/deactivate', [PengingatMoLogController::class, 'deactivate'])
                ->name('deactivate')->where('id', '[0-9a-f\-]+');
            Route::post('/{id}/activate', [PengingatMoLogController::class, 'activate'])
                ->name('activate')->where('id', '[0-9a-f\-]+');
        });

        // DELETE
        Route::delete('/{id}', [PengingatMoLogController::class, 'destroy'])
            ->name('destroy')
            ->where('id', '[0-9a-f\-]+')
            ->middleware('permission:pengingat-mo.delete');
    });
});

/*
|--------------------------------------------------------------------------
| PENGINGAT CEK GULA DARAH (LOG)
|--------------------------------------------------------------------------
| Akses:
| - Pasien & PMO: full CRUD
| - Admin/Superadmin: monitoring (view all)
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('pengingat-cgd')->name('pengingat-cgd.')->group(function () {

        // Options untuk dropdown
        Route::middleware('permission:pengingat-cgd.create|pengingat-cgd.edit')->group(function () {
            Route::get('/options/jadwal-cgd', [PengingatCgdLogController::class, 'jadwalCgdOptions'])->name('options.jadwal-cgd');
        });

        // INDEX
        Route::middleware('permission:pengingat-cgd.index')->group(function () {
            Route::get('/', [PengingatCgdLogController::class, 'index'])->name('index');
            Route::get('/data', [PengingatCgdLogController::class, 'getData'])->name('data');
        });

        // CREATE
        Route::middleware('permission:pengingat-cgd.create')->group(function () {
            Route::get('/create', [PengingatCgdLogController::class, 'create'])->name('create');
            Route::post('/', [PengingatCgdLogController::class, 'store'])->name('store');
        });

        // SHOW
        Route::middleware('permission:pengingat-cgd.show')->group(function () {
            Route::get('/{id}', [PengingatCgdLogController::class, 'show'])->name('show')
                ->where('id', '[0-9a-f\-]+');
            Route::get('/{id}/data', [PengingatCgdLogController::class, 'showData'])->name('show-data')
                ->where('id', '[0-9a-f\-]+');
        });

        // EDIT + UPDATE + STATUS
        Route::middleware('permission:pengingat-cgd.edit')->group(function () {
            Route::get('/{id}/edit', [PengingatCgdLogController::class, 'edit'])->name('edit')
                ->where('id', '[0-9a-f\-]+');
            Route::post('/{id}/update', [PengingatCgdLogController::class, 'update'])->name('update')
                ->where('id', '[0-9a-f\-]+');

            Route::post('/{id}/deactivate', [PengingatCgdLogController::class, 'deactivate'])
                ->name('deactivate')->where('id', '[0-9a-f\-]+');
            Route::post('/{id}/activate', [PengingatCgdLogController::class, 'activate'])
                ->name('activate')->where('id', '[0-9a-f\-]+');
        });

        // DELETE
        Route::delete('/{id}', [PengingatCgdLogController::class, 'destroy'])
            ->name('destroy')
            ->where('id', '[0-9a-f\-]+')
            ->middleware('permission:pengingat-cgd.delete');
    });
});

/*
|--------------------------------------------------------------------------
| SUPERADMIN AREA
|--------------------------------------------------------------------------
| Dashboard khusus superadmin. Untuk menu lain (master, konten, laporan),
| superadmin tetap mengakses via /admin/* (lihat ADMIN AREA di bawah).
*/
Route::middleware(['auth', 'verified', 'role:superadmin'])
    ->prefix('superadmin')->name('superadmin.')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'superadmin'])->name('dashboard');
    });

/*
|--------------------------------------------------------------------------
| ADMIN AREA
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:admin,superadmin'])
    ->prefix('admin')->name('admin.')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');

        Route::get('/master/pasien', [MasterPasienController::class, 'index'])
            ->name('master.pasien')->middleware('permission:master-pasien.index');
        Route::get('/master/pasien/{id}', [MasterPasienController::class, 'show'])
            ->name('master.pasien.show')->middleware('permission:master-pasien.show')
            ->where('id', '[0-9a-f\-]+');
        Route::view('/master/pmo', 'placeholder')->name('master.pmo')
            ->defaults('meta', ['title' => 'Master PMO']);

        Route::view('/transaksi/jadwal-cgd', 'placeholder')->name('transaksi.jadwal_cgd')
            ->defaults('meta', ['title' => 'Jadwal Cek Gula Darah']);
        Route::view('/transaksi/pillbox-mo', 'placeholder')->name('transaksi.pillbox_mo')
            ->defaults('meta', ['title' => 'Foto Pillbox MO']);
        Route::view('/transaksi/alat-cgd', 'placeholder')->name('transaksi.alat_cgd')
            ->defaults('meta', ['title' => 'Foto Alat CGD']);

        Route::get('/laporan/kepatuhan', [LaporanController::class, 'kepatuhan'])
            ->name('laporan.kepatuhan')
            ->middleware('permission:laporan-kepatuhan.index');
    });

/*
|--------------------------------------------------------------------------
| KONTEN PUBLIK — PENGUMUMAN
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('konten-pengumuman')->name('konten-pengumuman.')->group(function () {

        Route::middleware('permission:konten-pengumuman.index')->group(function () {
            Route::get('/', [KontenPengumumanController::class, 'index'])->name('index');
            Route::get('/data', [KontenPengumumanController::class, 'getData'])->name('data');
        });

        Route::middleware('permission:konten-pengumuman.create')->group(function () {
            Route::get('/create', [KontenPengumumanController::class, 'create'])->name('create');
            Route::post('/', [KontenPengumumanController::class, 'store'])->name('store');
        });

        Route::middleware('permission:konten-pengumuman.show')->group(function () {
            Route::get('/{id}', [KontenPengumumanController::class, 'show'])->name('show')
                ->where('id', '[0-9a-f\-]+');
            Route::get('/{id}/data', [KontenPengumumanController::class, 'showData'])->name('show-data')
                ->where('id', '[0-9a-f\-]+');
        });

        Route::middleware('permission:konten-pengumuman.edit')->group(function () {
            Route::get('/{id}/edit', [KontenPengumumanController::class, 'edit'])->name('edit')
                ->where('id', '[0-9a-f\-]+');
            Route::put('/{id}', [KontenPengumumanController::class, 'update'])->name('update')
                ->where('id', '[0-9a-f\-]+');
            Route::patch('/{id}', [KontenPengumumanController::class, 'update'])
                ->where('id', '[0-9a-f\-]+');
        });

        Route::delete('/{id}', [KontenPengumumanController::class, 'destroy'])
            ->name('destroy')
            ->where('id', '[0-9a-f\-]+')
            ->middleware('permission:konten-pengumuman.delete');
    });
});

/*
|--------------------------------------------------------------------------
| KONTEN PUBLIK — EDUKASI
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('konten-edukasi')->name('konten-edukasi.')->group(function () {

        Route::middleware('permission:konten-edukasi.index')->group(function () {
            Route::get('/', [KontenEdukasiController::class, 'index'])->name('index');
            Route::get('/data', [KontenEdukasiController::class, 'getData'])->name('data');
        });

        Route::middleware('permission:konten-edukasi.create')->group(function () {
            Route::get('/create', [KontenEdukasiController::class, 'create'])->name('create');
            Route::post('/', [KontenEdukasiController::class, 'store'])->name('store');
        });

        Route::middleware('permission:konten-edukasi.show')->group(function () {
            Route::get('/{id}', [KontenEdukasiController::class, 'show'])->name('show')
                ->where('id', '[0-9a-f\-]+');
            Route::get('/{id}/data', [KontenEdukasiController::class, 'showData'])->name('show-data')
                ->where('id', '[0-9a-f\-]+');
        });

        Route::middleware('permission:konten-edukasi.edit')->group(function () {
            Route::get('/{id}/edit', [KontenEdukasiController::class, 'edit'])->name('edit')
                ->where('id', '[0-9a-f\-]+');
            Route::put('/{id}', [KontenEdukasiController::class, 'update'])->name('update')
                ->where('id', '[0-9a-f\-]+');
            Route::patch('/{id}', [KontenEdukasiController::class, 'update'])
                ->where('id', '[0-9a-f\-]+');
        });

        Route::delete('/{id}', [KontenEdukasiController::class, 'destroy'])
            ->name('destroy')
            ->where('id', '[0-9a-f\-]+')
            ->middleware('permission:konten-edukasi.delete');
    });
});

/*
|--------------------------------------------------------------------------
| KONTEN PUBLIK — GALERI
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('konten-galery')->name('konten-galery.')->group(function () {

        Route::middleware('permission:konten-galery.index')->group(function () {
            Route::get('/', [KontenGaleriController::class, 'index'])->name('index');
            Route::get('/data', [KontenGaleriController::class, 'getData'])->name('data');
        });

        Route::middleware('permission:konten-galery.create')->group(function () {
            Route::get('/create', [KontenGaleriController::class, 'create'])->name('create');
            Route::post('/', [KontenGaleriController::class, 'store'])->name('store');
        });

        Route::middleware('permission:konten-galery.show')->group(function () {
            Route::get('/{id}', [KontenGaleriController::class, 'show'])->name('show')
                ->where('id', '[0-9a-f\-]+');
            Route::get('/{id}/data', [KontenGaleriController::class, 'showData'])->name('show-data')
                ->where('id', '[0-9a-f\-]+');
        });

        Route::middleware('permission:konten-galery.edit')->group(function () {
            Route::get('/{id}/edit', [KontenGaleriController::class, 'edit'])->name('edit')
                ->where('id', '[0-9a-f\-]+');
            Route::put('/{id}', [KontenGaleriController::class, 'update'])->name('update')
                ->where('id', '[0-9a-f\-]+');
            Route::patch('/{id}', [KontenGaleriController::class, 'update'])
                ->where('id', '[0-9a-f\-]+');
        });

        Route::delete('/{id}', [KontenGaleriController::class, 'destroy'])
            ->name('destroy')
            ->where('id', '[0-9a-f\-]+')
            ->middleware('permission:konten-galery.delete');
    });
});

// PROFILE
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| WEB PUSH — SUBSCRIBE / UNSUBSCRIBE
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('/push/unsubscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
});

/*
|--------------------------------------------------------------------------
| PENGINGAT — KONFIRMASI MINUM OBAT
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/pengingat/{kejadian}/konfirmasi', [KonfirmasiPengingatController::class, 'show'])
        ->name('pengingat.konfirmasi.show')->where('kejadian', '[0-9a-f\-]+');
    Route::post('/pengingat/{kejadian}/konfirmasi', [KonfirmasiPengingatController::class, 'store'])
        ->name('pengingat.konfirmasi.store')->where('kejadian', '[0-9a-f\-]+');
});

require __DIR__.'/auth.php';
