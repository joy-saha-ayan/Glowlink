<?php
session_start();

// ডেটাবেস কানেকশন
$conn = new mysqli("localhost", "root", "", "glowlinkp_db", 3308);

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // SQL ইনজেকশন থেকে বাঁচতে ডাটা স্যানিটাইজ করা হলো
    $name = $conn->real_escape_string($_POST['name']);
    $sku = $conn->real_escape_string($_POST['sku']);
    $price = $conn->real_escape_string($_POST['price']);
    $stock = $conn->real_escape_string($_POST['stock']);
    
    // একাধিক ইমেজের ডাটা হ্যান্ডেল করা
    $images = isset($_POST['images']) ? $_POST['images'] : [];
    // ফাঁকা ইনপুটগুলো বাদ দেওয়া
    $valid_images = array_filter($images, function($value) { return !is_null($value) && $value !== ''; });
    // ডাটাবেসে সেভ করার জন্য JSON ফরম্যাটে কনভার্ট করা
    $image_json = $conn->real_escape_string(json_encode(array_values($valid_images))); 

    $sql = "INSERT INTO products (name, sku, price, stock, image) VALUES ('$name', '$sku', '$price', '$stock', '$image_json')";
    
    if ($conn->query($sql) === TRUE) {
        $message = "<div class='alert success'><i class='fa-solid fa-circle-check'></i> Product added successfully!</div>";
    } else {
        $message = "<div class='alert error'><i class='fa-solid fa-circle-exclamation'></i> Error: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | GlowLink 3D</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { 
            background: radial-gradient(circle at 50% 0%, #1e293b, #0B0E14);
            color: #F8FAFC; 
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        /* 3D Glassmorphism Container */
        .form-container {
            background: rgba(21, 26, 35, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-top: 1px solid rgba(255,255,255,0.15);
            border-left: 1px solid rgba(255,255,255,0.15);
            padding: 40px;
            border-radius: 24px;
            width: 100%;
            max-width: 500px;
            box-shadow: 20px 20px 40px rgba(0,0,0,0.6), 
                        inset -5px -5px 15px rgba(0,0,0,0.3), 
                        inset 5px 5px 15px rgba(255,255,255,0.05);
            transform: perspective(1000px) rotateX(2deg);
            transition: transform 0.3s ease;
        }
        .form-container:hover {
            transform: perspective(1000px) rotateX(0deg);
        }

        .form-container h2 { 
            margin-top: 0; 
            margin-bottom: 30px; 
            font-size: 26px; 
            text-align: center;
            text-shadow: 0 4px 10px rgba(236, 72, 153, 0.5);
        }
        
        .input-group { margin-bottom: 20px; }
        .input-group label { 
            display: block; 
            font-size: 13px; 
            color: #cbd5e1; 
            margin-bottom: 8px; 
            text-transform: uppercase; 
            font-weight: 700; 
            letter-spacing: 0.5px;
        }

        /* 3D Inset Inputs */
        .input-group input { 
            width: 100%; 
            background: #0f131a; 
            border: none;
            padding: 14px; 
            border-radius: 12px; 
            color: #fff; 
            font-size: 14px; 
            outline: none; 
            box-sizing: border-box;
            box-shadow: inset 4px 4px 8px rgba(0,0,0,0.6), 
                        inset -4px -4px 8px rgba(255,255,255,0.03);
            transition: all 0.3s ease;
        }
        .input-group input:focus { 
            box-shadow: inset 4px 4px 8px rgba(0,0,0,0.8), 
                        0 0 0 2px #EC4899; 
        }

        /* Dynamic Image Row */
        .image-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .image-row input { flex: 1; }
        
        .btn-add-img {
            background: #334155;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 12px;
            cursor: pointer;
            box-shadow: 3px 3px 6px rgba(0,0,0,0.4), -2px -2px 5px rgba(255,255,255,0.05);
            transition: transform 0.1s;
        }
        .btn-add-img:active { transform: translateY(2px); box-shadow: inset 2px 2px 5px rgba(0,0,0,0.5); }
        
        .btn-remove { background: #ef4444; }

        /* 3D Submit Button */
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #EC4899, #F43F5E);
            color: #fff;
            padding: 16px;
            border: none;
            border-radius: 14px;
            font-weight: 800;
            cursor: pointer;
            font-size: 16px;
            margin-top: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 8px 0 #be185d, 0 15px 20px rgba(236, 72, 153, 0.4);
            transform: translateY(0);
            transition: all 0.15s ease;
        }
        .btn-submit:active {
            transform: translateY(8px);
            box-shadow: 0 0px 0 #be185d, 0 5px 10px rgba(236, 72, 153, 0.4);
        }

        .btn-back {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: #94A3B8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.3s;
        }
        .btn-back:hover { color: #EC4899; }

        /* Alerts */
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; text-align: center; }
        .success { background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid #10B981; }
        .error { background: rgba(244, 63, 94, 0.1); color: #fb7185; border: 1px solid #F43F5E; }
    </style>
</head>
<body>

    <div class="form-container">
        <h2><i class="fa-solid fa-cube" style="color: #EC4899;"></i> Add 3D Product</h2>
        
        <?php echo $message; ?>

        <form method="POST" action="">
            <div class="input-group">
                <label>Product Name</label>
                <input type="text" name="name" required placeholder="e.g. Premium Aloe Gel">
            </div>
            
            <div class="input-group">
                <label>SKU (Stock Keeping Unit)</label>
                <input type="text" name="sku" required placeholder="e.g. GL-ALOE-01">
            </div>
            
            <div style="display: flex; gap: 15px;">
                <div class="input-group" style="flex: 1;">
                    <label>Price ($)</label>
                    <input type="number" step="0.01" name="price" required placeholder="12.99">
                </div>
                <div class="input-group" style="flex: 1;">
                    <label>Stock Quantity</label>
                    <input type="number" name="stock" required placeholder="100">
                </div>
            </div>
            
            <div class="input-group" id="image-container">
                <label>Image URLs (Add multiple)</label>
                <div class="image-row">
                    <input type="text" name="images[]" required placeholder="Main Image URL">
                    <button type="button" class="btn-add-img" onclick="addImageField()" title="Add another image">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">Save Product</button>
        </form>
        
        <a href="products.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back to Inventory</a>
    </div>

    <script>
        function addImageField() {
            const container = document.getElementById('image-container');
            const row = document.createElement('div');
            row.className = 'image-row';
            row.innerHTML = `
                <input type="text" name="images[]" placeholder="Additional Image URL">
                <button type="button" class="btn-add-img btn-remove" onclick="this.parentElement.remove()" title="Remove this image">
                    <i class="fa-solid fa-trash"></i>
                </button>
            `;
            container.appendChild(row);
        }
    </script>
</body>
</html>