<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'dni',
        'sex',
        'birth_date',
        'phone',
        'address',
        'occupation',
    ];

    // Accesor conveniente
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    protected function dni(): Attribute
    {
        return Attribute::make(
            //  al guardar: deja solo 13 dígitos
            set: fn ($value) => preg_replace('/\D/', '', (string) $value),

            //  al leer: lo muestra con guiones
            get: fn ($value) => strlen($value) === 13
                ? substr($value, 0, 4) . '-' . substr($value, 4, 4) . '-' . substr($value, 8, 5)
                : $value,
        );
    }
}
