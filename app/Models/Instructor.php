<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    use HasFactory;

    // Optional: If your table name is 'instructors' (Laravel's default plural convention), 
    // you don't need to define $table. If it's different (e.g., 'teachers'), uncomment and adjust.
    // protected $table = 'instructors';
    
    // Specify the primary key column name
    protected $primaryKey = 'instructor_id';

    // The columns that are mass assignable (used in the create method in AuthController)
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'address',
        'mobile_number',
        'highest_qualification',
        'subject_area',
        'cv', // Stores the binary data of the CV PDF
        'status', // Used for approval status (e.g., 'pending', 'approved')
        'note',   // Used for administrative notes
    ];

    /**
     * The attributes that should be hidden for serialization.
     * Prevents binary CV data from causing JSON encoding errors.
     */
    protected $hidden = [
        'cv', // Hide binary CV data from JSON responses
    ];

    /**
     * Define the relationship: An Instructor belongs to one User.
     */
    public function user()
    {
        // Assumes the User model is in the App\Models namespace
        return $this->belongsTo(User::class);
    }
}