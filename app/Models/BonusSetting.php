<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusSetting extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'key', 'value'];

    public static function getGroup($type)
    {
        return self::where('type', $type)->pluck('value', 'key')->toArray();
    }
    public static function getBonusConfig($type, $key, $default = null)
{
    return self::where('type', $type)->where('key', $key)->value('value') ?? $default;
}
}
