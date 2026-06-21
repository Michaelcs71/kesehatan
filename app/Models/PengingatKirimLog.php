<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PengingatKirimLog extends Model
{
    use HasUuids;

    protected $table = 'pengingat_kirim_log';

    protected $fillable = [
        'kejadian_id',
        'peserta_id',
        'kanal',
        'target',
        'fase',
        'status',
        'error',
    ];
}
