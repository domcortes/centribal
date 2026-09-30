<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigSystem extends Model
{
    protected $table = 'config_system';

    protected $fillable = [
        'clave',
        'valor',
        'descripcion',
    ];
}
