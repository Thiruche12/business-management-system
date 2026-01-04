<?php
require_once 'app/config/config.php';
require_once 'app/config/database.php';
require_once 'app/core/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireAuth();

// Get dashboard stats
$company_id = $_SESSION['company_id'];

// Total Sales (Today)
$sales_query = "SELECT SUM(total_amount) as total_sales FROM sales_invoices 
                WHERE company_id = :company_id AND DATE(invoice_date) = CURDATE()";
$sales_stmt = $db->prepare($sales_query);
$sales_stmt->bindParam(':company_id', $company_id);
$sales_stmt->execute();
$sales_data = $sales_stmt->fetch(PDO::FETCH_ASSOC);
$total_sales = $sales_data['total_sales'] ?? 0;

// Stock Levels
$stock_query = "SELECT COUNT(*) as low_stock FROM products 
                WHERE company_id = :company_id AND stock_quantity <= min_stock_level";
$stock_stmt = $db->prepare($stock_query);
$stock_stmt->bindParam(':company_id', $company_id);
$stock_stmt->execute();
$stock_data = $stock_stmt->fetch(PDO::FETCH_ASSOC);
$low_stock = $stock_data['low_stock'] ?? 0;

// Customers Count
$customer_query = "SELECT COUNT(*) as total_customers FROM customers 
                   WHERE company_id = :company_id";
$customer_stmt = $db->prepare($customer_query);
$customer_stmt->bindParam(':company_id', $company_id);
$customer_stmt->execute();
$customer_data = $customer_stmt->fetch(PDO::FETCH_ASSOC);
$total_customers = $customer_data['total_customers'] ?? 0;

// Recent Sales
$recent_sales_query = "SELECT si.*, c.customer_name 
                       FROM sales_invoices si 
                       LEFT JOIN customers c ON si.customer_id = c.id 
                       WHERE si.company_id = :company_id 
                       ORDER BY si.created_at DESC LIMIT 5";
