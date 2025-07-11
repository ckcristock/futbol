<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'position',
        'team_id', // Para la relación con el equipo
    ];

    /**
     * Define la relación muchos a uno con el modelo Team.
     * Un jugador pertenece a un equipo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Define la relación muchos a muchos con el modelo Partido a través de la tabla pivote match_players.
     * Un jugador puede participar en muchos partidos y tener estadísticas en ellos.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function matches()
    {
        return $this->belongsToMany(Partido::class, 'match_players')
            ->withPivot('goals', 'assists', 'played_full_match') // Incluye las columnas de la tabla pivote
            ->withTimestamps(); // Si quieres created_at/updated_at en la tabla pivote
    }
}
