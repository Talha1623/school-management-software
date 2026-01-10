<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class Student extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_name',
        'surname_caste',
        'gender',
        'date_of_birth',
        'place_of_birth',
        'photo',
        'father_id_card',
        'father_name',
        'father_email',
        'father_phone',
        'mother_phone',
        'whatsapp_number',
        'religion',
        'home_address',
        'b_form_number',
        'monthly_fee',
        'discounted_student',
        'transport_route',
        'transport_fare',
        'admission_notification',
        'create_parent_account',
        'generate_admission_fee',
        'admission_fee_amount',
        'generate_other_fee',
        'fee_type',
        'other_fee_amount',
        'student_code',
        'gr_number',
        'campus',
        'class',
        'section',
        'previous_school',
        'admission_date',
        'reference_remarks',
        'parent_account_id',
        'email',
        'password',
        'api_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'admission_date' => 'date',
            'monthly_fee' => 'decimal:2',
            'transport_fare' => 'decimal:2',
            'admission_fee_amount' => 'decimal:2',
            'other_fee_amount' => 'decimal:2',
            'discounted_student' => 'boolean',
            'create_parent_account' => 'boolean',
        ];
    }

    /**
     * Set the password attribute (hash it)
     */
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    /**
     * Check if student has login access (has student_code and password)
     */
    public function hasLoginAccess(): bool
    {
        return !empty($this->student_code) && !empty($this->password);
    }

    /**
     * Get the photo URL.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo
            ? Storage::url($this->photo)
            : null;
    }

    /**
     * Get the parent account for this student.
     */
    public function parentAccount()
    {
        return $this->belongsTo(ParentAccount::class, 'parent_account_id');
    }

    /**
     * Get the behavior records for this student.
     */
    public function behaviorRecords()
    {
        return $this->hasMany(BehaviorRecord::class);
    }
}

