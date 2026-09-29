<?php

/**
 * Image upload helpers.
 *
 * Every image the site accepts — event posters and club logos — goes through
 * readImageUpload(), so there is one size limit and one set of messages to
 * reason about. IMAGE_UPLOAD_MAX_BYTES is that limit; anything larger is
 * refused with a validation error before a single byte is sent to MySQL.
 *
 * The limit is not arbitrary. Posters and logos are stored as data URIs in
 * LONGTEXT columns, so an image travels inside a MySQL packet and base64 grows
 * it by a third. Once a packet is bigger than max_allowed_packet MariaDB drops
 * the connection and mysqli reports "MySQL server has gone away" on the *next*
 * query rather than the one that overflowed, which is what turned a too-large
 * poster into a fatal 500 on /club/events/edit with nothing saved. 512KB
 * encodes to roughly 700KB, which fits inside the 1MB packet that ships with
 * XAMPP, so uploads stay safe even before the server config is raised.
 */

/** Image types accepted for posters and logos. */
const IMAGE_UPLOAD_MIMES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
];

/** The size limit for every image upload. */
const IMAGE_UPLOAD_MAX_BYTES = 512 * 1024;

/**
 * Kept out of the usable packet so the data URI prefix, the rest of the row and
 * the protocol headers still fit inside one packet.
 */
const DB_PACKET_HEADROOM = 64 * 1024;

/** Used when the server's max_allowed_packet cannot be read. */
const DB_PACKET_FALLBACK = 16 * 1024 * 1024;

/**
 * Read a php.ini shorthand size ("2M", "512K", a byte count) as bytes.
 * Returns 0 for "0" and for anything unparseable, which callers treat as
 * "no limit configured" rather than "reject everything".
 */
function iniBytes(string $key): int
{
    $raw = trim((string) ini_get($key));

    if ($raw === '') {
        return 0;
    }

    $value = (int) $raw;
    $unit = strtolower(substr($raw, -1));

    if ($unit === 'k') {
        $value *= 1024;
    } elseif ($unit === 'm') {
        $value *= 1024 * 1024;
    } elseif ($unit === 'g') {
        $value *= 1024 * 1024 * 1024;
    }

    return $value;
}

/**
 * The largest request body PHP will accept. A file bigger than this is rejected
 * by the SAPI before the page runs, which is why an over-size upload is named
 * explicitly in the error message instead of looking like a silent no-op.
 */
function phpUploadLimitBytes(): int
{
    $limits = array_filter(
        [iniBytes('upload_max_filesize'), iniBytes('post_max_size')],
        static fn (int $bytes): bool => $bytes > 0
    );

    return $limits === [] ? 0 : min($limits);
}

/**
 * The server's max_allowed_packet, read once per request.
 *
 * The connection may already be gone by the time an upload is validated, so a
 * failure here falls back to a generous default rather than masking the real
 * error — the size check that follows still protects the write.
 */
function dbPacketLimitBytes(): int
{
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $cached = DB_PACKET_FALLBACK;

    try {
        $row = db()->query('SELECT @@max_allowed_packet AS packet')->fetch_assoc();
        $packet = (int) ($row['packet'] ?? 0);

        if ($packet > 0) {
            $cached = $packet;
        }
    } catch (Throwable $e) {
        // Keep the fallback.
    }

    return $cached;
}

/**
 * The size limit actually enforced on this deployment.
 *
 * Normally this is just IMAGE_UPLOAD_MAX_BYTES. It is clamped to what one
 * packet can carry so that a server with an unusually small max_allowed_packet
 * cannot bring the page down the same way it did before.
 */
function imageUploadLimitBytes(): int
{
    // base64 expands by 4/3, so only three quarters of the packet is image.
    $packetCap = (int) floor(max(dbPacketLimitBytes() - DB_PACKET_HEADROOM, 0) * 3 / 4);

    return max(1, min(IMAGE_UPLOAD_MAX_BYTES, $packetCap));
}

/**
 * Human readable size for the messages the user actually sees.
 */
function formatBytes(int $bytes): string
{
    if ($bytes >= 1024 * 1024) {
        return rtrim(rtrim(number_format($bytes / (1024 * 1024), 1), '0'), '.') . 'MB';
    }

    return max(1, (int) round($bytes / 1024)) . 'KB';
}

/**
 * Validate an uploaded image and return it as a data URI ready for a LONGTEXT
 * column.
 *
 * Returns [$dataUri, $error]. $dataUri is null when the form submitted no file
 * (leave the existing image alone) and $error is null on success. $label is the
 * field name used in the messages, e.g. 'Poster'.
 */
function readImageUpload(?array $file, string $label): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }

    $limit = imageUploadLimitBytes();

    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        $phpLimit = phpUploadLimitBytes();

        return [null, "{$label} image is too large. {$label}s must be " . formatBytes($limit)
            . ' or smaller; this server accepts uploads up to ' . formatBytes($phpLimit) . '.'];
    }

    if ($error !== UPLOAD_ERR_OK) {
        return [null, "The {$label} could not be uploaded. Please try again."];
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');

    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return [null, "The {$label} upload could not be verified. Please try again."];
    }

    $mime = (string) (mime_content_type($tmpPath) ?: '');

    if (!isset(IMAGE_UPLOAD_MIMES[$mime])) {
        return [null, "{$label} must be a JPG, PNG, WebP, or GIF image."];
    }

    if ((int) ($file['size'] ?? 0) > $limit) {
        return [null, "{$label} image must be " . formatBytes($limit) . ' or smaller.'];
    }

    $bytes = file_get_contents($tmpPath);

    if ($bytes === false) {
        return [null, "The {$label} could not be read. Please try again."];
    }

    return ['data:' . $mime . ';base64,' . base64_encode($bytes), null];
}
