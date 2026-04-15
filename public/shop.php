<?php
session_start();
// Ensure customer or authorized user
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "glowlinkp_db", 3308);

$products = [];
if (!$conn->connect_error) {
    $result = $conn->query("SELECT * FROM products ORDER BY id DESC");
    if ($result) {
        $products = $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GlowLink - Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { 
            background: url('https://images.unsplash.com/photo-1550684848-fac1c5b4e853?auto=format&fit=crop&w=1920&q=80') center/cover fixed; 
            min-height: 100vh; 
            color: #fff; 
            perspective: 1200px; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            padding: 40px 20px; 
        }
        body::before { 
            content: ''; 
            position: absolute; 
            inset: 0; 
            background: radial-gradient(circle at top, rgba(15, 23, 42, 0.8), rgba(0, 0, 0, 0.95)); 
            z-index: -1; 
            position: fixed;
        }

        .top-bar { display: flex; justify-content: space-between; align-items: center; width: 100%; max-width: 1200px; margin-bottom: 50px; transform: translateZ(10px); }
        .logo-text { font-size: 28px; font-weight: 700; background: linear-gradient(to right, #ec4899, #f43f5e); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0 0 10px rgba(236, 72, 153, 0.4)); display: flex; align-items: center; gap: 10px;}
        
        .nav-buttons { display: flex; gap: 15px; }

        .btn-style { display: inline-flex; align-items: center; gap: 8px; padding: 10px 25px; border-radius: 30px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; text-decoration: none; font-weight: 600; backdrop-filter: blur(10px); transition: 0.4s; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .btn-style:hover { background: rgba(255,255,255,0.2); transform: translateY(-3px) scale(1.05); }

        .page-header { text-align: center; margin-bottom: 50px; }
        .page-header h1 { font-size: 42px; font-weight: 700; margin-bottom: 15px; text-shadow: 0 5px 15px rgba(236, 72, 153, 0.4); }
        .page-header p { color: #cbd5e1; font-size: 16px; max-width: 600px; margin: 0 auto; }

        .products-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
            gap: 40px; 
            width: 100%; 
            max-width: 1200px; 
            transform-style: preserve-3d;
        }

        .product-card { 
            background: rgba(255, 255, 255, 0.03); 
            backdrop-filter: blur(15px); 
            border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 24px; 
            overflow: hidden; 
            text-align: center; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.5); 
            transform: translateZ(20px); 
            transition: 0.5s; 
            display: flex;
            flex-direction: column;
        }
        
        .product-card:hover { 
            transform: translateZ(40px) translateY(-10px); 
            border-color: rgba(236, 72, 153, 0.4); 
            box-shadow: 0 30px 60px rgba(236, 72, 153, 0.2); 
        }

        .product-image-container { 
            height: 250px; 
            padding: 30px; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            background: radial-gradient(circle at center, rgba(236, 72, 153, 0.1), transparent);
            position: relative;
        }

        .product-image-container img { 
            max-width: 100%; 
            max-height: 100%; 
            object-fit: contain; 
            filter: drop-shadow(0 15px 25px rgba(0,0,0,0.6)); 
            transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
        }
        
        .product-card:hover .product-image-container img { 
            transform: scale(1.15) rotate(5deg); 
        }

        .product-info {
            padding: 25px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-title { font-size: 20px; font-weight: 700; margin-bottom: 10px; color: #f8fafc; }
        .product-sku { font-size: 13px; color: #94a3b8; margin-bottom: 15px; }
        .product-price { font-size: 26px; font-weight: 800; color: #ec4899; margin-bottom: 25px; }

        .btn-buy { 
            display: block; 
            width: 100%; 
            padding: 14px 20px; 
            background: linear-gradient(135deg, #ec4899, #e11d48); 
            border: none;
            border-radius: 14px; 
            color: white; 
            font-weight: 700; 
            font-size: 16px;
            text-transform: uppercase; 
            letter-spacing: 1px; 
            cursor: pointer;
            transition: 0.3s; 
            box-shadow: 0 10px 20px rgba(225, 29, 72, 0.4), inset 0 -3px 0 rgba(0,0,0,0.2); 
        }
        .btn-buy:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 15px 30px rgba(225, 29, 72, 0.6), inset 0 -3px 0 rgba(0,0,0,0.2); 
        }
        .btn-buy:active {
            transform: translateY(2px);
            box-shadow: 0 5px 10px rgba(225, 29, 72, 0.4);
        }

        .badge-stock {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            backdrop-filter: blur(5px);
        }
        
        .badge-out {
            background: rgba(244, 63, 94, 0.2);
            color: #f43f5e;
            border-color: rgba(244, 63, 94, 0.3);
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 20px;
            background: rgba(255,255,255,0.02);
            border-radius: 20px;
            border: 1px dashed rgba(255,255,255,0.1);
        }
        .empty-state i {
            font-size: 60px;
            color: #ec4899;
            opacity: 0.5;
            margin-bottom: 20px;
        }

    </style>
</head>
<body>

    <div class="top-bar">
        <div class="logo-text"><i class="fa-solid fa-cube"></i> GlowLink Shop</div>
        <div class="nav-buttons">
            <a href="customer_dashboard.php" class="btn-style"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>

    <div class="page-header">
        <h1>Premium Products</h1>
        <p>Discover our exclusive range of high-quality products. Carefully curated just for you.</p>
    </div>

    <div class="products-grid">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
                <?php
                    // Handle image strings (might be JSON array from add_product.php)
                    $imgUrl = "https://via.placeholder.com/250x250?text=No+Image";
                    if (!empty($product['image'])) {
                        $decoded = json_decode($product['image'], true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && count($decoded) > 0) {
                            $imgUrl = $decoded[0];
                        } else {
                            $imgUrl = $product['image'];
                        }
                    }
                    $inStock = $product['stock'] > 0;
                ?>
                <div class="product-card">
                    <div class="product-image-container">
                        <?php if ($inStock): ?>
                            <div class="badge-stock">In Stock (<?php echo htmlspecialchars($product['stock']); ?>)</div>
                        <?php else: ?>
                            <div class="badge-stock badge-out">Out of Stock</div>
                        <?php endif; ?>
                        
                        <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='https://via.placeholder.com/250x250?text=Image+Not+Found'">
                    </div>
                    <div class="product-info">
                        <div>
                            <div class="product-sku">SKU: <?php echo htmlspecialchars($product['sku']); ?></div>
                            <h2 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h2>
                        </div>
                        <div>
                            <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                            <?php if ($inStock): ?>
                                <button class="btn-buy" onclick="buyProduct('<?php echo addslashes(htmlspecialchars($product['name'])); ?>')"><i class="fa-solid fa-cart-arrow-down"></i> Buy Now</button>
                            <?php else: ?>
                                <button class="btn-buy" style="background: #334155; box-shadow: none; cursor: not-allowed;" disabled>Out of Stock</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <h2>No products available right now</h2>
                <p style="color: #94a3b8; margin-top: 10px;">Please check back later.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function buyProduct(productName) {
            Swal.fire({
                title: 'Order Placed!',
                text: 'You have successfully purchased: ' + productName,
                icon: 'success',
                background: '#1e293b',
                color: '#fff',
                confirmButtonColor: '#ec4899',
                confirmButtonText: 'Awesome!'
            });
            // For now, this is simulated. We would typically make an AJAX request to create an order here.
        }
    </script>
</body>
</html>
