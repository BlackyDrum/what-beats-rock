<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Guess extends Model
{
    protected $table = 'guesses';

    protected $fillable = [
        'guess',
        'game_id'
    ];

    protected function guess(): Attribute
    {
        return Attribute::make(
            set: fn($value) => strtolower($value)
        );
    }
}
