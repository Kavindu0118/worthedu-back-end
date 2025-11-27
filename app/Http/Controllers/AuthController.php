<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Learner;
use App\Models\Instructor;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $data['username'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // generate token and store hashed version
        $plainToken = Str::random(60);
        $user->api_token = hash('sha256', $plainToken);
        $user->save();

        // Prepare user data with profile details based on role
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
        ];

        // Load role-specific profile data
        if ($user->role === 'instructor') {
            $instructor = $user->instructor;
            if ($instructor) {
                $userData['instructor'] = [
                    'instructor_id' => $instructor->instructor_id,
                    'first_name' => $instructor->first_name,
                    'last_name' => $instructor->last_name,
                    'date_of_birth' => $instructor->date_of_birth,
                    'address' => $instructor->address,
                    'mobile_number' => $instructor->mobile_number,
                    'highest_qualification' => $instructor->highest_qualification,
                    'subject_area' => $instructor->subject_area,
                    'status' => $instructor->status,
                    'note' => $instructor->note,
                ];
            }
        } elseif ($user->role === 'learner') {
            $learner = $user->learner;
            if ($learner) {
                $userData['learner'] = [
                    'learner_id' => $learner->learner_id,
                    'first_name' => $learner->first_name,
                    'last_name' => $learner->last_name,
                    'date_of_birth' => $learner->date_of_birth,
                    'address' => $learner->address,
                    'mobile_number' => $learner->mobile_number,
                    'highest_qualification' => $learner->highest_qualification,
                ];
            }
        }

        return response()->json([
            'message' => 'Login successful',
            'token' => $plainToken,
            'token_type' => 'Bearer',
            'user' => $userData,
        ]);
    }

    /**
     * Register a new learner: create user, then create learner profile linked by user_id.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',

            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'address' => 'required|string',
            'date_of_birth' => 'required|date',
            'highest_qualification' => 'required|in:none,certificate,diploma,degree,None,Certificate,Diploma,Degree',
            'mobile_number' => 'required|string',
        ]);

        // Normalize to lowercase for database
        $data['highest_qualification'] = strtolower($data['highest_qualification']);


        // Transaction: create user then learner
        $user = null;
        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $data['first_name'] . ' ' . $data['last_name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'learner',
            ]);

            Learner::create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'date_of_birth' => $data['date_of_birth'],
                'address' => $data['address'],
                'highest_qualification' => $data['highest_qualification'],
                'mobile_number' => $data['mobile_number'],
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Registration failed', 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Registration successful',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], 201);
    }
    /**
     * Instructor Registration
     */
    public function registerInstructor(Request $request)
    {
        $data = $request->validate([
            'first_name'            => 'required|string|max:255',
            'last_name'             => 'required|string|max:255',
            'username'              => 'required|string|max:255|unique:users,username',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|min:6',
            'date_of_birth'         => 'required|date',
            'address'               => 'required|string',
            'mobile_number'         => 'required|string|max:20',
            'highest_qualification' => 'required|in:none,certificate,diploma,degree,None,Certificate,Diploma,Degree',
            'subject_area'          => 'required|string|max:255',
            'cv'                    => 'required|mimes:pdf|max:2048', // 2MB PDF only
        ]);

        // Normalize to lowercase
        $data['highest_qualification'] = strtolower($data['highest_qualification']);

        DB::beginTransaction();

        try {
            // Create user with instructor role
            $user = User::create([
                'name'     => $data['first_name'] . ' ' . $data['last_name'],
                'username' => $data['username'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role'     => 'instructor',
            ]);

            // Read PDF as binary
            $cvBinary = file_get_contents($request->file('cv')->getRealPath());

            // Create instructor record
            Instructor::create([
                'user_id'               => $user->id,
                'first_name'            => $data['first_name'],
                'last_name'             => $data['last_name'],
                'date_of_birth'         => $data['date_of_birth'],
                'address'               => $data['address'],
                'mobile_number'         => $data['mobile_number'],
                'highest_qualification' => $data['highest_qualification'],
                'subject_area'          => $data['subject_area'],
                'cv'                    => $cvBinary,
                'status'                => 'pending',
                'note'                  => null,
            ]);

            DB::commit();

            return response()->json(['message' => 'Instructor registration submitted successfully!'], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Registration failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function me(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Prepare user data with profile details based on role
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
        ];

        // Load role-specific profile data
        if ($user->role === 'instructor') {
            $instructor = $user->instructor;
            if ($instructor) {
                $userData['instructor'] = [
                    'instructor_id' => $instructor->instructor_id,
                    'first_name' => $instructor->first_name,
                    'last_name' => $instructor->last_name,
                    'date_of_birth' => $instructor->date_of_birth,
                    'address' => $instructor->address,
                    'mobile_number' => $instructor->mobile_number,
                    'highest_qualification' => $instructor->highest_qualification,
                    'subject_area' => $instructor->subject_area,
                    'status' => $instructor->status,
                    'note' => $instructor->note,
                ];
            }
        } elseif ($user->role === 'learner') {
            $learner = $user->learner;
            if ($learner) {
                $userData['learner'] = [
                    'learner_id' => $learner->learner_id,
                    'first_name' => $learner->first_name,
                    'last_name' => $learner->last_name,
                    'date_of_birth' => $learner->date_of_birth,
                    'address' => $learner->address,
                    'mobile_number' => $learner->mobile_number,
                    'highest_qualification' => $learner->highest_qualification,
                ];
            }
        }

        return response()->json([
            'user' => $userData,
        ]);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $user->api_token = null;
            $user->save();
        }

        return response()->json(['message' => 'Logged out']);
    }
}
