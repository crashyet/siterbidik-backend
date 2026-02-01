<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'thumbnail',
        'video_url',
        'views',
        'duration',
        'description',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['thumbnail_url', 'video_full_url', 'author', 'upload_date'];

    /**
     * Get the user that uploaded the video.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full URL for the video thumbnail.
     *
     * @return string|null
     */
    public function getThumbnailUrlAttribute()
    {
        if ($this->thumbnail) {
            return url('storage/' . $this->thumbnail);
        }
        return null;
    }

    /**
     * Get the full URL for the video file.
     *
     * @return string|null
     */
    public function getVideoFullUrlAttribute()
    {
        if ($this->video_url) {
            return url('storage/' . $this->video_url);
        }
        return null;
    }

    /**
     * Get the author information.
     *
     * @return array
     */
    public function getAuthorAttribute()
    {
        if ($this->user) {
            return [
                'name' => $this->user->name,
                'avatar' => $this->user->photo_url,
            ];
        }
        return [
            'name' => 'Unknown',
            'avatar' => null,
        ];
    }

    /**
     * Get the upload date in human readable format.
     *
     * @return string
     */
    public function getUploadDateAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Increment the view count.
     *
     * @return void
     */
    public function incrementViews()
    {
        $this->increment('views');
    }
}
