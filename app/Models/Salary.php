<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class Salary extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'salary_month',
        'year',
        'present',
        'absent',
        'late',
        'early_exit',
        'leaves',
        'holidays',
        'sundays',
        'basic',
        'salary_generated',
        'amount_paid',
        'loan_repayment',
        'discount',
        'bonus_amount',
        'deduction_amount',
        'payment_method',
        'payment_date',
        'paid_by_type',
        'paid_by_name',
        'status',
    ];

    protected $casts = [
        'present' => 'integer',
        'absent' => 'integer',
        'late' => 'integer',
        'early_exit' => 'integer',
        'leaves' => 'integer',
        'holidays' => 'integer',
        'sundays' => 'integer',
        'basic' => 'decimal:2',
        'salary_generated' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'loan_repayment' => 'decimal:2',
        'discount' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Tenant DBs may exist before the payment columns migration ran — add columns on first use.
     */
    public static function ensurePaymentColumns(): void
    {
        if (! Schema::hasTable('salaries')) {
            return;
        }

        $needsPaymentMethod = ! Schema::hasColumn('salaries', 'payment_method');
        $needsPaymentDate = ! Schema::hasColumn('salaries', 'payment_date');

        if (! $needsPaymentMethod && ! $needsPaymentDate) {
            return;
        }

        try {
            Schema::table('salaries', function (Blueprint $table) use ($needsPaymentMethod, $needsPaymentDate) {
                if ($needsPaymentMethod) {
                    $table->string('payment_method')->nullable();
                }
                if ($needsPaymentDate) {
                    $table->date('payment_date')->nullable();
                }
            });
        } catch (\Throwable $e) {
            $paymentMethodReady = ! $needsPaymentMethod || Schema::hasColumn('salaries', 'payment_method');
            $paymentDateReady = ! $needsPaymentDate || Schema::hasColumn('salaries', 'payment_date');

            if ($paymentMethodReady && $paymentDateReady) {
                return;
            }

            throw $e;
        }
    }

    public static function ensurePaidByColumns(): void
    {
        if (! Schema::hasTable('salaries')) {
            return;
        }

        $needsPaidByType = ! Schema::hasColumn('salaries', 'paid_by_type');
        $needsPaidByName = ! Schema::hasColumn('salaries', 'paid_by_name');

        if (! $needsPaidByType && ! $needsPaidByName) {
            return;
        }

        try {
            Schema::table('salaries', function (Blueprint $table) use ($needsPaidByType, $needsPaidByName) {
                if ($needsPaidByType) {
                    $table->string('paid_by_type')->nullable();
                }
                if ($needsPaidByName) {
                    $table->string('paid_by_name')->nullable();
                }
            });
        } catch (\Throwable $e) {
            $paidByTypeReady = ! $needsPaidByType || Schema::hasColumn('salaries', 'paid_by_type');
            $paidByNameReady = ! $needsPaidByName || Schema::hasColumn('salaries', 'paid_by_name');

            if ($paidByTypeReady && $paidByNameReady) {
                return;
            }

            throw $e;
        }
    }

    public static function resolveAdminPayerType(?AdminRole $admin): string
    {
        if (! $admin) {
            return 'admin';
        }

        if (method_exists($admin, 'isSuperAdmin') && $admin->isSuperAdmin()) {
            return 'super_admin';
        }

        $flag = $admin->getAttributes()['super_admin'] ?? $admin->super_admin ?? false;
        if (in_array($flag, [true, 1, '1', 'true', 'yes', 'Yes'], true)) {
            return 'super_admin';
        }

        return 'admin';
    }

    /**
     * @return array{type: string, name: string}
     */
    public static function resolveCurrentPayer(): array
    {
        if (Auth::guard('accountant')->check()) {
            $accountant = Auth::guard('accountant')->user();
            $name = trim((string) ($accountant->name ?? $accountant->email ?? 'Accountant'));

            return [
                'type' => 'accountant',
                'name' => $name !== '' ? $name : 'Accountant',
            ];
        }

        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            $name = trim((string) ($admin->name ?? $admin->email ?? 'Admin'));

            return [
                'type' => static::resolveAdminPayerType($admin),
                'name' => $name !== '' ? $name : 'Admin',
            ];
        }

        if (Auth::guard('platform_super_admin')->check()) {
            $actor = Auth::guard('platform_super_admin')->user();
            $name = trim((string) ($actor->name ?? $actor->email ?? 'Super Admin'));

            return [
                'type' => 'super_admin',
                'name' => $name !== '' ? $name : 'Super Admin',
            ];
        }

        return ['type' => '', 'name' => ''];
    }

    /**
     * Payer + payment date metadata when a salary is marked or recorded as paid.
     *
     * @return array<string, mixed>
     */
    public static function normalizePaymentMethod(?string $method): ?string
    {
        $normalized = trim((string) $method);
        if ($normalized === '') {
            return null;
        }

        if (strcasecmp($normalized, 'Check') === 0 || strcasecmp($normalized, 'Cheque') === 0) {
            return 'Cheque';
        }

        return $normalized;
    }

    public function setPaymentMethodAttribute($value): void
    {
        $this->attributes['payment_method'] = static::normalizePaymentMethod(
            is_string($value) || $value === null ? $value : (string) $value
        );
    }

    public static function metadataForPaidAction(?self $existing = null): array
    {
        static::ensurePaymentColumns();
        static::ensurePaidByColumns();

        $payer = static::resolveCurrentPayer();
        $metadata = [];

        if ($payer['type'] !== '') {
            $metadata['paid_by_type'] = $payer['type'];
        }
        if ($payer['name'] !== '') {
            $metadata['paid_by_name'] = $payer['name'];
        }
        if ($existing === null || empty($existing->payment_date)) {
            $metadata['payment_date'] = now()->toDateString();
        }

        return $metadata;
    }

    public static function effectivePaymentDateExpression(): string
    {
        if (! Schema::hasColumn('salaries', 'payment_date')) {
            return 'updated_at';
        }

        return "COALESCE(NULLIF(payment_date, '0000-00-00'), updated_at)";
    }

    /**
     * @param  list<string>  $scopedNames
     * @param  list<string>  $superAdminNames
     */
    public static function applyPayerScopeFilter(
        $query,
        array $scopedNames,
        ?string $filterUserType,
        ?string $filterUser,
        array $superAdminNames = []
    ): void {
        if ($scopedNames === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        if (! Schema::hasColumn('salaries', 'paid_by_name')) {
            $query->whereRaw('1 = 0');

            return;
        }

        $userType = strtolower(trim((string) $filterUserType));
        $hasPaidByType = Schema::hasColumn('salaries', 'paid_by_type');

        $query->where(function ($scopeQuery) use ($scopedNames, $userType, $filterUser, $hasPaidByType, $superAdminNames) {
            $scopeQuery->where(function ($matchQuery) use ($scopedNames, $userType, $filterUser, $hasPaidByType) {
                foreach ($scopedNames as $name) {
                    $matchQuery->orWhereRaw('LOWER(TRIM(paid_by_name)) = ?', [$name]);
                }

                if (! $filterUser && $hasPaidByType && in_array($userType, ['super_admin', 'accountant'], true)) {
                    $matchQuery->orWhereRaw('LOWER(TRIM(paid_by_type)) = ?', [$userType]);
                }
            });

            if ($userType === 'admin') {
                $scopeQuery->where(function ($adminOnlyQuery) use ($hasPaidByType, $superAdminNames) {
                    if ($hasPaidByType) {
                        $adminOnlyQuery->where(function ($typeQuery) {
                            $typeQuery->whereNull('paid_by_type')
                                ->orWhereRaw("TRIM(paid_by_type) = ''")
                                ->orWhereRaw("LOWER(TRIM(paid_by_type)) = 'admin'");
                        });
                        $adminOnlyQuery->whereRaw("LOWER(TRIM(COALESCE(paid_by_type, ''))) != 'super_admin'");
                    }

                    foreach ($superAdminNames as $superAdminName) {
                        $adminOnlyQuery->whereRaw("LOWER(TRIM(COALESCE(paid_by_name, ''))) != ?", [$superAdminName]);
                    }
                });
            }
        });
    }

    protected static function booted(): void
    {
        static::saving(function () {
            static::ensurePaymentColumns();
            static::ensurePaidByColumns();
        });
    }

    /**
     * Get the staff member that owns the salary.
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}

