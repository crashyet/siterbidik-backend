<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimulationVideo extends Model
{
    protected $table = 'simulation_videos';
    protected $fillable = [
        'title',
        'slug',
        'video',
        'thumbnail',
        'description',
        'created_by',
        'is_active',
    ];

    // Relasi
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function views()
    {
        return $this->hasMany(SimulationVideoView::class);
    }

    public function comments()
    {
        return $this->hasMany(SimulationVideoComment::class);
    }
}
