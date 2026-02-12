<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class SubmissionController extends Controller
{
    /**
     * Get submissions for a specific assignment (Guru only)
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:bicara,tugas_akhir',
            'assignment_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        
        $query = Submission::with('user')
            ->where('type', $request->type)
            ->where('assignment_id', $request->assignment_id);

        // Filter by class if the user is a teacher (guru) and has a class assigned
        if ($user->role === 'guru' && !empty($user->class)) {
            // Support multiple classes separated by comma (e.g., "10 IPA 1, 10 IPA 2")
            $classes = array_map('trim', explode(',', $user->class));
            $query->whereHas('user', function ($q) use ($classes) {
                $q->whereIn('class', $classes);
            });
        }

        $submissions = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $submissions
        ]);
    }

    /**
     * Store student submission
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:bicara,tugas_akhir',
            'assignment_id' => 'required',
            'file' => 'required|file|max:20480', // Max 20MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        
        // Handle file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('submissions/' . $request->type, $filename, 'public');

            // Find or create submission
            $submission = Submission::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => $request->type,
                    'assignment_id' => $request->assignment_id,
                ],
                [
                    'file_path' => $path,
                    'status' => 'Selesai',
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Tugas berhasil diupload',
                'data' => $submission
            ]);
        }

        return response()->json(['message' => 'File tidak ditemukan'], 400);
    }

    /**
     * Submit grade and feedback (Guru only)
     */
    public function grade(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'score' => 'required|integer|min:0|max:100',
            'feedback' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $submission = Submission::findOrFail($id);
        
        $submission->update([
            'score' => $request->score,
            'feedback' => $request->feedback,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Nilai berhasil disimpan',
            'data' => $submission
        ]);
    }

    /**
     * Get a student's own submission for a specific assignment
     */
    public function showStudentSubmission(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:bicara,tugas_akhir',
            'assignment_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        
        $submission = Submission::where('user_id', $user->id)
            ->where('type', $request->type)
            ->where('assignment_id', $request->assignment_id)
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => $submission
        ]);
    }

    /**
     * Get all submissions for the current authenticated student
     */
    public function indexMySubmissions(Request $request)
    {
        $user = $request->user();
        
        $submissions = Submission::where('user_id', $user->id)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $submissions
        ]);
    }
}
