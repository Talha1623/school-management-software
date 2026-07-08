@php
    $currencySettings = $settings ?? \App\Models\GeneralSetting::getSettings();
@endphp
const feeCurrencyCode = @json($currencySettings->currencyCode());
function formatFeeCurrency(val, decimals = 2) {
    const amount = Number(val || 0).toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });

    if (feeCurrencyCode === 'USD') return '$' + amount;
    if (feeCurrencyCode === 'EUR') return '€' + amount;
    if (feeCurrencyCode === 'GBP') return '£' + amount;
    if (feeCurrencyCode === 'AED') return 'AED ' + amount;

    return 'PKR ' + amount;
}
