<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'assignment_id',
        'file_path',
        'score',
        'feedback',
        'status',
    ];

    /**
     * Get the user who made the submission.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor for full file URL
     */
    public function getFileFullUrlAttribute()
    {
        return url('storage/' . $this->file_path);
    }
    
    protected $appends = ['file_full_url'];
}
