<?php
session_start();

include 'connection.php';

$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$product_id = $_REQUEST['product_id'] ?? $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 1; 
$main_product = null;
$shop_products = [];
$user_address = "";

try {
    $u_cols = [];
    $u_res = $conn->query("SHOW COLUMNS FROM users");
    $addr_col = '';
    if($u_res) {
        while($row = $u_res->fetch_assoc()) { 
            $u_cols[] = strtolower($row['Field']); 
        }
        if(in_array('address', $u_cols)) $addr_col = 'address';
        elseif(in_array('location', $u_cols)) $addr_col = 'location';
    }

    if($addr_col != '') {
        $user_query = $conn->prepare("SELECT $addr_col FROM users WHERE id = ?");
        $user_query->bind_param("i", $user_id);
        $user_query->execute();
        $user_res = $user_query->get_result();
        if($user_res->num_rows > 0) {
            $u_data = $user_res->fetch_assoc();
            $user_address = $u_data[$addr_col];
        }
    }

    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $main_product = $result->fetch_assoc();
        
        $img = 'https://via.placeholder.com/400x400.png?text=No+Image';
        if (!empty($main_product['main_image_url']) && $main_product['main_image_url'] !== 'NULL') {
            $img = $main_product['main_image_url'];
        } elseif (!empty($main_product['image'])) {
            $decoded = json_decode($main_product['image'], true);
            $img = (is_array($decoded) && isset($decoded[0])) ? $decoded[0] : $main_product['image'];
        }
        $main_product['display_image'] = $img;

        $shop_query = "SELECT * FROM products WHERE id != ? AND stock > 0 LIMIT 4";
        $stmt2 = $conn->prepare($shop_query);
        $stmt2->bind_param("i", $product_id);
        $stmt2->execute();
        $shop_result = $stmt2->get_result();
        
        while($row = $shop_result->fetch_assoc()) {
            $s_img = 'https://via.placeholder.com/400x400.png?text=No+Image';
            if (!empty($row['main_image_url']) && $row['main_image_url'] !== 'NULL') {
                $s_img = $row['main_image_url'];
            } elseif (!empty($row['image'])) {
                $decoded = json_decode($row['image'], true);
                $s_img = (is_array($decoded) && isset($decoded[0])) ? $decoded[0] : $row['image'];
            }
            $row['display_image'] = $s_img;
            $shop_products[] = $row;
        }
    } else {
        die("<div style='min-height: 100vh; display:flex; align-items:center; justify-content:center; font-family:sans-serif; background:#f8fafc;'><h2 style='color:#64748b; font-size:24px;'>Product not found!</h2></div>");
    }

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo htmlspecialchars($main_product['name']); ?> | GlowLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        
        body { 
            background-color: #f8fafc; 
            font-family: 'Inter', sans-serif; 
        }
        
        .premium-shadow { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); }
        .hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .hover-lift:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04); }
        
        .payment-label input:checked + div {
            border-color: #f43f5e; 
            background-color: #fff1f2;
            color: #be123c;
            box-shadow: inset 0 0 0 1px #f43f5e, 0 4px 10px rgba(244, 63, 94, 0.1);
        }
        
        .glass-overlay { backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px); }
        
        input:focus, textarea:focus, select:focus { border-color: #f43f5e !important; box-shadow: 0 0 0 3px rgba(244, 63, 94, 0.1) !important; outline: none; }
        
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body class="text-slate-800">

    <header class="bg-white border-b border-slate-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-6 h-20 flex justify-between items-center">
            <a href="shop.php" class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-900 font-semibold transition-colors">
                <i class="fa-solid fa-arrow-left text-sm"></i> Back to Shop
            </a>
            
            <div class="flex items-center gap-4">
                <button onclick="toggleCart()" class="relative p-2.5 rounded-full hover:bg-slate-100 transition-colors text-slate-700">
                    <i class="fa-solid fa-bag-shopping text-xl"></i>
                    <span id="cart_badge" class="absolute top-1 right-1 bg-rose-500 text-white text-[10px] font-bold w-4 h-4 flex items-center justify-center rounded-full shadow-sm">1</span>
                </button>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-10 space-y-12">
        
        <div class="flex flex-col lg:flex-row gap-8 items-start">
            
            <div class="w-full lg:w-5/12 sticky top-28">
                <div class="bg-white rounded-[24px] p-6 premium-shadow border border-slate-100">
                    
                    <div class="aspect-square bg-slate-50 rounded-[16px] mb-6 flex items-center justify-center p-8 relative">
                        <span class="absolute top-4 left-4 bg-white px-3 py-1 rounded-md text-[11px] font-bold tracking-wider text-slate-500 shadow-sm border border-slate-100 uppercase">Primary Item</span>
                        <img src="<?php echo htmlspecialchars($main_product['display_image']); ?>" onerror="this.src='https://via.placeholder.com/400x400.png?text=Product'" class="w-full h-full object-contain mix-blend-multiply">
                    </div>
                    
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 leading-snug mb-2"><?php echo htmlspecialchars($main_product['name']); ?></h1>
                        <div class="flex items-end justify-between mt-4">
                            <div>
                                <p class="text-sm text-slate-500 font-medium mb-1">Unit Price</p>
                                <p class="text-2xl font-black text-rose-600">৳<?php echo number_format($main_product['price'], 2); ?></p>
                            </div>
                            
                            <div class="flex flex-col items-end">
                                <p class="text-sm text-slate-500 font-medium mb-2">Quantity</p>
                                <div class="flex items-center bg-slate-50 border border-slate-200 rounded-lg p-1 shadow-sm">
                                    <button type="button" onclick="updateCartItemQty(<?php echo $main_product['id']; ?>, -1)" class="w-8 h-8 flex items-center justify-center text-slate-500 hover:text-slate-900 hover:bg-slate-200 rounded-md transition-colors font-medium">
                                        <i class="fa-solid fa-minus text-xs"></i>
                                    </button>
                                    <span id="main_product_qty_display" class="w-12 text-center font-bold text-slate-900 text-lg">1</span>
                                    <button type="button" onclick="updateCartItemQty(<?php echo $main_product['id']; ?>, 1)" class="w-8 h-8 flex items-center justify-center text-slate-500 hover:text-slate-900 hover:bg-slate-200 rounded-md transition-colors font-medium">
                                        <i class="fa-solid fa-plus text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="w-full lg:w-7/12">
                <form id="checkoutForm" action="process_order.php" method="POST" class="bg-white rounded-[24px] p-8 premium-shadow border border-slate-100">
                    <h2 class="text-xl font-bold text-slate-900 mb-6 border-b border-slate-100 pb-4">Shipping & Payment</h2>
                    
                    <input type="hidden" name="cart_data" id="cart_data_input" value="">
                    <input type="hidden" name="total_amount" id="form_total_amount" value="">
                    
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Delivery Zone</label>
                                <div class="relative">
                                    <select id="location_select" name="delivery_zone" onchange="renderCart()" class="w-full appearance-none px-4 py-3.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 font-medium transition-all cursor-pointer">
                                        <option value="60">Inside Dhaka City (৳60)</option>
                                        <option value="100">Outside Dhaka City (৳100)</option>
                                        <option value="120">Outside Dhaka (৳120)</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                        <i class="fa-solid fa-chevron-down text-sm"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Exact Delivery Address</label>
                                <textarea name="detailed_address" rows="2" placeholder="House, Road, Block, Area..." required class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 font-medium resize-none transition-all placeholder:text-slate-400"><?php echo htmlspecialchars($user_address); ?></textarea>
                            </div>
                        </div>

                        <div class="pt-4">
                            <label class="block text-sm font-semibold text-slate-700 mb-3">Payment Method</label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <label class="cursor-pointer payment-label">
                                    <input type="radio" name="payment_method" value="cod" class="peer sr-only" checked>
                                    <div class="flex flex-col items-center justify-center p-4 rounded-xl border border-slate-200 bg-slate-50 transition-all text-slate-600 hover:bg-slate-100 h-24">
                                        <i class="fa-solid fa-hand-holding-dollar text-2xl mb-2"></i>
                                        <span class="font-semibold text-sm">Cash on Delivery</span>
                                    </div>
                                </label>
                                <label class="cursor-pointer payment-label">
                                    <input type="radio" name="payment_method" value="bkash" class="peer sr-only">
                                    <div class="flex flex-col items-center justify-center p-4 rounded-xl border border-slate-200 bg-slate-50 transition-all text-slate-600 hover:bg-slate-100 h-24">
                                        <svg class="h-6 mb-2 text-[#e2136e]" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14.5v-9l6 4.5-6 4.5z"/></svg>
                                        <span class="font-semibold text-sm">bKash</span>
                                    </div>
                                </label>
                                <label class="cursor-pointer payment-label">
                                    <input type="radio" name="payment_method" value="card" class="peer sr-only">
                                    <div class="flex flex-col items-center justify-center p-4 rounded-xl border border-slate-200 bg-slate-50 transition-all text-slate-600 hover:bg-slate-100 h-24">
                                        <i class="fa-regular fa-credit-card text-2xl mb-2 text-blue-500"></i>
                                        <span class="font-semibold text-sm">Card Payment</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="mt-8 bg-slate-50 p-6 rounded-2xl border border-slate-100">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-4">Order Summary</h3>
                            
                            <div id="checkout_items_list" class="space-y-3 mb-4 max-h-48 overflow-y-auto pr-2">
                                </div>

                            <div class="space-y-2 pt-4 border-t border-slate-200">
                                <div class="flex justify-between text-sm font-medium text-slate-600">
                                    <span>Subtotal (<span id="summary_qty">1</span> items)</span>
                                    <span class="font-semibold text-slate-800">৳<span id="summary_subtotal">0.00</span></span>
                                </div>
                                <div class="flex justify-between text-sm font-medium text-slate-600">
                                    <span>Delivery Fee</span>
                                    <span class="font-semibold text-slate-800">৳<span id="summary_delivery">60.00</span></span>
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-center mt-4 pt-4 border-t border-slate-200">
                                <span class="text-base font-bold text-slate-900">Total Payment</span>
                                <span class="text-3xl font-black text-rose-600">৳<span id="summary_total">0.00</span></span>
                            </div>
                        </div>

                        <button type="button" onclick="processCheckout()" class="w-full py-4 mt-4 bg-slate-900 text-white rounded-xl font-bold text-lg hover:bg-slate-800 transition-colors shadow-lg shadow-slate-900/20 flex justify-center items-center gap-2">
                            Place Order <i class="fa-solid fa-check"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if(!empty($shop_products)): ?>
        <div class="pt-10 border-t border-slate-200 mt-12">
            <h2 class="text-xl font-bold text-slate-900 mb-6">Frequently Bought Together</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                <?php foreach($shop_products as $sp): ?>
                <div class="bg-white border border-slate-100 p-4 rounded-2xl hover-lift premium-shadow flex flex-col group">
                    <div class="aspect-square bg-slate-50 rounded-xl mb-4 flex items-center justify-center p-4 overflow-hidden">
                        <img src="<?php echo htmlspecialchars($sp['display_image']); ?>" onerror="this.src='https://via.placeholder.com/400x400.png?text=Product'" class="w-full h-full object-contain mix-blend-multiply group-hover:scale-105 transition-transform duration-300">
                    </div>
                    <div class="flex-1 flex flex-col">
                        <h3 class="font-medium text-slate-800 text-sm mb-1 line-clamp-2 leading-snug"><?php echo htmlspecialchars($sp['name']); ?></h3>
                        <p class="text-rose-600 font-bold mb-4 mt-auto">৳<?php echo number_format($sp['price'], 2); ?></p>
                        
                        <button type="button" onclick="addToSideCart(<?php echo $sp['id']; ?>, '<?php echo addslashes($sp['name']); ?>', <?php echo $sp['price']; ?>, '<?php echo addslashes($sp['display_image']); ?>')" class="w-full py-2.5 bg-slate-50 border border-slate-200 text-slate-700 rounded-lg font-semibold text-sm hover:bg-slate-900 hover:text-white hover:border-slate-900 transition-all">
                            Add to Cart
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </main>

    <div id="cartOverlay" onclick="toggleCart()" class="fixed inset-0 bg-slate-900/30 glass-overlay z-40 hidden opacity-0 transition-opacity duration-300"></div>
    <div id="sideCart" class="fixed inset-y-0 right-0 w-full md:w-[400px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 flex flex-col">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-white">
            <h2 class="text-lg font-bold text-slate-900">Your Cart</h2>
            <button onclick="toggleCart()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-50 text-slate-500 hover:bg-slate-100 hover:text-slate-900 transition-colors"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <div id="sideCartItems" class="flex-1 overflow-y-auto p-6 space-y-4 bg-slate-50/50">
            </div>

        <div class="p-6 border-t border-slate-100 bg-white shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
            <button onclick="toggleCart()" class="w-full py-3.5 bg-slate-900 text-white rounded-xl font-semibold hover:bg-slate-800 transition-colors">
                Back to Checkout
            </button>
        </div>
    </div>

    <div id="bkashModal" class="fixed inset-0 bg-slate-900/50 glass-overlay z-[60] hidden flex items-center justify-center opacity-0 transition-opacity duration-300">
        <div class="bg-white w-full max-w-[360px] rounded-2xl overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300" id="bkashBox">
            <div class="bg-[#e2136e] pt-8 pb-6 px-6 text-center text-white relative">
                <button type="button" onclick="closeModal('bkashModal', 'bkashBox')" class="absolute top-4 right-4 text-white/80 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
                <img src="https://scripts.sandbox.bka.sh/resources/img/bkash_logo_white.png" onerror="this.style.display='none'" alt="bKash" class="h-10 mx-auto mb-3">
                <p class="text-white/90 text-sm">GlowLink Checkout</p>
            </div>
            <div class="p-6 space-y-5 bg-white">
                <div class="text-center">
                    <span class="text-slate-500 text-sm">Amount to Pay</span>
                    <div class="text-[#e2136e] text-3xl font-black mt-1">৳<span id="bkash_amount">0.00</span></div>
                </div>
                <div class="space-y-4 mt-6">
                    <div>
                        <input type="text" placeholder="bKash Account Number" class="w-full text-center p-3.5 rounded-lg border border-slate-300 focus:border-[#e2136e] focus:ring-1 focus:ring-[#e2136e] bg-slate-50 font-medium placeholder:text-slate-400 transition-all outline-none">
                    </div>
                    <div>
                        <input type="password" placeholder="Enter PIN" class="w-full text-center p-3.5 rounded-lg border border-slate-300 focus:border-[#e2136e] focus:ring-1 focus:ring-[#e2136e] bg-slate-50 font-black tracking-widest transition-all outline-none">
                    </div>
                </div>
                <button type="button" onclick="submitFinalOrder()" class="w-full py-3.5 bg-[#e2136e] text-white rounded-lg font-bold hover:bg-[#c90d5e] transition-colors mt-2">
                    Confirm Payment
                </button>
            </div>
        </div>
    </div>

    <div id="cardModal" class="fixed inset-0 bg-slate-900/50 glass-overlay z-[60] hidden flex items-center justify-center opacity-0 transition-opacity duration-300">
        <div class="bg-white w-full max-w-[400px] rounded-2xl overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300" id="cardBox">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2"><i class="fa-regular fa-credit-card text-blue-600"></i> Card Payment</h2>
                <button type="button" onclick="closeModal('cardModal', 'cardBox')" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div class="p-6 space-y-5">
                <div class="text-center mb-6">
                    <span class="text-slate-500 text-sm">Total Amount</span>
                    <div class="text-slate-900 text-3xl font-black mt-1">৳<span id="card_amount">0.00</span></div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wide">Card Number</label>
                        <input type="text" placeholder="0000 0000 0000 0000" class="w-full p-3.5 rounded-lg border border-slate-300 focus:border-blue-500 bg-slate-50 font-medium transition-all outline-none">
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wide">Expiry</label>
                            <input type="text" placeholder="MM/YY" class="w-full p-3.5 rounded-lg border border-slate-300 focus:border-blue-500 bg-slate-50 font-medium text-center transition-all outline-none">
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wide">CVC</label>
                            <input type="password" placeholder="•••" class="w-full p-3.5 rounded-lg border border-slate-300 focus:border-blue-500 bg-slate-50 font-black text-center tracking-widest transition-all outline-none">
                        </div>
                    </div>
                </div>
                <button type="button" onclick="submitFinalOrder()" class="w-full py-4 bg-slate-900 text-white rounded-xl font-bold hover:bg-slate-800 transition-colors mt-4">
                    Pay Now
                </button>
            </div>
        </div>
    </div>


    <script>
        const MAIN_PRODUCT_ID = <?php echo $main_product['id']; ?>;
        
        let cart = [{
            id: MAIN_PRODUCT_ID,
            name: "<?php echo addslashes($main_product['name']); ?>",
            price: <?php echo $main_product['price']; ?>,
            image: "<?php echo addslashes($main_product['display_image']); ?>",
            qty: 1
        }];

        function addToSideCart(id, name, price, img) {
            let existing = cart.find(item => item.id === id);
            if(existing) {
                existing.qty++;
            } else {
                cart.push({ id: id, name: name, price: price, image: img, qty: 1 });
            }
            renderCart();
            openCart();
        }

        function updateCartItemQty(id, change) {
            let itemIndex = cart.findIndex(item => item.id === id);
            
            if(itemIndex !== -1) {
                let newQty = cart[itemIndex].qty + change;
                
                if(id === MAIN_PRODUCT_ID && newQty < 1) {
                    newQty = 1; 
                }
                
                if(newQty <= 0) {
                    cart.splice(itemIndex, 1);
                } else {
                    cart[itemIndex].qty = newQty;
                }
            }
            
            if(cart.length === 0) {
                window.location.href = 'shop.php';
                return;
            }
            
            renderCart();
        }

        function renderCart() {
            let sideCartHtml = '';
            let checkoutListHtml = '';
            let subtotal = 0;
            let totalQty = 0;

            cart.forEach(item => {
                let itemTotal = item.price * item.qty;
                subtotal += itemTotal;
                totalQty += item.qty;

                if(item.id === MAIN_PRODUCT_ID) {
                    const mainQtyDisplay = document.getElementById('main_product_qty_display');
                    if(mainQtyDisplay) mainQtyDisplay.innerText = item.qty;
                }

                sideCartHtml += `
                    <div class="flex gap-4 bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
                        <div class="w-16 h-16 bg-slate-50 rounded-lg p-1.5 flex-shrink-0 border border-slate-100">
                            <img src="${item.image}" class="w-full h-full object-contain mix-blend-multiply">
                        </div>
                        <div class="flex-1 flex flex-col justify-between">
                            <h4 class="text-sm font-semibold text-slate-800 leading-snug line-clamp-2">${item.name}</h4>
                            <div class="flex justify-between items-end mt-2">
                                <p class="text-rose-600 font-bold text-sm">৳${item.price.toFixed(2)}</p>
                                <div class="flex items-center bg-slate-50 border border-slate-200 rounded-md">
                                    <button onclick="updateCartItemQty(${item.id}, -1)" class="w-7 h-7 flex items-center justify-center text-slate-500 hover:bg-slate-200 rounded-l-md font-medium"><i class="fa-solid fa-minus text-[10px]"></i></button>
                                    <span class="w-8 text-center text-xs font-bold text-slate-800">${item.qty}</span>
                                    <button onclick="updateCartItemQty(${item.id}, 1)" class="w-7 h-7 flex items-center justify-center text-slate-500 hover:bg-slate-200 rounded-r-md font-medium"><i class="fa-solid fa-plus text-[10px]"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                checkoutListHtml += `
                    <div class="flex gap-3 items-center py-2">
                        <div class="w-12 h-12 bg-white rounded-md border border-slate-100 p-1 flex-shrink-0">
                            <img src="${item.image}" class="w-full h-full object-contain">
                        </div>
                        <div class="flex-1 overflow-hidden">
                            <h4 class="text-sm font-medium text-slate-700 truncate">${item.name}</h4>
                            <p class="text-xs text-slate-500 mt-0.5">Qty: ${item.qty}</p>
                        </div>
                        <div class="text-sm font-semibold text-slate-900 text-right">
                            ৳${itemTotal.toFixed(2)}
                        </div>
                    </div>
                `;
            });

            let deliveryFee = parseFloat(document.getElementById('location_select').value);
            let finalTotal = subtotal + deliveryFee;

            document.getElementById('sideCartItems').innerHTML = sideCartHtml;
            document.getElementById('checkout_items_list').innerHTML = checkoutListHtml;
            
            document.getElementById('cart_badge').innerText = totalQty;
            
            document.getElementById('summary_qty').innerText = totalQty;
            document.getElementById('summary_subtotal').innerText = subtotal.toFixed(2);
            document.getElementById('summary_delivery').innerText = deliveryFee.toFixed(2);
            document.getElementById('summary_total').innerText = finalTotal.toFixed(2);
            
            document.getElementById('form_total_amount').value = finalTotal.toFixed(2);
            document.getElementById('cart_data_input').value = JSON.stringify(cart);
        }

        function toggleCart() {
            const cartMenu = document.getElementById('sideCart');
            if (cartMenu.classList.contains('translate-x-full')) {
                openCart();
            } else {
                closeCart();
            }
        }
        
        function openCart() {
            document.getElementById('sideCart').classList.remove('translate-x-full');
            const overlay = document.getElementById('cartOverlay');
            overlay.classList.remove('hidden');
            setTimeout(() => overlay.classList.remove('opacity-0'), 10);
        }
        
        function closeCart() {
            document.getElementById('sideCart').classList.add('translate-x-full');
            const overlay = document.getElementById('cartOverlay');
            overlay.classList.add('opacity-0');
            setTimeout(() => overlay.classList.add('hidden'), 300);
        }

        function processCheckout() {
            let method = document.querySelector('input[name="payment_method"]:checked').value;
            let total = document.getElementById('form_total_amount').value;

            if(method === 'bkash') {
                document.getElementById('bkash_amount').innerText = total;
                showModal('bkashModal', 'bkashBox');
            } 
            else if(method === 'card') {
                document.getElementById('card_amount').innerText = total;
                showModal('cardModal', 'cardBox');
            } 
            else {
                submitFinalOrder();
            }
        }

        function showModal(modalId, boxId) {
            const modal = document.getElementById(modalId);
            const box = document.getElementById(boxId);
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                box.classList.remove('scale-95');
                box.classList.add('scale-100');
            }, 10);
        }

        function closeModal(modalId, boxId) {
            const modal = document.getElementById(modalId);
            const box = document.getElementById(boxId);
            modal.classList.add('opacity-0');
            box.classList.remove('scale-100');
            box.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        function submitFinalOrder() {
            document.getElementById('checkoutForm').submit();
        }

        window.onload = function() {
            renderCart();
        };
    </script>
</body>
</html>