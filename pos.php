<?php
// Similar authentication setup as dashboard
require_once 'app/config/config.php';
require_once 'app/config/database.php';
require_once 'app/core/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->requireAuth();

$company_id = $_SESSION['company_id'];

// Handle POS actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_to_cart'])) {
        // Add product to cart
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        
        // Get product details
        $query = "SELECT * FROM products WHERE id = :id AND company_id = :company_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $product_id);
        $stmt->bindParam(':company_id', $company_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Initialize cart if not exists
            if (!isset($_SESSION['pos_cart'])) {
                $_SESSION['pos_cart'] = [];
            }
            
            // Add to cart
            if (isset($_SESSION['pos_cart'][$product_id])) {
                $_SESSION['pos_cart'][$product_id]['quantity'] += $quantity;
            } else {
                $_SESSION['pos_cart'][$product_id] = [
                    'product_id' => $product_id,
                    'name' => $product['product_name'],
                    'price' => $product['selling_price'],
                    'quantity' => $quantity,
                    'discount' => 0
                ];
            }
        }
    }
    
    if (isset($_POST['process_sale'])) {
        // Process the complete sale
        // This would create invoice, update stock, etc.
        // Implementation depends on your complete workflow
    }
}

// Get products for search
$products_query = "SELECT * FROM products WHERE company_id = :company_id AND is_active = 1 ORDER BY product_name";
$products_stmt = $db->prepare($products_query);
$products_stmt->bindParam(':company_id', $company_id);
$products_stmt->execute();
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
?>