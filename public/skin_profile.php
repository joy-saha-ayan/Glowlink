<?php
session_start();
$servername = "localhost:3308"; $username = "root"; $password = ""; $dbname = "glowlinkp_db";

// Fallback user auth
$user_id = $_SESSION['user_id'] ?? 1;
$glow_points = $_SESSION['glow_points'] ?? 1250;
$user_name = "Sarah";
$user_skin_type = "Oily / Acne-Prone"; 

try { 
    $conn = new mysqli($servername, $username, $password, $dbname); 
    // Data fetch logic (already setup in your previous code)
} catch (Exception $e) {}

$current_page = 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Skin Profile | GlowLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #faf9f8; font-family: sans-serif; }
        .glass-card { background: white; border: 1px solid #f1f5f9; box-shadow: 0 4px 20px rgba(0,0,0,0.03); transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .glass-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.06); }
        
        /* Custom scrollbar for table */
        .table-scroll::-webkit-scrollbar { height: 6px; }
        .table-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800">
    
    <aside class="w-72 bg-white border-r border-gray-100 flex flex-col shadow-sm z-20">
        <div class="p-8">
            <div class="text-3xl font-serif font-bold text-slate-900 mb-1">Glow<span class="text-rose-500 italic">Link</span></div>
            <div class="text-xs font-bold uppercase tracking-widest text-slate-400">Skincare Universe</div>
        </div>
        <nav class="space-y-2 px-4 flex-1 mt-4">
            <a href="customer_dashboard.php" class="flex items-center gap-4 px-4 py-3.5 <?php echo $current_page == 'dashboard' ? 'text-rose-600 bg-rose-50' : 'text-slate-500 hover:bg-slate-50'; ?> rounded-2xl font-bold"> <i class="fa-solid fa-border-all text-lg w-5"></i> Dashboard </a>
            <a href="skin_profile.php" class="flex items-center gap-4 px-4 py-3.5 <?php echo $current_page == 'profile' ? 'text-rose-600 bg-rose-50' : 'text-slate-500 hover:bg-slate-50'; ?> rounded-2xl font-bold"> <i class="fa-solid fa-sparkles text-lg w-5"></i> My Skin Profile </a>
            <a href="shop.php" class="flex items-center gap-4 px-4 py-3.5 <?php echo $current_page == 'shop' ? 'text-rose-600 bg-rose-50' : 'text-slate-500 hover:bg-slate-50'; ?> rounded-2xl font-bold"> <i class="fa-solid fa-bag-shopping text-lg w-5"></i> Shop Products </a>
            <a href="my_orders.php" class="flex items-center gap-4 px-4 py-3.5 <?php echo $current_page == 'orders' ? 'text-rose-600 bg-rose-50' : 'text-slate-500 hover:bg-slate-50'; ?> rounded-2xl font-bold"> <i class="fa-solid fa-box-open text-lg w-5"></i> My Orders </a>
            <a href="rewards.php" class="flex items-center justify-between px-4 py-3.5 <?php echo $current_page == 'rewards' ? 'text-rose-600 bg-rose-50' : 'text-slate-500 hover:bg-slate-50'; ?> rounded-2xl font-bold"> <div><i class="fa-solid fa-gift text-lg w-5 mr-3"></i> Rewards</div> <span class="bg-amber-100 text-amber-600 text-xs px-2 py-1 rounded-full font-black"><?php echo $glow_points; ?></span> </a>
        </nav>
        <div class="p-6 border-t border-gray-100">
            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-500 font-bold hover:bg-red-50 rounded-xl"> <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout </a>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto p-8 lg:p-12">
        <div class="max-w-6xl mx-auto space-y-10">
            
            <div class="flex justify-between items-end">
                <div>
                    <h1 class="text-3xl font-serif font-bold text-slate-900 mb-2">Skin Profile ✨</h1>
                    <p class="text-slate-500">Personalized insights and routines for <span class="font-bold text-slate-700"><?php echo htmlspecialchars($user_name); ?></span>.</p>
                </div>
            </div>

            <div class="bg-white rounded-3xl p-8 shadow-sm border border-gray-100">
                
                <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Master Product Finder</h2>
                        <p class="text-sm text-slate-500 mt-1">Search your skin or hair type to find the best match.</p>
                    </div>
                    
                    <div class="relative w-full md:w-96">
                        <i class="fa-solid fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="liveSearch" onkeyup="filterDataset()" placeholder="Search 'Oily', 'Dandruff', 'Dry'..." 
                               class="w-full bg-slate-50 border border-gray-200 text-slate-900 text-sm rounded-xl focus:ring-rose-500 focus:border-rose-500 block pl-10 p-3.5 transition-colors">
                    </div>
                </div>

                <div class="overflow-x-auto table-scroll">
                    <table class="w-full text-left border-collapse min-w-[800px]" id="datasetTable">
                        <thead>
                            <tr class="bg-slate-50 border-b border-gray-100">
                                <th class="p-4 font-bold text-slate-600 rounded-tl-xl">Category</th>
                                <th class="p-4 font-bold text-slate-600">Type / Condition</th>
                                <th class="p-4 font-bold text-slate-600">Recommended Product</th>
                                <th class="p-4 font-bold text-slate-600">Key Ingredients</th>
                                <th class="p-4 font-bold text-slate-600 text-right rounded-tr-xl">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm text-slate-700">
                            
                            <tr class="border-b border-gray-50 hover:bg-slate-50 transition-colors data-row">
                                <td class="p-4 font-bold text-rose-500"><i class="fa-solid fa-face-smile mr-2"></i>Face Care</td>
                                <td class="p-4 font-bold text-amber-600"><span class="bg-amber-50 px-3 py-1 rounded-full">Oily / Acne-Prone</span></td>
                                <td class="p-4 font-bold text-slate-900">Salicylic Acid Face Wash</td>
                                <td class="p-4 text-slate-500">2% Salicylic Acid, Tea Tree, Zinc</td>
                                <td class="p-4 text-right"><button onclick="window.location.href='shop.php'" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-rose-500 transition">Buy Now</button></td>
                            </tr>
                            
                            <tr class="border-b border-gray-50 hover:bg-slate-50 transition-colors data-row">
                                <td class="p-4 font-bold text-rose-500"><i class="fa-solid fa-face-smile mr-2"></i>Face Care</td>
                                <td class="p-4 font-bold text-blue-500"><span class="bg-blue-50 px-3 py-1 rounded-full">Dry / Dehydrated</span></td>
                                <td class="p-4 font-bold text-slate-900">Hydrating Cream Cleanser</td>
                                <td class="p-4 text-slate-500">Hyaluronic Acid, Ceramides, Glycerin</td>
                                <td class="p-4 text-right"><button onclick="window.location.href='shop.php'" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-rose-500 transition">Buy Now</button></td>
                            </tr>

                            <tr class="border-b border-gray-50 hover:bg-slate-50 transition-colors data-row">
                                <td class="p-4 font-bold text-rose-500"><i class="fa-solid fa-face-smile mr-2"></i>Face Care</td>
                                <td class="p-4 font-bold text-green-500"><span class="bg-green-50 px-3 py-1 rounded-full">Sensitive / Redness</span></td>
                                <td class="p-4 font-bold text-slate-900">Soothing Aloe Vera Wash</td>
                                <td class="p-4 text-slate-500">Centella Asiatica (Cica), Aloe Vera</td>
                                <td class="p-4 text-right"><button onclick="window.location.href='shop.php'" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-rose-500 transition">Buy Now</button></td>
                            </tr>

                            <tr class="border-b border-gray-50 hover:bg-slate-50 transition-colors data-row">
                                <td class="p-4 font-bold text-rose-500"><i class="fa-solid fa-face-smile mr-2"></i>Face Care</td>
                                <td class="p-4 font-bold text-purple-500"><span class="bg-purple-50 px-3 py-1 rounded-full">Combination Skin</span></td>
                                <td class="p-4 font-bold text-slate-900">Gentle Foaming Cleanser</td>
                                <td class="p-4 text-slate-500">Niacinamide, Green Tea Extract</td>
                                <td class="p-4 text-right"><button onclick="window.location.href='shop.php'" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-rose-500 transition">Buy Now</button></td>
                            </tr>

                            <tr class="border-b border-gray-50 hover:bg-slate-50 transition-colors data-row">
                                <td class="p-4 font-bold text-rose-500"><i class="fa-solid fa-face-smile mr-2"></i>Face Care</td>
                                <td class="p-4 font-bold text-orange-500"><span class="bg-orange-50 px-3 py-1 rounded-full">Dull / Uneven Tone</span></td>
                                <td class="p-4 font-bold text-slate-900">Vitamin C Brightening Wash</td>
                                <td class="p-4 text-slate-500">Vitamin C, Lemon Extract, Kojic Acid</td>
                                <td class="p-4 text-right"><button onclick="window.location.href='shop.php'" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-rose-500 transition">Buy Now</button></td>
                            </tr>

                            <tr class="border-b border-gray-50 hover:bg-slate-50 transition-colors data-row bg-slate-50/30">
                                <td class="p-4 font-bold text-indigo-500"><i class="fa-solid fa-spray-can mr-2"></i>Hair Care</td>
                                <td class="p-4 font-bold text-slate-700"><span class="bg-slate-200 px-3 py-1 rounded-full">Oily Scalp / Dandruff</span></td>
                                <td class="p-4 font-bold text-slate-900">Clarifying Tea Tree Shampoo</td>
                                <td class="p-4 text-slate-500">Ketoconazole, Tea Tree Oil, Mint</td>
                                <td class="p-4 text-right"><button onclick="window.location.href='shop.php'" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-rose-500 transition">Buy Now</button></td>
                            </tr>

                            <tr class="border-b border-gray-50 hover:bg-slate-50 transition-colors data-row bg-slate-50/30">
                                <td class="p-4 font-bold text-indigo-500"><i class="fa-solid fa-spray-can mr-2"></i>Hair Care</td>
                                <td class="p-4 font-bold text-yellow-600"><span class="bg-yellow-50 px-3 py-1 rounded-full">Dry / Frizzy Hair</span></td>
                                <td class="p-4 font-bold text-slate-900">Argan Oil Hydrating Shampoo</td>
                                <td class="p-4 text-slate-500">Argan Oil, Shea Butter, Vitamin E</td>
                                <td class="p-4 text-right"><button onclick="window.location.href='shop.php'" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-rose-500 transition">Buy Now</button></td>
                            </tr>

                            <tr class="border-b border-gray-50 hover:bg-slate-50 transition-colors data-row bg-slate-50/30">
                                <td class="p-4 font-bold text-indigo-500"><i class="fa-solid fa-spray-can mr-2"></i>Hair Care</td>
                                <td class="p-4 font-bold text-red-500"><span class="bg-red-50 px-3 py-1 rounded-full">Hair Fall / Thinning</span></td>
                                <td class="p-4 font-bold text-slate-900">Strengthening Biotin Shampoo</td>
                                <td class="p-4 text-slate-500">Biotin, Keratin, Caffeine Extract</td>
                                <td class="p-4 text-right"><button onclick="window.location.href='shop.php'" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-rose-500 transition">Buy Now</button></td>
                            </tr>

                            <tr class="border-b border-gray-50 hover:bg-slate-50 transition-colors data-row bg-slate-50/30">
                                <td class="p-4 font-bold text-indigo-500"><i class="fa-solid fa-spray-can mr-2"></i>Hair Care</td>
                                <td class="p-4 font-bold text-pink-500"><span class="bg-pink-50 px-3 py-1 rounded-full">Color-Treated / Damaged</span></td>
                                <td class="p-4 font-bold text-slate-900">Sulfate-Free Repair Shampoo</td>
                                <td class="p-4 text-slate-500">Amino Acids, Silk Protein, No Sulfates</td>
                                <td class="p-4 text-right"><button onclick="window.location.href='shop.php'" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-rose-500 transition">Buy Now</button></td>
                            </tr>

                            <tr class="hover:bg-slate-50 transition-colors data-row bg-slate-50/30">
                                <td class="p-4 font-bold text-indigo-500"><i class="fa-solid fa-spray-can mr-2"></i>Hair Care</td>
                                <td class="p-4 font-bold text-teal-600"><span class="bg-teal-50 px-3 py-1 rounded-full">Normal / Everyday Use</span></td>
                                <td class="p-4 font-bold text-slate-900">Daily Mild Cleansing Shampoo</td>
                                <td class="p-4 text-slate-500">Apple Cider Vinegar, Aloe Vera</td>
                                <td class="p-4 text-right"><button onclick="window.location.href='shop.php'" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-rose-500 transition">Buy Now</button></td>
                            </tr>

                        </tbody>
                    </table>
                    
                    <div id="noMatchMsg" class="hidden text-center p-8">
                        <i class="fa-solid fa-face-frown text-4xl text-slate-300 mb-3"></i>
                        <p class="text-slate-500 font-bold">No exact match found.</p>
                        <p class="text-sm text-slate-400">Try searching for keywords like 'Dry', 'Oily', or 'Hair Fall'.</p>
                    </div>

                </div>
            </div>

        </div>
    </main>

    <script>
        function filterDataset() {
            // Get search input value
            let input = document.getElementById('liveSearch').value.toLowerCase();
            let rows = document.querySelectorAll('.data-row');
            let matchCount = 0;
            let noMatchMsg = document.getElementById('noMatchMsg');

            // Loop through all table rows
            rows.forEach(row => {
                // Get all text content from the row
                let rowText = row.innerText.toLowerCase();
                
                // If the row contains the search keyword, show it, else hide it
                if(rowText.includes(input)) {
                    row.style.display = ''; // Shows the row
                    matchCount++;
                } else {
                    row.style.display = 'none'; // Hides the row
                }
            });

            // Show 'No Match' message if nothing was found
            if(matchCount === 0) {
                noMatchMsg.classList.remove('hidden');
            } else {
                noMatchMsg.classList.add('hidden');
            }
        }
    </script>
</body>
</html>