<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    protected $fillable = [
        'created_by','title','description','event_datetime','venue_name',
        'venue_address','city','theme','primary_color','secondary_color',
        'background_image','slug','is_active'
    ];

    protected $casts = [
        'event_datetime' => 'datetime',
        'is_active'      => 'boolean',
    ];

    public function owner() { return $this->belongsTo(User::class, 'created_by'); }
    public function guests() { return $this->hasMany(GuestEntry::class); }
}