$recent_sales_stmt = $db->prepare($recent_sales_query);
$recent_sales_stmt->bindParam(':company_id', $company_id);
$recent_sales_stmt->execute();
$recent_sales = $recent_sales_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Business Management System</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
            --dark-color: #2b2d42;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .bg-gradient-sales {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .bg-gradient-stock {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .bg-gradient-customers {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .bg-gradient-suppliers {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3">
                    <h4 class="text-center mb-4">
                        <i class="fas fa-chart-line"></i> BMS Pro
                    </h4>
                    <div class="text-center mb-4">
                        <div class="bg-white rounded-circle d-inline-flex p-2">
                            <i class="fas fa-building text-primary fa-2x"></i>
                        </div>
                        <h6 class="mt-2"><?php echo $_SESSION['company_name']; ?></h6>
                        <small><?php echo $_SESSION['user_role']; ?></small>
                    </div>
                </div>
                
                <nav class="nav flex-column">
                    <a class="nav-link active" href="index.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="pos.php">
                        <i class="fas fa-cash-register me-2"></i> POS
                    </a>
                    <a class="nav-link" href="sales.php">
                        <i class="fas fa-shopping-cart me-2"></i> Sales
                    </a>
                    <a class="nav-link" href="products.php">
                        <i class="fas fa-boxes me-2"></i> Inventory
                    </a>
                    <a class="nav-link" href="customers.php">
                        <i class="fas fa-users me-2"></i> Customers
                    </a>
                    <a class="nav-link" href="suppliers.php">
                        <i class="fas fa-truck me-2"></i> Suppliers
                    </a>
                    <a class="nav-link" href="purchases.php">
                        <i class="fas fa-shopping-bag me-2"></i> Purchases
                    </a>
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar me-2"></i> Reports
                    </a>
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog me-2"></i> Settings
                    </a>
                    <hr class="bg-light">
                    <div class="p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-user-circle fa-2x"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <small>Logged in as</small>
                                <div><?php echo $_SESSION['user_name']; ?></div>
                            </div>
                        </div>
                        <a href="logout.php" class="btn btn-sm btn-outline-light w-100 mt-3">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                    </div>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4">
                <!-- Top Bar -->
                <nav class="navbar navbar-light bg-white py-3">
                    <div class="container-fluid">
                        <h4 class="mb-0">
                            <i class="fas fa-tachometer-alt text-primary me-2"></i> Dashboard
                        </h4>
                        <div class="d-flex">
                            <span class="badge bg-primary me-3">
                                <i class="fas fa-calendar me-1"></i> <?php echo date('F j, Y'); ?>
                            </span>
                            <button class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> New Invoice
                            </button>
                        </div>
                    </div>
                </nav>
                
                <!-- Stats Cards -->
                <div class="row mt-4">
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card text-white bg-gradient-sales">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Today's Sales</h6>
                                        <h2>$<?php echo number_format($total_sales, 2); ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-dollar-sign"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <small><i class="fas fa-calendar me-1"></i> <?php echo date('M j, Y'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card text-white bg-gradient-stock">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Low Stock Items</h6>
                                        <h2><?php echo $low_stock; ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <a href="#" class="text-white text-decoration-none">
                                        <small>View Details <i class="fas fa-arrow-right ms-1"></i></small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card text-white bg-gradient-customers">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Customers</h6>
                                        <h2><?php echo $total_customers; ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <a href="customers.php" class="text-white text-decoration-none">
                                        <small>Manage Customers <i class="fas fa-arrow-right ms-1"></i></small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card text-white bg-gradient-suppliers">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Active Suppliers</h6>
                                        <h2>24</h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-truck"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <a href="#" class="text-white text-decoration-none">
                                        <small>View Suppliers <i class="fas fa-arrow-right ms-1"></i></small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts and Recent Activity -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Sales Overview (Last 7 Days)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="salesChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Recent Sales</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php foreach($recent_sales as $sale): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong>INV-<?php echo $sale['invoice_number']; ?></strong>
                                                <div class="small"><?php echo $sale['customer_name']; ?></div>
                                            </div>
                                            <div class="text-end">
                                                <div class="text-success">$<?php echo number_format($sale['total_amount'], 2); ?></div>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($sale['created_at'])); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-2 mb-3">
                                        <a href="pos.php" class="btn btn-outline-primary btn-lg rounded-circle p-3">
                                            <i class="fas fa-cash-register fa-2x"></i>
                                        </a>
                                        <div class="mt-2">POS</div>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <a href="products.php?action=add" class="btn btn-outline-success btn-lg rounded-circle p-3">
                                            <i class="fas fa-box fa-2x"></i>
                                        </a>
                                        <div class="mt-2">Add Product</div>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <a href="customers.php?action=add" class="btn btn-outline-info btn-lg rounded-circle p-3">
                                            <i class="fas fa-user-plus fa-2x"></i>
                                        </a>
                                        <div class="mt-2">Add Customer</div>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <a href="purchases.php?action=create" class="btn btn-outline-warning btn-lg rounded-circle p-3">
                                            <i class="fas fa-clipboard-list fa-2x"></i>
                                        </a>
                                        <div class="mt-2">Create PO</div>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <a href="reports.php" class="btn btn-outline-danger btn-lg rounded-circle p-3">
                                            <i class="fas fa-chart-pie fa-2x"></i>
                                        </a>
                                        <div class="mt-2">Reports</div>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <a href="settings.php" class="btn btn-outline-secondary btn-lg rounded-circle p-3">
                                            <i class="fas fa-cog fa-2x"></i>
                                        </a>
                                        <div class="mt-2">Settings</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Sales Chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Sales ($)',
                    data: [1200, 1900, 3000, 5000, 2000, 3000, 4500],
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        // Auto-refresh dashboard every 60 seconds
        setTimeout(function() {
            location.reload();
        }, 60000);
    </script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>