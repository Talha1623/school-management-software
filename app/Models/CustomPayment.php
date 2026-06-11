<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CustomPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'student_code',
        'payment_title',
        'payment_amount',
        'accountant',
        'method',
        'notify_admin',
        'payment_date',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Tenant DBs may exist before the student_code migration ran — add column on first use.
     */
    public static function ensureStudentCodeColumn(): void
    {
        if (! Schema::hasTable('custom_payments') || Schema::hasColumn('custom_payments', 'student_code')) {
            return;
        }

        try {
            Schema::table('custom_payments', function (Blueprint $table) {
                $table->string('student_code')->nullable()->after('campus');
            });
        } catch (\Throwable $e) {
            if (! Schema::hasColumn('custom_payments', 'student_code')) {
                throw $e;
            }
        }
    }

    protected static function booted(): void
    {
        static::creating(function () {
            static::ensureStudentCodeColumn();
        });
    }

    /**
     * Super-admin Custom Payment also posts to student_payments — skip custom_payments row in income reports when mirrored.
     */
    public function isMirroredOnStudentLedger(): bool
    {
        $studentCode = trim((string) ($this->student_code ?? ''));
        $title = trim((string) ($this->payment_title ?? ''));

        if ($studentCode === '' || $title === '') {
            return false;
        }

        $query = StudentPayment::query()
            ->ledgerActive()
            ->where('student_code', $studentCode)
            ->paymentTitleKey($title)
            ->whereNotIn('method', ['Generated', 'Installment']);

        if ($this->payment_date) {
            $query->whereDate('payment_date', $this->payment_date);
        }

        return $query->exists();
    }
}
