<?php

/**
 * Reusable input validators.
 *
 * Every function takes the raw value plus a human label and returns an
 * error string on failure or null when the value is acceptable, so pages
 * can collect messages with a single expression:
 *
 *     if ($msg = vRequired($name, 'Full name')) { $errors[] = $msg; }
 */

const MIN_PASSWORD_LENGTH = 8;

/**
 * Normalise a Bangladeshi mobile number to a comparable digit form so
 * +8801712…, 8801712… and 01712… all compare equal.
 */
function normalizePhone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';

    if (strpos($digits, '880') === 0) {
        $digits = substr($digits, 3);
    } elseif (strlen($digits) === 12 && strpos($digits, '88') === 0) {
        $digits = substr($digits, 2);
    } elseif (strlen($digits) === 11 && $digits[0] === '0') {
        $digits = substr($digits, 1);
    }

    return $digits;
}

function vRequired(string $value, string $label, string $message = ''): ?string
{
    if (trim($value) === '') {
        return $message !== '' ? $message : "{$label} is required.";
    }

    return null;
}

function vMinLen(string $value, int $min, string $label): ?string
{
    if (trim($value) !== '' && strLength(trim($value)) < $min) {
        return "{$label} must be at least {$min} characters.";
    }

    return null;
}

function vMaxLen(string $value, int $max, string $label): ?string
{
    if (strLength($value) > $max) {
        return "{$label} must be {$max} characters or fewer.";
    }

    return null;
}

function vEmail(string $value, string $label = 'Email'): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return "Please enter a valid {$label} address.";
    }

    return null;
}

function vPhone(string $value, string $label = 'Phone number'): ?string
{
    $digits = normalizePhone($value);
    if ($digits === '') {
        return null;
    }

    if (strlen($digits) !== 10 || $digits[0] !== '1') {
        return "{$label} must be a valid 10-digit mobile number (e.g. 01712345678).";
    }

    return null;
}

function vUrl(string $value, string $label = 'URL'): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    if (!filter_var($value, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $value)) {
        return "{$label} must be a valid link starting with http:// or https://";
    }

    return null;
}

function vDate(string $value, string $label = 'Date'): ?string
{
    if (trim($value) === '') {
        return null;
    }

    $date = DateTime::createFromFormat('Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) {
        return "{$label} is not a valid date.";
    }

    return null;
}

function vDateTime(string $value, string $label = 'Date and time'): ?string
{
    if (trim($value) === '') {
        return null;
    }

    $date = DateTime::createFromFormat('Y-m-d\TH:i', $value)
        ?: DateTime::createFromFormat('Y-m-d H:i:s', $value)
        ?: DateTime::createFromFormat('Y-m-d H:i', $value);
    if (!$date) {
        return "{$label} is not a valid date and time.";
    }

    return null;
}

function vTime(string $value, string $label = 'Time'): ?string
{
    if (trim($value) === '') {
        return null;
    }

    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $value)) {
        return "{$label} is not a valid time.";
    }

    return null;
}

function vInt(string $value, string $label, int $min = 0, int $max = PHP_INT_MAX): ?string
{
    if (trim($value) === '') {
        return null;
    }

    if (!preg_match('/^-?\d+$/', trim($value))) {
        return "{$label} must be a whole number.";
    }

    $int = (int) $value;
    if ($int < $min) {
        return "{$label} must be at least {$min}.";
    }
    if ($int > $max) {
        return "{$label} must be {$max} or less.";
    }

    return null;
}

function vIn(string $value, array $allowed, string $label): ?string
{
    if ($value === '') {
        return null;
    }

    if (!in_array($value, $allowed, true)) {
        return "{$label} is not a valid choice.";
    }

    return null;
}

/**
 * University ID: digits only, 7-20 characters.
 */
function vUniversityId(string $value, string $label = 'University ID'): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    if (!preg_match('/^\d{7,20}$/', $value)) {
        return "{$label} must be 7-20 digits with no spaces or letters.";
    }

    return null;
}

/**
 * Password policy: at least 8 characters and not entirely numeric.
 */
function vPassword(string $value, string $label = 'Password'): ?string
{
    if (strLength($value) < MIN_PASSWORD_LENGTH) {
        return "{$label} must be at least " . MIN_PASSWORD_LENGTH . ' characters long.';
    }

    if (preg_match('/^\d+$/', $value)) {
        return "{$label} cannot consist of numbers only.";
    }

    return null;
}

/**
 * A password must differ from things that are public on the same form.
 */
function vPasswordNotSimilar(string $password, array $forbidden, string $label = 'Password'): ?string
{
    // Compare with case and separators removed so "Arif Hossain" is also caught
    // by a password like "arifhossain1".
    $squash = static function (string $value): string {
        return strtolower((string) preg_replace('/[^a-z0-9]/i', '', $value));
    };

    $lowered = $squash($password);

    foreach ($forbidden as $value) {
        $value = $squash(trim((string) $value));
        // A 1-2 character name would sit inside almost any password, so only
        // compare against values specific enough to be a real giveaway.
        if (strlen($value) < 4) {
            continue;
        }
        if (strpos($lowered, $value) !== false) {
            return "{$label} must not contain your name, email or university ID.";
        }
    }

    return null;
}

/**
 * UIU student IDs start with a fixed department code. Returns the matching
 * department, or null when the prefix is not one of the known departments.
 */
function departmentForUniversityId(string $universityId): ?string
{
    $prefixes = departmentIdPrefixes();
    foreach ($prefixes as $department => $prefix) {
        if (strpos($universityId, $prefix) === 0) {
            return $department;
        }
    }

    return null;
}

/**
 * Collect the first error from a set of checks.
 */
function vFirst(?string ...$messages): ?string
{
    foreach ($messages as $message) {
        if ($message !== null) {
            return $message;
        }
    }

    return null;
}

/**
 * Push a message onto an error bag when it is not null.
 */
function vAdd(array &$errors, ?string $message): void
{
    if ($message !== null) {
        $errors[] = $message;
    }
}
