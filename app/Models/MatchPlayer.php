<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot; // Importa la clase Pivot

class MatchPlayer extends Pivot // Extiende de Pivot, no de Model, para tablas pivote
{
    use HasFactory;

    // Define explícitamente el nombre de la tabla si no sigue la convención de Laravel (snake_case del nombre de la clase en plural)
    // En este caso, MatchPlayer -> match_players, que es la convención, pero es bueno ser explícito.
    protected $table = 'match_players';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'match_id',
        'player_id',
        'goals',
        'assists',
        'played_full_match',
    ];

    /**
     * Define la relación muchos a uno con el modelo Partido.
     * Un registro de MatchPlayer pertenece a un Partido.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function match()
    {
        // Asegúrate de que el modelo sea Partido, no Match
        return $this->belongsTo(Partido::class);
    }

    /**
     * Define la relación muchos a uno con el modelo Player.
     * Un registro de MatchPlayer pertenece a un Player.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
