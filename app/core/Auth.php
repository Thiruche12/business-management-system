<?php
class Auth
{
    public function __construct(private PDO $db)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }

        $stmt = $this->db->prepare('SELECT c.id, c.name FROM companies c JOIN user_companies uc ON uc.company_id = c.id WHERE uc.user_id = ? ORDER BY uc.is_default DESC LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        $company = $stmt->fetch();
        if (!$company) {
            http_response_code(403);
            exit('No company assigned to this account.');
        }
        $_SESSION['company_id'] = $company['id'];
        $_SESSION['company_name'] = $company['name'];
    }
}
