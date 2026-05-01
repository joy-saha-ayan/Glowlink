<?php
session_start();
include 'connection.php'; 
mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, $port);

$admin_id = $_SESSION['admin_id'] ?? 1; 

if(isset($_POST['save_product'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $brand = $conn->real_escape_string($_POST['brand']);
    $category = $conn->real_escape_string($_POST['category']);
    $price = $conn->real_escape_string($_POST['price']);
    $stock = $conn->real_escape_string($_POST['stock']);
    $sku = $conn->real_escape_string($_POST['sku']);
    $ingredients = $conn->real_escape_string($_POST['ingredients']);
    
    $uploaded_images = [];
    $upload_dir = 'uploads/';
    
    if(!is_dir($upload_dir)){
        mkdir($upload_dir, 0777, true);
    }
    
    if(isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $file_count = count($_FILES['images']['name']);
        
        for($i = 0; $i < $file_count; $i++) {
            $tmp_name = $_FILES['images']['tmp_name'][$i];
            $file_name = time() . "_" . rand(100, 999) . "_" . basename($_FILES['images']['name'][$i]);
            $target_file = $upload_dir . $file_name;
            
            if(move_uploaded_file($tmp_name, $target_file)) {
                $uploaded_images[] = $file_name; 
            }
        }
    }
    
    $images_string = implode(",", $uploaded_images);

    $sql = "INSERT INTO products (admin_id, name, brand, category, price, stock, sku, ingredients_list, image) 
            VALUES ('$admin_id', '$name', '$brand', '$category', '$price', '$stock', '$sku', '$ingredients', '$images_string')";
            
    if($conn->query($sql) === TRUE) {
        echo "<script>alert('Product Added Successfully with Multiple Images!'); window.location.href='products.php';</script>";
    } else {
        echo "<script>alert('Database Error: " . $conn->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | GlowLink Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-main: #0B0E14;
            --bg-panel: #151A23;
            --bg-panel-hover: #1E2532;
            --primary: #6366F1;
            --accent: #EC4899;
            --success: #10B981;
            --danger: #F43F5E;
            --warning: #F59E0B;
            --text-light: #F8FAFC;
            --text-muted: #94A3B8;
            --border-color: rgba(255, 255, 255, 0.06);
            --input-bg: rgba(255, 255, 255, 0.03);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        
        body { 
            background-color: var(--bg-main); 
            color: var(--text-light); 
            display: flex; 
            min-height: 100vh; 
            overflow-x: hidden;
            background-image: radial-gradient(circle at 15% 50%, rgba(99, 102, 241, 0.08), transparent 25%), radial-gradient(circle at 85% 30%, rgba(236, 72, 153, 0.08), transparent 25%);
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }

        .sidebar { width: 260px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 100; }
        .brand-area { height: 80px; display: flex; align-items: center; padding: 0 25px; border-bottom: 1px solid var(--border-color); }
        .brand-area h2 { font-size: 22px; font-weight: 800; letter-spacing: 0.5px; }
        .brand-area span { color: var(--accent); }
        
        .nav-menu { padding: 25px 15px; flex: 1; display: flex; flex-direction: column; gap: 8px; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 14px 20px; border-radius: 10px; color: var(--text-muted); text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.3s; }
        .nav-item i { font-size: 16px; width: 20px; text-align: center; }
        .nav-item:hover { background: rgba(255,255,255,0.03); color: var(--text-light); }
        .nav-item.active { background: rgba(236, 72, 153, 0.1); color: var(--accent); position: relative; }

        .main-wrapper { margin-left: 260px; flex: 1; display: flex; flex-direction: column; }
        .topbar { height: 80px; display: flex; justify-content: space-between; align-items: center; padding: 0 40px; background: rgba(21, 26, 35, 0.5); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 50; }
        
        .content { padding: 40px; }
        .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
        .page-header h1 { font-size: 28px; font-weight: 700; margin-bottom: 5px; }
        .page-header p { color: var(--text-muted); font-size: 14px; }
        
        .btn-back { display: flex; align-items: center; gap: 8px; background: var(--bg-panel); color: var(--text-muted); border: 1px solid var(--border-color); padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 13px; text-decoration: none; transition: 0.3s; }
        .btn-back:hover { color: var(--text-light); background: rgba(255,255,255,0.05); }

        .form-card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; padding: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group label i { margin-right: 5px; color: var(--primary); }
        
        .form-control { width: 100%; padding: 14px; border: 1px solid var(--border-color); border-radius: 10px; background: var(--input-bg); color: var(--text-light); outline: none; font-size: 14px; transition: 0.3s; }
        .form-control:focus { border-color: var(--primary); background: rgba(99, 102, 241, 0.05); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
        select.form-control { appearance: none; cursor: pointer; }
        select.form-control option { background: var(--bg-panel); color: var(--text-light); }
        textarea.form-control { resize: vertical; min-height: 80px; }

        .row-flex { display: flex; gap: 25px; }
        .row-flex .form-group { flex: 1; }

        .upload-area { border: 2px dashed rgba(99, 102, 241, 0.4); border-radius: 12px; padding: 40px 20px; text-align: center; cursor: pointer; transition: 0.3s; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; background: rgba(0,0,0,0.2); min-height: 250px; }
        .upload-area:hover { border-color: var(--primary); background: rgba(99, 102, 241, 0.05); }
        .upload-area i { color: var(--primary); font-size: 40px; margin-bottom: 15px; }
        .upload-area p { color: var(--text-muted); font-size: 14px; margin-bottom: 5px; }
        
        .image-preview-container { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; justify-content: center; width: 100%; }
        .image-preview-container img { width: 70px; height: 70px; object-fit: cover; border-radius: 8px; border: 2px solid var(--primary); box-shadow: 0 4px 10px rgba(0,0,0,0.3); }

        .btn-submit { width: 100%; background: linear-gradient(to right, #6366F1, #8B5CF6); color: white; padding: 16px; border: none; border-radius: 10px; font-size: 15px; font-weight: 700; margin-top: 30px; cursor: pointer; transition: 0.3s; display: flex; justify-content: center; align-items: center; gap: 10px; letter-spacing: 0.5px; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3); }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand-area">
            <h2>GLOW<span>LINK</span></h2>
        </div>
        <div class="nav-menu">
            <a href="retailer_dashboard.php" class="nav-item"><i class="fa-solid fa-border-all"></i> Dashboard</a>
            <a href="products.php" class="nav-item active"><i class="fa-solid fa-box-open"></i> Products</a>
            <a href="orders.php" class="nav-item"><i class="fa-solid fa-cart-shopping"></i> Orders</a>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="topbar">
            <h3 style="color: var(--text-muted); font-weight: 500;">Inventory Management</h3>
            <div style="display: flex; gap: 12px; align-items: center;">
                <span style="font-weight: 600;">Admin</span>
                <div style="width:38px; height:38px; border-radius:50%; background:linear-gradient(135deg, var(--primary), var(--accent)); display:grid; place-items:center; font-weight: bold;">A</div>
            </div>
        </header>

        <div class="content">
            <div class="page-header">
                <div>
                    <h1>Add New Product</h1>
                    <p>Upload a new skincare product to your inventory with multiple images.</p>
                </div>
                <div>
                    <a href="products.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Products</a>
                </div>
            </div>

            <div class="form-card">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        
                        <div>
                            <div class="row-flex">
                                <div class="form-group">
                                    <label><i class="fas fa-box"></i> Product Name</label>
                                    <input type="text" name="name" class="form-control" placeholder="Enter product name..." required>
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-tag"></i> Brand</label>
                                    <input type="text" name="brand" class="form-control" placeholder="e.g. CeraVe, The Ordinary" required>
                                </div>
                            </div>

                            <div class="row-flex">
                                <div class="form-group">
                                    <label><i class="fas fa-layer-group"></i> Category</label>
                                    <select name="category" class="form-control" required>
                                        <option value="" disabled selected>Select Category</option>
                                        <option value="Cleanser">Cleanser</option>
                                        <option value="Moisturizer">Moisturizer</option>
                                        <option value="Serum">Serum</option>
                                        <option value="Sunscreen">Sunscreen</option>
                                        <option value="Toner">Toner</option>
                                        <option value="Mask">Mask</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-dollar-sign"></i> Price ($)</label>
                                    <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" required>
                                </div>
                            </div>

                            <div class="row-flex">
                                <div class="form-group">
                                    <label><i class="fas fa-cubes"></i> Stock Quantity</label>
                                    <input type="number" name="stock" class="form-control" placeholder="e.g. 50" required>
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-barcode"></i> SKU</label>
                                    <input type="text" name="sku" class="form-control" placeholder="e.g. GL-CLN-001" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-flask"></i> Ingredients List</label>
                                <textarea name="ingredients" class="form-control" placeholder="Water, Glycerin, Niacinamide, Hyaluronic Acid..." required></textarea>
                            </div>
                        </div>

                        <div>
                            <label class="upload-area" for="fileInput">
                                <input type="file" name="images[]" id="fileInput" multiple="multiple" accept="image/*" style="display:none;" onchange="previewImages()" required>
                                
                                <div id="uploadText">
                                    <i class="fas fa-images"></i>
                                    <p style="font-weight: 600; color: var(--text-light);">Click to select multiple images</p>
                                    <p style="font-size: 12px;">(Hold <b>CTRL</b> or <b>CMD</b> to select many)</p>
                                </div>

                                <div class="image-preview-container" id="imagePreview"></div>
                            </label>
                        </div>

                    </div>

                    <button type="submit" name="save_product" class="btn-submit">
                        <i class="fas fa-cloud-upload-alt"></i> SAVE PRODUCT TO DATABASE
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function previewImages() {
            var previewContainer = document.getElementById('imagePreview');
            var uploadText = document.getElementById('uploadText');
            var files = document.getElementById('fileInput').files;
            
            previewContainer.innerHTML = ''; 
            
            if(files.length > 0) {
                uploadText.style.display = 'none'; 
                
                for(var i = 0; i < files.length; i++) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        var img = document.createElement('img');
                        img.src = e.target.result;
                        previewContainer.appendChild(img);
                    }
                    reader.readAsDataURL(files[i]);
                }
            } else {
                uploadText.style.display = 'block'; 
            }
        }
    </script>
</body>
</html>