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

/**
 * Normalizes a phone number to standard Indonesian WhatsApp format (starts with 628...).
 * Handles common variations: 08xxx, +62xxx, 8xxx, and accidental typos like 68xxx (+68 missing '2').
 * Example: "081392156513" -> "6281392156513"
 * Example: "+681392156513" -> "6281392156513"
 */
export const formatIndonesianWaNumber = (phone?: string | null): string => {
    let cleaned = (phone || '').replace(/\D/g, '');
    if (!cleaned) return '6281392156513';

    // Fix typo where country code 62 was typed as 6 (e.g. 6813... -> 62813...)
    if (cleaned.startsWith('68')) {
        cleaned = '628' + cleaned.slice(2);
    } else if (cleaned.startsWith('0')) {
        cleaned = '62' + cleaned.slice(1);
    } else if (cleaned.startsWith('8')) {
        cleaned = '62' + cleaned;
    } else if (!cleaned.startsWith('62')) {
        cleaned = '62' + cleaned;
    }

    return cleaned;
};

