<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
      $request->validate([
        'nisn' => 'required',
        'password' => 'required',
      ]);

      if (!Auth::attempt($request->only('nisn', 'password'))) {
        return response()->json([
          'message' => 'NISN atau password salah',
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
        
        if ($request->has('password')) {
          $user->password = $request->password;
        }
        
        // Handle photo upload
        if ($request->hasFile('photo')) {
          // Delete old photo if exists
          if ($user->photo && \Storage::disk('public')->exists($user->photo)) {
            \Storage::disk('public')->delete($user->photo);
          }
          
          $user->photo = $request->file('photo')->store('photos', 'public');
        }
        
        // Mark as completed first login
        $user->is_first_login = false;
        
        $user->save();

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
