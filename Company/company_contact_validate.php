<?php
/**
 * Contact (mobile): 11 digits, starts with 09 — display hint 09XX-XXX-XXXX.
 * Telephone (landline): 8 digits, starts with 8 — display hint 8XXX-XXXX.
 */

function workconnect_normalize_contact_digits(string $raw): string {
    return preg_replace('/\D/', '', trim($raw));
}

function workconnect_contact_mobile_valid(string $digitsOnly): bool {
    return $digitsOnly !== '' && (bool) preg_match('/^09\d{9}$/', $digitsOnly);
}

/** For profile: empty is OK; if provided, must be valid mobile format. */
function workconnect_contact_mobile_valid_or_empty(string $digitsOnly): bool {
    return $digitsOnly === '' || workconnect_contact_mobile_valid($digitsOnly);
}

function workconnect_telephone_landline_valid(string $digitsOnly): bool {
    return $digitsOnly !== '' && (bool) preg_match('/^8\d{7}$/', $digitsOnly);
}

/** Optional landline: empty OK; if provided, must be 8XXX-XXXX (8 digits, leading 8). */
function workconnect_telephone_landline_valid_or_empty(string $digitsOnly): bool {
    return $digitsOnly === '' || workconnect_telephone_landline_valid($digitsOnly);
}
