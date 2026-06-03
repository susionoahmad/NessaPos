/**
 * Formats a number into Indonesian Rupiah (IDR) currency format.
 * Example: 1000000 -> "Rp 1.000.000"
 */
export const formatCurrency = (amount: number | string | undefined | null): string => {
    const value = typeof amount === 'string' ? parseFloat(amount) : amount;
    
    if (value === undefined || value === null || isNaN(value)) {
        return 'Rp 0';
    }

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value).replace(/\s/g, ' '); // Ensure consistent spacing
};

/**
 * Helper to ensure a value is a number, handling string decimals from API.
 */
export const toNum = (val: any): number => {
    if (typeof val === 'number') return val;
    if (!val) return 0;
    const translated = parseFloat(val);
    return isNaN(translated) ? 0 : translated;
};
