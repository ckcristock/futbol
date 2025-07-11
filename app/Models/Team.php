<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'city',
    ];

    /**
     * Define la relación uno a muchos con el modelo Player.
     * Un equipo puede tener muchos jugadores.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function players()
    {
        return $this->hasMany(Player::class);
    }

    /**
     * Define la relación uno a muchos con el modelo Partido (para partidos donde este equipo es local).
     * Un equipo puede ser local en muchos partidos.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function homeMatches()
    {
        return $this->hasMany(Partido::class, 'home_team_id');
    }

    /**
     * Define la relación uno a muchos con el modelo Partido (para partidos donde este equipo es visitante).
     * Un equipo puede ser visitante en muchos partidos.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function awayMatches()
    {
        return $this->hasMany(Partido::class, 'away_team_id');
    }
}
