<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/enquiry-form.php';

function enquiry_types(): array
{
    return ['General', 'Property Enquiry', 'Listing Enquiry'];
}

function enquiry_statuses(): array
{
    return ['New', 'Contacted', 'Closed'];
}

function enquiry_status_class(string $status): string
{
    return match ($status) {
        'New'       => 'is-new',
        'Contacted' => 'is-contacted',
        'Closed'    => 'is-closed',
        default     => 'is-unknown',
    };
}

function enquiry_whatsapp_url(?string $phone): ?string
{
    if ($phone === null || trim($phone) === '') {
        return null;
    }

    $digits = preg_replace('/\D/', '', ltrim(trim($phone), '+')) ?? '';
    if ($digits === '') {
        return null;
    }

    return 'https://wa.me/' . $digits;
}

/**
 * @return array{valid: bool, error: ?string, data: array<string, mixed>}
 */
function enquiry_parse_and_validate(array $data): array
{
    $name          = trim($data['name'] ?? '');
    $phone         = trim($data['phone'] ?? '');
    $email         = trim($data['email'] ?? '');
    $message       = trim($data['message'] ?? '');
    $enquiryType   = trim($data['enquiry_type'] ?? 'General');
    $propertyId    = filter_var($data['property_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $propertyTitle = trim($data['property_title'] ?? $data['interested_property'] ?? '');

    if ($name === '' || mb_strlen($name) < 2) {
        return ['valid' => false, 'error' => 'Please enter your full name.', 'data' => []];
    }

    if (mb_strlen($name) > 150) {
        return ['valid' => false, 'error' => 'Name is too long.', 'data' => []];
    }

    if ($message === '') {
        return ['valid' => false, 'error' => 'Please enter a message.', 'data' => []];
    }

    if (mb_strlen($message) < 10) {
        return ['valid' => false, 'error' => 'Please add a little more detail to your message.', 'data' => []];
    }

    if (mb_strlen($message) > 5000) {
        return ['valid' => false, 'error' => 'Message is too long.', 'data' => []];
    }

    if ($phone === '' && $email === '') {
        return ['valid' => false, 'error' => 'Please enter a phone number or email address so we can contact you.', 'data' => []];
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'error' => 'Please enter a valid email address.', 'data' => []];
    }

    if ($phone !== '') {
        $phoneError = enquiry_validate_phone($phone);
        if ($phoneError !== null) {
            return ['valid' => false, 'error' => $phoneError, 'data' => []];
        }
        $phone = enquiry_normalize_phone($phone);
    }

    if ($propertyTitle !== '' && mb_strlen($propertyTitle) > 255) {
        return ['valid' => false, 'error' => 'Subject is too long.', 'data' => []];
    }

    if (!in_array($enquiryType, enquiry_types(), true)) {
        $enquiryType = 'General';
    }

    if ($propertyId && $propertyTitle === '') {
        $lookup = db()->prepare('SELECT title FROM properties WHERE id = :id LIMIT 1');
        $lookup->execute(['id' => $propertyId]);
        $row = $lookup->fetch();
        if ($row) {
            $propertyTitle = $row['title'];
        }
    }

    if (!$propertyId && $propertyTitle !== '') {
        $lookup = db()->prepare('SELECT id, title FROM properties WHERE title = :title LIMIT 1');
        $lookup->execute(['title' => $propertyTitle]);
        $row = $lookup->fetch();
        if ($row) {
            $propertyId = (int) $row['id'];
            $propertyTitle = $row['title'];
        }
    }

    if ($propertyId && $enquiryType === 'General') {
        $enquiryType = 'Property Enquiry';
    }

    return [
        'valid' => true,
        'error' => null,
        'data'  => [
            'name'           => $name,
            'phone'          => $phone,
            'email'          => $email,
            'message'        => $message,
            'enquiry_type'   => $enquiryType,
            'property_id'    => $propertyId,
            'property_title' => $propertyTitle,
        ],
    ];
}

function enquiry_is_honeypot(array $data): bool
{
    $trap = trim($data['website'] ?? '');

    return $trap !== '';
}

function enquiry_rate_limit_exceeded(?string $ip = null): bool
{
    $limit = defined('ENQUIRY_RATE_LIMIT') ? ENQUIRY_RATE_LIMIT : 5;
    if ($limit <= 0) {
        return false;
    }

    $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '');
    if ($ip === '') {
        return false;
    }

    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM enquiries
         WHERE ip_address = :ip AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)'
    );
    $stmt->execute(['ip' => $ip]);

    return (int) $stmt->fetchColumn() >= $limit;
}

