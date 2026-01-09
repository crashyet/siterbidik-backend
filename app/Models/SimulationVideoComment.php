<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimulationVideoComment extends Model
{
    protected $fillable = [
        'user_id',
        'simulation_video_id',
        'comment',
        'is_approved',
    ];

    public function video()
    {
        return $this->belongsTo(SimulationVideo::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

