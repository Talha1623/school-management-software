<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    use HasFactory;

    protected $table = 'general_settings';

    protected $fillable = [
        'school_name',
        'sms_signature',
        'address',
        'school_phone',
        'school_email',
        'currency',
        'timezone',
        'running_session',
        'logo',
        'system_name',
        'fee_voucher_notice',
        'accounts_settlement_print_note',
        'fee_voucher_bank_name',
        'fee_voucher_account_title',
        'fee_voucher_account_number',
        'fee_voucher_iban',
        'bio_token',
        'campus_id',
        'automation_settings',
    ];

    protected $casts = [
        'automation_settings' => 'array',
    ];

    public static function getSettings(): self
    {
        return self::first() ?? self::create([
            'currency' => 'PKR',
            'timezone' => 'Asia/Karachi',
        ]);
    }

    public function currencyCode(): string
    {
        return strtoupper(trim((string) ($this->currency ?: 'PKR')));
    }

    public function currencySymbol(): string
    {
        return match ($this->currencyCode()) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'AED' => 'د.إ',
            default => '₨',
        };
    }

    public function formatCurrency(float|int|string|null $amount, int $decimals = 2): string
    {
        $formatted = number_format((float) $amount, $decimals);

        return match ($this->currencyCode()) {
            'USD' => '$' . $formatted,
            'EUR' => '€' . $formatted,
            'GBP' => '£' . $formatted,
            'AED' => 'AED ' . $formatted,
            default => 'PKR ' . $formatted,
        };
    }
}
