<?php
// Installation script for setting up database and admin user
if (file_exists('app/config/config.php')) {
    die('System already installed!');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'];
    $dbname = $_POST['dbname'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $admin_email = $_POST['admin_email'];
    $admin_password = $_POST['admin_password'];
    $admin_name = $_POST['admin_name'];
    
    try {
        // Create database connection
        $conn = new PDO("mysql:host=$host", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database
        $conn->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
        $conn->exec("USE `$dbname`");
        
        // Read and execute SQL file
        $sql = file_get_contents('database/schema.sql');
        $conn->exec($sql);
        
        // Create admin user
        $hashed_password = password_hash($admin_password, PASSWORD_BCRYPT);
        
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute([$admin_name, $admin_email, $hashed_password]);
        
        // Create config file
        $config_template = file_get_contents('app/config/config.template.php');
        $config_content = str_replace(
            ['{{DB_HOST}}', '{{DB_NAME}}', '{{DB_USER}}', '{{DB_PASS}}'],
            [$host, $dbname, $username, $password],
            $config_template
        );
        
        file_put_contents('app/config/config.php', $config_content);
        
        echo "Installation successful! <a href='login.php'>Go to Login</a>";
        exit();
        
    } catch(PDOException $e) {
        $error = "Installation failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Install BMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h1 class="text-center mb-4">Install Business Management System</h1>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <h4>Database Configuration</h4>
                <div class="mb-3">
                    <label>Database Host</label>
                    <input type="text" name="host" class="form-control" value="localhost" required>
                </div>
                <div class="mb-3">
                    <label>Database Name</label>
                    <input type="text" name="dbname" class="form-control" value="business_management_system" required>
                </div>
                <div class="mb-3">
                    <label>Database Username</label>
                    <input type="text" name="username" class="form-control" value="root" required>
                </div>
                <div class="mb-3">
                    <label>Database Password</label>
                    <input type="password" name="password" class="form-control">
                </div>
                
                <h4 class="mt-4">Admin Account</h4>
                <div class="mb-3">
                    <label>Admin Name</label>
                    <input type="text" name="admin_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Admin Email</label>
                    <input type="email" name="admin_email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Admin Password</label>
                    <input type="password" name="admin_password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg w-100">Install Now</button>
            </form>
        </div>
    </div>
</body>
</html>