<?php
// Idempotent local preview account; never use these credentials in production.
require_once __DIR__ . '/../app/config/database.php';
$db = (new Database())->getConnection();
$db->beginTransaction();
try {
    $email = 'demo@localhost.test';
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $userId = $stmt->fetchColumn();
    if (!$userId) {
        $stmt = $db->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute(['Demo Admin', $email, password_hash('demo123', PASSWORD_DEFAULT)]);
        $userId = $db->lastInsertId();
    }
    $stmt = $db->prepare('SELECT company_id FROM user_companies WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    if (!$stmt->fetchColumn()) {
        $stmt = $db->prepare('INSERT INTO companies (name, created_by) VALUES (?, ?)');
        $stmt->execute(['Demo Company', $userId]);
        $companyId = $db->lastInsertId();
        $stmt = $db->prepare('INSERT INTO user_companies (user_id, company_id, is_default) VALUES (?, ?, 1)');
        $stmt->execute([$userId, $companyId]);
    }
    $db->commit();
} catch (Throwable $error) {
    $db->rollBack();
    throw $error;
}
