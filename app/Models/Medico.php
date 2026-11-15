<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medico extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'especialidad',
    ];

    // Relación con la tabla de pacientes
    public function pacientes()
    {
        return $this->belongsToMany(Paciente::class);
    }
}
