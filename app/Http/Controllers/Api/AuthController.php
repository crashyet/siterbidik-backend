<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function login(Request $request)
    {
      $request->validate([
        'login' => 'required|string',
        'password' => 'required|string',
      ]);

      // Determine if login is email or NISN
      $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'nisn';
      
      // Attempt login with the appropriate field
      $credentials = [
        $loginType => $request->login,
        'password' => $request->password,
      ];

      if (!Auth::attempt($credentials)) {
        return response()->json([
          'message' => 'NISN/Email atau password salah',
        ], 401);
      }

      $user = Auth::user();
      $token = $user->createToken('auth-token')->plainTextToken;

      return response()->json([
        'message' => 'Login berhasil',
        'user' => $user,
        'token' => $token,
      ], 200);
    }

    public function completedprofile(Request $request)
    {
      $request->validate([
        'name' => 'nullable|string|max:255',
        'class' => 'nullable|string|max:100',
        'email' => 'nullable|email|unique:users,email,' . Auth::id(),
        'phone' => 'nullable|string|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:15',
        'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'password' => 'nullable|string|min:8',
      ]);

      try {
        $user = Auth::user();
        
        // Debug logging
        \Log::info('=== Complete Profile Request ===');
        \Log::info('User ID: ' . $user->id);
        \Log::info('Has photo file: ' . ($request->hasFile('photo') ? 'YES' : 'NO'));
        
        if ($request->hasFile('photo')) {
          $file = $request->file('photo');
          \Log::info('Photo file details:', [
            'is_valid' => $file->isValid(),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
          ]);
        }
        
        // Update fields if provided
        if ($request->has('name')) {
          $user->name = $request->name;
        }
        
        if ($request->has('class')) {
          $user->class = $request->class;
        }
        
        if ($request->has('email')) {
          $user->email = $request->email;
        }
        
        if ($request->has('phone')) {
          $user->phone = $request->phone;
        }
        
        if ($request->filled('password')) {
          $user->password = Hash::make($request->password);
        }
        
        // Handle photo upload
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
          \Log::info('Processing photo upload...');
          
          // Delete old photo if exists
          if ($user->photo && Storage::disk('public')->exists($user->photo)) {
            Storage::disk('public')->delete($user->photo);
            \Log::info('Deleted old photo: ' . $user->photo);
          }
          
          // Store new photo
          $photoPath = $request->file('photo')->store('photos', 'public');
          $user->photo = $photoPath;
          \Log::info('Photo stored at: ' . $photoPath);
        } else {
          \Log::warning('Photo upload skipped - file not valid or not present');
        }
        
        // Mark as completed first login
        $user->is_first_login = false;
        
        \Log::info('Saving user with photo: ' . ($user->photo ?? 'NULL'));
        $user->save();
        \Log::info('User saved successfully');

        // Hide sensitive data
        $user->makeHidden(['password', 'remember_token']);

        return response()->json([
          'message' => 'Profile berhasil diupdate',
          'user' => $user,
        ], 200);
        
      } catch (\Exception $e) {
        return response()->json([
          'message' => 'Gagal update profile',
          'error' => $e->getMessage(),
        ], 500);
      }
    }
}
