<?php
session_start();
include 'connection.php';

// Database connection setup
$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$product_id = $_GET['id'] ?? 0;
$product = null;

try {
    // 1. Fetch Product Details
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        // Handle Image properly
        $img = 'https://via.placeholder.com/400x400.png?text=No+Image';
        if (!empty($product['main_image_url']) && $product['main_image_url'] !== 'NULL') {
            $img = $product['main_image_url'];
        } elseif (!empty($product['image'])) {
            $decoded = json_decode($product['image'], true);
            $img = (is_array($decoded) && isset($decoded[0])) ? $decoded[0] : $product['image'];
        }
        $product['display_image'] = $img;
    } else {
        die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>Product not found!</h2><a href='shop.php'>Go back to Shop</a></div>");
    }

    // 2. Fetch Dynamic Reviews from Database
    $reviews = [];
    $total_rating = 0;
    
    // Check if table exists first (to prevent errors if SQL isn't run)
    $table_check = $conn->query("SHOW TABLES LIKE 'customer_reviews'");
    if($table_check && $table_check->num_rows > 0) {
        $rev_stmt = $conn->prepare("SELECT * FROM customer_reviews WHERE product_id = ? ORDER BY created_at DESC");
        $rev_stmt->bind_param("i", $product_id);
        $rev_stmt->execute();
        $rev_result = $rev_stmt->get_result();
        
        while($r = $rev_result->fetch_assoc()) {
            $reviews[] = $r;
            $total_rating += $r['rating'];
        }
    }
    
    $review_count = count($reviews);
    $avg_rating = $review_count > 0 ? round($total_rating / $review_count, 1) : 0;

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Details | GlowLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #faf9f8; font-family: sans-serif; }
    </style>
</head>
<body class="text-slate-800 p-4 md:p-8">

    <div class="max-w-5xl mx-auto space-y-8">
        
        <a href="shop.php" class="inline-flex items-center gap-2 text-slate-500 hover:text-rose-600 font-bold transition">
            <i class="fa-solid fa-arrow-left"></i> Back to Shop
        </a>

        <div class="bg-white rounded-3xl p-6 md:p-10 shadow-sm border border-gray-100 flex flex-col md:flex-row gap-10">
            
            <div class="w-full md:w-1/2 bg-slate-50 rounded-2xl flex items-center justify-center p-8 border border-slate-100 min-h-[300px]">
                <img src="<?php echo htmlspecialchars($product['display_image']); ?>" onerror="this.src='https://via.placeholder.com/400x400.png?text=Product'" class="max-w-full max-h-80 object-contain mix-blend-multiply drop-shadow-md">
            </div>

            <div class="w-full md:w-1/2 flex flex-col justify-center space-y-5">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-slate-400">
                        Brand: <span class="text-rose-500"><?php echo htmlspecialchars($product['brand'] ?? 'GlowLink'); ?></span>
                    </span>
                    <h1 class="text-3xl md:text-4xl font-black text-slate-900 mt-2"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <div class="flex items-center gap-2 mt-3">
                        <div class="flex text-amber-400 text-sm">
                            <?php 
                                for($i=1; $i<=5; $i++) {
                                    if($i <= $avg_rating) echo '<i class="fa-solid fa-star"></i>';
                                    elseif($i - 0.5 <= $avg_rating) echo '<i class="fa-solid fa-star-half-stroke"></i>';
                                    else echo '<i class="fa-regular fa-star"></i>';
                                }
                            ?>
                        </div>
                        <span class="text-sm font-bold text-slate-500">(<?php echo $avg_rating; ?> / 5) - <?php echo $review_count; ?> Reviews</span>
                    </div>

                    <p class="text-3xl font-black text-rose-600 mt-4">৳<?php echo number_format($product['price'], 2); ?></p>
                </div>

                <div class="flex items-center gap-4 text-sm font-bold text-slate-600">
                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full"><i class="fa-solid fa-check-circle mr-1"></i> In Stock (<?php echo htmlspecialchars($product['stock'] ?? '10'); ?>)</span>
                    <span>SKU: <?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></span>
                </div>

                <?php if(!empty($product['ingredients_list']) && $product['ingredients_list'] !== 'NULL'): ?>
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 text-sm text-slate-600">
                    <p class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-flask text-rose-500 mr-1"></i> Key Ingredients:</p>
                    <p><?php echo htmlspecialchars($product['ingredients_list']); ?></p>
                </div>
                <?php endif; ?>

                <form action="shop_now.php" method="GET" class="pt-4">
                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                    
                    <button type="submit" class="w-full py-4 bg-slate-900 text-white rounded-xl font-bold text-lg hover:bg-rose-500 transition-colors shadow-lg flex justify-center items-center gap-2">
                        <i class="fa-solid fa-bag-shopping"></i> Shop Now
                    </button>
                    <p class="text-center text-xs text-slate-400 mt-3"><i class="fa-solid fa-shield-halved text-green-500"></i> Secure checkout available on the next page.</p>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-3xl p-6 md:p-10 shadow-sm border border-gray-100">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-black text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-comments text-rose-500"></i> Customer Reviews (<?php echo $review_count; ?>)
                </h2>
            </div>

            <div class="space-y-6">
                <?php if($review_count > 0): ?>
                    <?php foreach($reviews as $review): 
                        $date = date("M d, Y", strtotime($review['created_at']));
                    ?>
                    <div class="border-b border-slate-100 pb-6 last:border-0 last:pb-0">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-bold text-slate-800"><?php echo htmlspecialchars($review['customer_name']); ?></h4>
                                    <?php if($review['is_verified_buyer']): ?>
                                        <span class="bg-blue-50 text-blue-600 text-[10px] px-2 py-0.5 rounded-full font-bold border border-blue-100"><i class="fa-solid fa-circle-check"></i> Verified Buyer</span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex text-amber-400 text-xs mt-1">
                                    <?php 
                                        for($i=1; $i<=5; $i++) {
                                            if($i <= $review['rating']) echo '<i class="fa-solid fa-star"></i>';
                                            else echo '<i class="fa-regular fa-star text-slate-300"></i>';
                                        }
                                    ?>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-slate-400"><?php echo $date; ?></span>
                        </div>
                        <p class="text-slate-600 text-sm leading-relaxed mt-2">
                            "<?php echo htmlspecialchars($review['review_text']); ?>"
                        </p>
                        
                        <div class="mt-3 flex items-center gap-4 text-xs text-slate-500 font-bold">
                            <span>Was this review helpful?</span>
                            <button class="hover:text-rose-500 transition"><i class="fa-regular fa-thumbs-up"></i> Yes (<?php echo $review['helpful_votes']; ?>)</button>
                            <button class="hover:text-slate-800 transition"><i class="fa-regular fa-thumbs-down"></i> No</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-10 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                        <i class="fa-regular fa-comment-dots text-4xl text-slate-300 mb-3 block"></i>
                        <p class="text-slate-500 font-bold">No reviews yet for this product.</p>
                        <p class="text-xs text-slate-400">Be the first to share your thoughts!</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-8 pt-6 border-t border-slate-100">
                
            </div>
        </div>

    </div>

</body>
</html>