<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimulationVideoView extends Model
{
    public $timestamps = false; // cuma created_at

    protected $fillable = [
        'user_id',
        'simulation_video_id',
        'watched_duration',
        'watch_percentage',
        'is_counted',
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

