<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    /**
     * Display a listing of videos.
     */
    public function index()
    {
        $videos = Video::with(['user' => function($query) {
            $query->select('id', 'name', 'photo');
        }])
        ->latest()
        ->get(['id', 'user_id', 'title', 'thumbnail', 'duration', 'views', 'created_at']);
        
        return response()->json([
            'success' => true,
            'data' => $videos,
        ], 200);
    }

    /**
     * Display the specified video.
     */
    public function show($id)
    {
        $video = Video::with('user')->find($id);
        
        if (!$video) {
            return response()->json([
                'success' => false,
                'message' => 'Video tidak ditemukan',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $video,
        ], 200);
    }

    /**
     * Store a newly created video.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            \Log::error('Video upload failed: User not authenticated');
            return response()->json([
                'success' => false,
                'message' => 'Anda harus login untuk mengupload video',
            ], 401);
        }

        \Log::info('Video upload started', [
            'user_id' => $user->id,
            'nisn' => $user->nisn,
            'has_video' => $request->hasFile('video'),
            'has_thumbnail' => $request->hasFile('thumbnail'),
            'title' => $request->title,
        ]);

        $request->validate([
            'title' => 'required|string|max:255',
            'video' => 'required|file|mimes:mp4,mov,avi,wmv|max:102400', // Max 100MB
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'description' => 'nullable|string',
        ]);

        try {
            \Log::info('Validation passed, starting file upload');
            
            // Upload thumbnail
            $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
            \Log::info('Thumbnail uploaded', ['path' => $thumbnailPath]);
            
            // Upload video
            $videoPath = $request->file('video')->store('videos', 'public');
            \Log::info('Video uploaded', ['path' => $videoPath]);
            
            // Get video duration using getID3
            \Log::info('Getting video duration');
            $duration = $this->getVideoDuration($request->file('video'));
            \Log::info('Duration detected', ['duration' => $duration]);
            
            // Create video record
            $video = Video::create([
                'user_id' => $user->id,
                'title' => $request->title,
                'thumbnail' => $thumbnailPath,
                'video_url' => $videoPath,
                'duration' => $duration,
                'description' => $request->description,
                'views' => 0,
            ]);
            
            \Log::info('Video record created', ['video_id' => $video->id]);
            
            return response()->json([
                'success' => true,
                'message' => 'Video berhasil diupload',
                'data' => $video->load('user'),
            ], 201);
            
        } catch (\Exception $e) {
            \Log::error('Video upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal upload video: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified video.
     */
    public function update(Request $request, $id)
    {
        $video = Video::find($id);
        
        if (!$video) {
            return response()->json([
                'success' => false,
                'message' => 'Video tidak ditemukan',
            ], 404);
        }
        
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Anda harus login',
            ], 401);
        }

        // Check if user is owner or admin
        if ($video->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengupdate video ini',
            ], 403);
        }
        
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'thumbnail' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'description' => 'nullable|string',
        ]);

        try {
            // Update thumbnail if provided
            if ($request->hasFile('thumbnail')) {
                // Delete old thumbnail
                if ($video->thumbnail && Storage::disk('public')->exists($video->thumbnail)) {
                    Storage::disk('public')->delete($video->thumbnail);
                }
                
                $video->thumbnail = $request->file('thumbnail')->store('thumbnails', 'public');
            }
            
            // Update other fields
            if ($request->has('title')) {
                $video->title = $request->title;
            }
            
            if ($request->has('description')) {
                $video->description = $request->description;
            }
            
            $video->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Video berhasil diupdate',
                'data' => $video->load('user'),
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal update video',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified video.
     */
    public function destroy($id)
    {
        $video = Video::find($id);
        
        if (!$video) {
            return response()->json([
                'success' => false,
                'message' => 'Video tidak ditemukan',
            ], 404);
        }
        
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Anda harus login',
            ], 401);
        }

        // Check if user is owner or admin
        if ($video->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menghapus video ini',
            ], 403);
        }

        try {
            // Delete files from storage
            if ($video->thumbnail && Storage::disk('public')->exists($video->thumbnail)) {
                Storage::disk('public')->delete($video->thumbnail);
            }
            
            if ($video->video_url && Storage::disk('public')->exists($video->video_url)) {
                Storage::disk('public')->delete($video->video_url);
            }
            
            $video->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Video berhasil dihapus',
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus video',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Increment view count for a video.
     */
    public function incrementView($id)
    {
        $video = Video::find($id);
        
        if (!$video) {
            return response()->json([
                'success' => false,
                'message' => 'Video tidak ditemukan',
            ], 404);
        }
        
        $video->incrementViews();
        
        return response()->json([
            'success' => true,
            'message' => 'View count updated',
            'views' => $video->views,
        ], 200);
    }

    /**
     * Get video duration using getID3 library.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string
     */
    private function getVideoDuration($file)
    {
        try {
            $getID3 = new \getID3;
            $fileInfo = $getID3->analyze($file->getRealPath());
            
            if (isset($fileInfo['playtime_seconds'])) {
                $seconds = (int) $fileInfo['playtime_seconds'];
                $minutes = floor($seconds / 60);
                $remainingSeconds = $seconds % 60;
                
                return sprintf('%d:%02d', $minutes, $remainingSeconds);
            }
            
            return '0:00';
        } catch (\Exception $e) {
            \Log::error('Failed to get video duration: ' . $e->getMessage());
            return '0:00';
        }
    }
}
