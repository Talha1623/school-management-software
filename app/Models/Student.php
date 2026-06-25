<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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
        'discount_reason',
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
        'previous_class',
        'previous_section',
        'previous_school',
        'admission_date',
        'status',
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
     * Tenant DBs may exist before the status column migration ran — add column on first use.
     */
    public static function ensureStatusColumn(): void
    {
        if (!Schema::hasTable('students') || Schema::hasColumn('students', 'status')) {
            return;
        }

        try {
            Schema::table('students', function (Blueprint $table) {
                $table->string('status', 20)->default('active');
            });

            DB::table('students')->update(['status' => 'active']);
        } catch (\Throwable $e) {
            if (Schema::hasColumn('students', 'status')) {
                return;
            }

            throw $e;
        }
    }

    public static function hasStatusColumn(): bool
    {
        self::ensureStatusColumn();

        return Schema::hasTable('students') && Schema::hasColumn('students', 'status');
    }

    public function isActiveStudent(): bool
    {
        if (!self::hasStatusColumn()) {
            return $this->admission_date !== null;
        }

        return strtolower(trim((string) ($this->status ?? 'active'))) === 'active';
    }

    public function isInactiveStudent(): bool
    {
        if (!self::hasStatusColumn()) {
            return $this->admission_date === null;
        }

        return strtolower(trim((string) ($this->status ?? 'active'))) === 'inactive';
    }

    public function scopeActive($query)
    {
        if (!self::hasStatusColumn()) {
            return $query->whereNotNull('admission_date');
        }

        return $query->where(function ($q) {
            $q->where('status', 'active')
                ->orWhereNull('status');
        });
    }

    public function scopeInactive($query)
    {
        if (!self::hasStatusColumn()) {
            return $query->whereNull('admission_date');
        }

        return $query->where('status', 'inactive');
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

    /**
     * Student-specific in-app notifications (used by mobile app).
     */
    public function notifications()
    {
        return $this->hasMany(StudentNotification::class);
    }

    /**
     * Resolve transport fare from student profile or campus route table.
     */
    public function resolvedTransportFare(): ?float
    {
        $fare = (float) ($this->transport_fare ?? 0);
        if ($fare > 0.00001) {
            return round($fare, 2);
        }

        $route = trim((string) ($this->transport_route ?? ''));
        if ($route === '') {
            return null;
        }

        $transportQuery = Transport::query()
            ->whereRaw('LOWER(TRIM(route_name)) = ?', [strtolower($route)]);

        $campus = trim((string) ($this->campus ?? ''));
        if ($campus !== '') {
            $transportQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
        }

        $transport = $transportQuery->orderBy('id')->first();
        if ($transport && (float) $transport->route_fare > 0) {
            return round((float) $transport->route_fare, 2);
        }

        return null;
    }

    /**
     * Whether transport route and fare are configured for fee generation.
     */
    public function hasTransportConfigured(): bool
    {
        return trim((string) ($this->transport_route ?? '')) !== ''
            && $this->resolvedTransportFare() !== null;
    }

    /**
     * Students eligible for transport fee generation.
     */
    public function scopeEligibleForTransportFee($query)
    {
        return $query
            ->whereNotNull('transport_route')
            ->whereRaw("TRIM(COALESCE(transport_route, '')) <> ''")
            ->where(function ($q) {
                $q->where('transport_fare', '>', 0)
                    ->orWhereExists(function ($sub) {
                        $sub->selectRaw('1')
                            ->from('transports')
                            ->whereRaw('LOWER(TRIM(transports.route_name)) = LOWER(TRIM(students.transport_route))')
                            ->where('transports.route_fare', '>', 0);
                    });
            });
    }
}

