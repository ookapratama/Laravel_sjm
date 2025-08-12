<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MitraProfile extends Model
{
    use HasFactory;

    protected $table = 'mitra_profiles';

    protected $fillable = [
        'user_id',
        'no_ktp',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'alamat',
        'rt',
        'rw',
        'desa',
        'kecamatan',
        'kota',
        'kode_pos',

        'nama_rekening',
        'nama_bank',
        'nomor_rekening',

        'nama_ahli_waris',
        'hubungan_ahli_waris',
        'nama_sponsor',
    ];

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Jika ingin akses withdrawals melalui mitra
     */
    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class, 'user_id', 'user_id');
    }
}
