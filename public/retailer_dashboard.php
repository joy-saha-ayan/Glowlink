<?php
// glowlinkp/public/retailer_dashboard.php
session_start();

// 1. Security Check: Only Retailers allowed
if(!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'retailer') {
    header("Location: login.php");
    exit();
}

require_once '../config/Database.php';

$message = '';
$database = new Database();
$db = $database->getConnection();

// 2. Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    
    $name = $_POST['product_name'];
    $brand = $_POST['brand'];
    $ingredients = $_POST['ingredients'];
    $price = $_POST['price'];
    $product_link = $_POST['product_link'];
    $retailer_id = $_SESSION['user_id'];

    try {
        // Start a database transaction so both tables update together safely
        $db->beginTransaction();

        // A. Insert into the Master Products table
        $query1 = "INSERT INTO products (name, brand, ingredients_list) VALUES (:name, :brand, :ingredients)";
        $stmt1 = $db->prepare($query1);
        $stmt1->bindParam(':name', $name);
        $stmt1->bindParam(':brand', $brand);
        $stmt1->bindParam(':ingredients', $ingredients);
        $stmt1->execute();
        
        $product_id = $db->lastInsertId(); // Get the ID of the product we just added

        // B. Insert into the Retailer Prices table
        $query2 = "INSERT INTO retailer_prices (retailer_id, product_id, current_price, product_link) 
                   VALUES (:retailer_id, :product_id, :price, :link)";
        $stmt2 = $db->prepare($query2);
        $stmt2->bindParam(':retailer_id', $retailer_id);
        $stmt2->bindParam(':product_id', $product_id);
        $stmt2->bindParam(':price', $price);
        $stmt2->bindParam(':link', $product_link);
        $stmt2->execute();

        $db->commit();
        $message = "<div class='success-alert'><i class='fa-solid fa-check-circle'></i> Product added successfully to the GlowLink ecosystem!</div>";
        
    } catch (Exception $e) {
        $db->rollBack();
        $message = "<div class='error-alert'><i class='fa-solid fa-triangle-exclamation'></i> Failed to add product: " . $e->getMessage() . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GlowLink - Retailer Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f4f7fa;
            margin: 0;
            padding: 20px;
            font-family: 'Poppins', sans-serif;
            color: #333;
        }
        .dashboard-wrapper { max-width: 1000px; margin: 0 auto; }
        
        .top-nav {
            display: flex; justify-content: space-between; align-items: center;
            background: #2c3e50; color: white; padding: 15px 30px;
            border-radius: 10px; margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .top-nav h1 { margin: 0; font-size: 22px; }
        .logout-btn { color: white; text-decoration: none; font-weight: bold; }
        
        .business-panel {
            background: white; padding: 30px; border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .business-panel h2 { margin-top: 0; color: #34495e; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .full-width { grid-column: span 2; }
        
        .input-group { display: flex; flex-direction: column; }
        .input-group label { font-weight: 600; margin-bottom: 5px; font-size: 14px; color: #555; }
        .input-group input, .input-group textarea {
            padding: 12px; border: 1px solid #ccc; border-radius: 8px;
            font-family: inherit; transition: 0.3s; outline: none;
        }
        .input-group input:focus, .input-group textarea:focus { border-color: #3498db; }
        
        .submit-btn {
            background: #27ae60; color: white; border: none; padding: 15px;
            border-radius: 8px; font-weight: bold; cursor: pointer;
            font-size: 16px; margin-top: 20px; transition: 0.3s;
            width: 100%;
        }
        .submit-btn:hover { background: #219653; }
        
        .success-alert { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .error-alert { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>

    <div class="dashboard-wrapper">
        <header class="top-nav">
            <h1><i class="fa-solid fa-store"></i> Retailer Portal</h1>
            <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </header>

        <div class="business-panel">
            <h2><i class="fa-solid fa-plus-circle" style="color: #3498db;"></i> Add New Skincare Product</h2>
            <p style="color: #777; font-size: 14px;">Upload your inventory so GlowLink's AI can recommend your products to consumers.</p>
            
            <?php echo $message; ?>

            <form action="retailer_dashboard.php" method="POST">
                <div class="form-grid">
                    <div class="input-group">
                        <label>Product Name</label>
                        <input type="text" name="product_name" required placeholder="e.g. Hydrating Cleanser">
                    </div>
                    
                    <div class="input-group">
                        <label>Brand</label>
                        <input type="text" name="brand" required placeholder="e.g. CeraVe">
                    </div>

                    <div class="input-group full-width">
                        <label>Key Ingredients (Crucial for AI matching!)</label>
                        <textarea name="ingredients" rows="3" required placeholder="e.g. Hyaluronic Acid, Ceramides, Glycerin..."></textarea>
                    </div>

                    <div class="input-group">
                        <label>Your Price ($)</label>
                        <input type="number" step="0.01" name="price" required placeholder="12.99">
                    </div>

                    <div class="input-group">
                        <label>Product Link (Where users go to buy)</label>
                        <input type="url" name="product_link" required placeholder="https://yourstore.com/product">
                    </div>
                </div>

                <button type="submit" name="add_product" class="submit-btn"><i class="fa-solid fa-cloud-arrow-up"></i> Publish Product to Network</button>
            </form>
        </div>
    </div>

</body>
</html>