function enquiry_save(array $data): array
{
    require_once __DIR__ . '/recaptcha.php';
    $recaptchaError = recaptcha_verify_token($data['g-recaptcha-response'] ?? null);
    if ($recaptchaError !== null) {
        return ['success' => false, 'error' => $recaptchaError];
    }

    if (enquiry_is_honeypot($data)) {
        return ['success' => true, 'spam' => true];
    }

    if (enquiry_rate_limit_exceeded()) {
        return [
            'success' => false,
            'error'   => 'Too many enquiries from your connection. Please try again later or contact us by phone.',
        ];
    }

    $parsed = enquiry_parse_and_validate($data);
    if (!$parsed['valid']) {
        return ['success' => false, 'error' => $parsed['error']];
    }

    $v = $parsed['data'];

    try {
        db()->prepare(
            'INSERT INTO enquiries (name, email, phone, message, enquiry_type, property_id, property_title, status, ip_address)
             VALUES (:name, :email, :phone, :message, :enquiry_type, :property_id, :property_title, :status, :ip_address)'
        )->execute([
            'name'           => $v['name'],
            'email'          => $v['email'] !== '' ? $v['email'] : null,
            'phone'          => $v['phone'] !== '' ? $v['phone'] : null,
            'message'        => $v['message'],
            'enquiry_type'   => $v['enquiry_type'],
            'property_id'    => $v['property_id'],
            'property_title' => $v['property_title'] !== '' ? $v['property_title'] : null,
            'status'         => 'New',
            'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $e) {
        return ['success' => false, 'error' => 'Your enquiry could not be saved. Please try again or contact us by phone.'];
    }

    $id = (int) db()->lastInsertId();

    require_once __DIR__ . '/mail.php';
    enquiry_send_notification($id, $v);

    return ['success' => true, 'id' => $id];
}

function enquiry_list(array $filters = []): array
{
    $where  = ['1 = 1'];
    $params = [];

    if (!empty($filters['status'])) {
        $where[] = 'e.status = :status';
        $params['status'] = $filters['status'];
    }

    if (!empty($filters['enquiry_type'])) {
        $where[] = 'e.enquiry_type = :enquiry_type';
        $params['enquiry_type'] = $filters['enquiry_type'];
    }

    if (!empty($filters['property_id'])) {
        $where[] = 'e.property_id = :property_id';
        $params['property_id'] = (int) $filters['property_id'];
    }

    $sql = 'SELECT e.*, COALESCE(e.property_title, p.title) AS property_title
            FROM enquiries e
            LEFT JOIN properties p ON p.id = e.property_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY e.created_at DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function enquiry_find(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT e.*, COALESCE(e.property_title, p.title) AS property_title
         FROM enquiries e
         LEFT JOIN properties p ON p.id = e.property_id
         WHERE e.id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function enquiry_update(int $id, string $status, string $adminNote = ''): bool
{
    if (!in_array($status, enquiry_statuses(), true)) {
        return false;
    }

    db()->prepare(
        'UPDATE enquiries SET status = :status, admin_note = :admin_note, updated_at = NOW() WHERE id = :id'
    )->execute([
        'id'         => $id,
        'status'     => $status,
        'admin_note' => trim($adminNote) !== '' ? trim($adminNote) : null,
    ]);

    return true;
}

function enquiry_delete(int $id): bool
{
    $stmt = db()->prepare('DELETE FROM enquiries WHERE id = :id');
    $stmt->execute(['id' => $id]);

    return $stmt->rowCount() > 0;
}

function enquiry_count_by_status(string $status): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM enquiries WHERE status = :status');
    $stmt->execute(['status' => $status]);

    return (int) $stmt->fetchColumn();
}

function enquiry_recent(int $limit = 5): array
{
    $stmt = db()->prepare(
        'SELECT e.*, COALESCE(e.property_title, p.title) AS property_title
         FROM enquiries e
         LEFT JOIN properties p ON p.id = e.property_id
         ORDER BY e.created_at DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}
