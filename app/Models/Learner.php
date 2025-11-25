<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Learner extends Model
{
    use HasFactory;

    protected $table = 'learners';
    protected $primaryKey = 'learner_id';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'address',
        'highest_qualification',
        'mobile_number',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
