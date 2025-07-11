<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partido extends Model // <-- Asegúrate de que la clase se llame Partido
{
    use HasFactory;

    protected $table = 'matches'; // <-- Importante: Laravel usará la tabla 'matches' en la base de datos

    protected $fillable = [
        'home_team_id',
        'away_team_id',
        'match_date',
        'location',
        'home_team_score',
        'away_team_score',
        'status',
    ];

    protected $casts = [
        'match_date' => 'datetime', // Castea automáticamente la fecha y hora
    ];

    // Relación con el equipo local
    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    // Relación con el equipo visitante
    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    // Relación muchos a muchos con jugadores a través de la tabla pivote match_players
    public function players()
    {
        return $this->belongsToMany(Player::class, 'match_players')
            ->withPivot('goals', 'assists', 'played_full_match') // Cargar las columnas extra de la tabla pivote
            ->withTimestamps(); // Si quieres created_at/updated_at en la tabla pivote
    }
}
