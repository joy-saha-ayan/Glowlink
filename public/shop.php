<?php
session_start();
include 'connection.php';
$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, $port);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$col_rp_price       = 'current_price';
$col_rp_product_id  = 'product_id';
$col_rp_retailer_id = 'retailer_id';
$col_r_id           = 'user_id';
$col_r_name         = 'company_name';

$user            = ['glow_points' => $_SESSION['glow_points'] ?? 1250];
$current_page    = 'shop';
$products        = [];
$recommended_products = [];

$search_query    = isset($_GET['search'])   ? trim($_GET['search'])   : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : 'all';

// ── Query ──────────────────────────────────────────────────────────────────────
try {
    $p_cols = [];
    $res = $conn->query("SHOW COLUMNS FROM products");
    if ($res) while ($r = $res->fetch_assoc()) $p_cols[] = $r['Field'];

    $img_raw_col  = in_array('image',          $p_cols) ? 'p.image'          : 'NULL';
    $img_main_col = in_array('main_image_url', $p_cols) ? 'p.main_image_url' : 'NULL';
    // Check if stock column exists dynamically
    $stock_col    = in_array('stock',          $p_cols) ? 'p.stock'          : '1';

    // FIX: Removed strict "p.stock > 0" requirement so all newly added items show up
    $where = ["1=1"]; 
    
    if (!empty($search_query)) {
        $es = $conn->real_escape_string($search_query);
        $where[] = "(p.name LIKE '%$es%' OR p.description LIKE '%$es%')";
    }
    if ($category_filter !== 'all') {
        $ec = $conn->real_escape_string($category_filter);
        if (in_array($category_filter, ['cleanser','Face Wash'])) {
            $where[] = "(p.name LIKE '%cleanser%' OR p.name LIKE '%wash%' OR p.name LIKE '%foam%')";
        } elseif ($category_filter === 'sunscreen') {
            $where[] = "(p.name LIKE '%sunscreen%' OR p.name LIKE '%sunblock%' OR p.name LIKE '%spf%')";
        } else {
            $where[] = "p.name LIKE '%$ec%'";
        }
    }
    $where_sql = implode(' AND ', $where);

    $sql = "SELECT p.id, p.name, p.price AS our_price,
                   $img_raw_col AS image_raw, $img_main_col AS main_image_url,
                   $stock_col AS stock
            FROM products p WHERE $where_sql GROUP BY p.id";

    $result = $conn->query($sql);

    function processImages($row) {
        $imgs = [];
        if (!empty($row['image_raw']) && $row['image_raw'] !== 'NULL') {
            $dec = json_decode($row['image_raw'], true);
            $imgs = is_array($dec) ? $dec : [$row['image_raw']];
        }
        if (empty($imgs) && !empty($row['main_image_url']) && $row['main_image_url'] !== 'NULL')
            $imgs[] = $row['main_image_url'];
        if (empty($imgs))
            $imgs[] = 'https://via.placeholder.com/400x400.png?text=No+Image';
        return $imgs;
    }

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['images_array'] = processImages($row);
            $products[] = $row;
        }
    } else {
        // FIX: Removed strict "p.stock > 0" here as well
        $fb = $conn->query("SELECT p.id, p.name, p.price AS our_price,
                $img_raw_col AS image_raw, $img_main_col AS main_image_url,
                $stock_col AS stock
            FROM products p WHERE 1=1 GROUP BY p.id ORDER BY RAND() LIMIT 4");
        if ($fb && $fb->num_rows > 0) {
            while ($r = $fb->fetch_assoc()) {
                $r['images_array'] = processImages($r);
                $recommended_products[] = $r;
            }
        }
    }
} catch (Exception $e) {
    die("DB Error: " . $e->getMessage());
}

$productsToDisplay = !empty($products) ? $products : $recommended_products;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop | GlowLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700;9..40,800&display=swap" rel="stylesheet">
    <style>
        *{font-family:'DM Sans',sans-serif;}
        .serif{font-family:'DM Serif Display',serif;}
        body{background:#f7f6f3;}
        #sidebar{transition:transform .3s ease;}
        @media(max-width:1024px){.sidebar-closed{transform:translateX(-100%)}.sidebar-open{transform:translateX(0)}}
        .no-scrollbar::-webkit-scrollbar{display:none}
        .no-scrollbar{-ms-overflow-style:none;scrollbar-width:none}
        .product-card{transition:transform .3s,box-shadow .3s}
        .product-card:hover{transform:translateY(-5px);box-shadow:0 20px 40px rgba(0,0,0,.07)}
        
        /* ── UI FIX: Added margin and border control directly into the drawer classes ── */
        .comp-drawer {
            max-height: 0;
            overflow: hidden;
            transition: max-height .4s cubic-bezier(.4,0,.2,1), opacity .3s, margin .3s, border-width .3s;
            opacity: 0;
            margin-bottom: 0; 
            border-width: 0px; 
            border-style: solid;
            border-color: #f1f5f9; 
        }
        .comp-drawer.open {
            max-height: 800px;
            opacity: 1;
            margin-bottom: 0.75rem; 
            border-width: 1px; 
        }
        
        .price-bar{height:7px;border-radius:999px;transition:width .7s cubic-bezier(.4,0,.2,1);min-width:4px}
        @keyframes sk{0%{background-position:-400px 0}100%{background-position:400px 0}}
        .skeleton{background:linear-gradient(90deg,#f0efed 25%,#e8e7e4 50%,#f0efed 75%);background-size:400px 100%;animation:sk 1.2s infinite;border-radius:6px}
        .badge-save{background:#fef2f2;color:#e11d48;border:1.5px solid #fecdd3}
        .badge-best{background:#f0fdf4;color:#16a34a;border:1.5px solid #bbf7d0}
        .retailer-row{transition:background .15s}
        .retailer-row:hover{background:#f9f8f6}
        .our-row{background:linear-gradient(135deg,#fff1f3 0%,#fdf4ff 100%)}
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 relative">

<div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-20 hidden lg:hidden"></div>

<aside id="sidebar" class="w-72 bg-white border-r border-gray-100 flex flex-col shadow-sm z-30 absolute lg:relative h-full sidebar-closed lg:translate-x-0 shrink-0">
    <div class="p-8 flex justify-between items-center">
        <div>
            <div class="text-3xl font-bold text-slate-900 serif mb-1">Glow<span class="text-rose-500 italic">Link</span></div>
            <div class="text-xs font-bold uppercase tracking-widest text-slate-400">Skincare Universe</div>
        </div>
        <button id="closeSidebarBtn" class="lg:hidden text-slate-400 hover:text-slate-700 text-xl"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <nav class="space-y-1 px-4 flex-1 mt-2">
        <?php
        $nav=[
            ['href'=>'customer_dashboard.php','key'=>'dashboard','icon'=>'fa-border-all','label'=>'Dashboard'],
            ['href'=>'skin_profile.php','key'=>'profile','icon'=>'fa-sparkles','label'=>'My Skin Profile'],
            ['href'=>'shop.php','key'=>'shop','icon'=>'fa-bag-shopping','label'=>'Shop Products'],
            ['href'=>'my_orders.php','key'=>'orders','icon'=>'fa-box-open','label'=>'My Orders'],
            ['href'=>'rewards.php','key'=>'rewards','icon'=>'fa-gift','label'=>'Rewards','badge'=>$user['glow_points']],
        ];
        foreach($nav as $n):$active=($current_page===$n['key']);?>
        <a href="<?=$n['href']?>" class="flex items-center justify-between px-4 py-3.5 rounded-2xl font-bold transition <?=$active?'text-rose-600 bg-rose-50':'text-slate-500 hover:bg-slate-50'?>">
            <div class="flex items-center gap-4"><i class="fa-solid <?=$n['icon']?> text-base w-5"></i><?=$n['label']?></div>
            <?php if(isset($n['badge'])):?><span class="bg-amber-100 text-amber-600 text-xs px-2 py-0.5 rounded-full font-black"><?=$n['badge']?></span><?php endif;?>
        </a>
        <?php endforeach;?>
    </nav>
    <div class="p-6 border-t border-gray-100">
        <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-500 font-bold hover:bg-red-50 rounded-xl"><i class="fa-solid fa-arrow-right-from-bracket"></i>Logout</a>
    </div>
</aside>

<main class="flex-1 overflow-y-auto p-4 md:p-8 lg:p-10 w-full">
<div class="max-w-7xl mx-auto space-y-7">

    <div class="flex justify-between items-center bg-white p-4 lg:p-5 rounded-3xl shadow-sm border border-gray-100">
        <div class="flex items-center gap-4">
            <button id="openSidebarBtn" class="lg:hidden text-slate-700 bg-slate-100 p-3 rounded-xl hover:bg-slate-200"><i class="fa-solid fa-bars-staggered"></i></button>
            <div>
                <h1 class="text-xl lg:text-2xl font-bold text-slate-900 serif">Smart Shop 🛒</h1>
                <p class="text-xs text-slate-500 hidden sm:flex items-center gap-1.5 mt-0.5">
                    <i class="fa-solid fa-shield-halved text-green-500"></i>
                    Live prices checked: Daraz 🛒 · Shajgoj 💄
                </p>
            </div>
        </div>
        <a href="cart.php" class="bg-slate-900 text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-rose-500 transition shadow-md flex items-center gap-2">
            <i class="fa-solid fa-cart-shopping"></i><span class="hidden sm:inline">Cart</span>
        </a>
    </div>

    <form action="shop.php" method="GET" class="bg-white p-3 rounded-2xl shadow-sm border border-gray-100 flex flex-col lg:flex-row gap-3 items-center">
        <div class="relative w-full lg:w-72 shrink-0">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input type="text" name="search" value="<?=htmlspecialchars($search_query)?>" placeholder="Search products…"
                   class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 text-sm text-slate-700 font-medium transition">
        </div>
        <div class="flex gap-2 overflow-x-auto w-full no-scrollbar pb-1 lg:pb-0">
            <?php
            $cats=['all'=>['icon'=>'','label'=>'All'],'Face Wash'=>['icon'=>'fa-pump-soap','label'=>'Face Wash'],
                   'serum'=>['icon'=>'fa-droplet','label'=>'Serum'],'toner'=>['icon'=>'fa-spray-can','label'=>'Toner'],
                   'cream'=>['icon'=>'fa-jar','label'=>'Cream'],'sunscreen'=>['icon'=>'fa-sun','label'=>'Sunscreen']];
            foreach($cats as $val=>$cat):
                $a=($category_filter===$val)||($val==='Face Wash'&&$category_filter==='cleanser');?>
            <button type="submit" name="category" value="<?=$val?>"
                    class="px-5 py-2.5 rounded-xl text-sm font-bold whitespace-nowrap transition <?=$a?'bg-slate-900 text-white':'bg-slate-50 text-slate-600 hover:bg-slate-200 border border-slate-200'?>">
                <?php if($cat['icon']):?><i class="fa-solid <?=$cat['icon']?> mr-1"></i><?php endif;?><?=$cat['label']?>
            </button>
            <?php endforeach;?>
        </div>
    </form>

    <?php if(empty($products)&&!empty($search_query)):?>
    <div class="text-center py-16 bg-white rounded-3xl border border-rose-100 shadow-sm">
        <div class="text-5xl mb-4">🥺</div>
        <h3 class="text-2xl font-bold mb-2">No products found</h3>
        <p class="text-slate-500">Nothing matched "<strong><?=htmlspecialchars($search_query)?></strong>"</p>
        <a href="shop.php" class="inline-block mt-6 px-6 py-3 bg-slate-900 text-white rounded-xl font-bold hover:bg-rose-500 transition"><i class="fa-solid fa-rotate-right mr-2"></i>Clear Filters</a>
    </div>
    <?php if(!empty($recommended_products)):?><h2 class="text-xl font-bold"><i class="fa-solid fa-fire text-rose-500 mr-2"></i>You Might Also Like</h2><?php endif;endif;?>

    <?php if(!empty($productsToDisplay)):?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <?php foreach($productsToDisplay as $p):
        $pid=$p['id'];
        $our_price=floatval($p['our_price']);
        $pname_js=htmlspecialchars(json_encode($p['name']),ENT_QUOTES);
        
        // FIX: Verify Stock Status
        $stock = isset($p['stock']) ? intval($p['stock']) : 1; 
        $in_stock = $stock > 0;
    ?>
    <div class="product-card bg-white border border-gray-100 shadow-sm rounded-3xl flex flex-col overflow-hidden relative group">

        <?php if(!$in_stock): ?>
            <div class="absolute top-3 right-3 z-10 text-[10px] font-black px-2 py-1 rounded bg-slate-800 text-white uppercase tracking-wider opacity-90 shadow">Out of Stock</div>
        <?php endif; ?>

        <div id="badge_<?=$pid?>" class="absolute top-3 left-3 z-10 text-xs font-black px-3 py-1 rounded-full border skeleton" style="min-width:88px;height:22px;"></div>

        <div class="h-48 bg-white relative overflow-hidden mt-8">
            <div class="flex overflow-x-auto snap-x snap-mandatory h-full w-full no-scrollbar">
            <?php foreach($p['images_array'] as $img):
                $src=(filter_var($img,FILTER_VALIDATE_URL)||str_starts_with($img,'http'))?$img:'uploads/'.trim($img,'[]"\' ');
            ?>
                <div class="snap-center shrink-0 w-full h-full flex items-center justify-center p-3 group-hover:scale-105 transition duration-300">
                    <img src="<?=htmlspecialchars($src)?>"
                         onerror="this.src='https://via.placeholder.com/400x400.png?text=Product'"
                         alt="<?=htmlspecialchars($p['name'])?>"
                         class="h-full w-full object-contain mix-blend-multiply <?= !$in_stock ? 'opacity-50 grayscale' : '' ?>">
                </div>
            <?php endforeach;?>
            </div>
        </div>

        <div class="p-4 flex-1 flex flex-col">
            <h3 class="font-bold text-slate-900 text-sm leading-snug mb-3 line-clamp-2 <?= !$in_stock ? 'opacity-60' : '' ?>"><?=htmlspecialchars($p['name'])?></h3>

            <div class="flex items-end gap-2 mb-0.5">
                <span class="text-rose-600 text-2xl font-black <?= !$in_stock ? 'opacity-60' : '' ?>">৳<?=number_format($our_price,0)?></span>
                <span id="slash_price_<?=$pid?>" class="text-xs text-slate-400 line-through mb-1 hidden"></span>
            </div>
            <p class="text-[10px] text-slate-400 font-semibold mb-3 uppercase tracking-wider">GlowLink Price</p>

            <button id="compbtn_<?=$pid?>"
                    onclick="toggleComp(<?=$pid?>, <?=$pname_js?>, <?=$our_price?>)"
                    class="w-full mb-3 py-2 rounded-xl border-2 border-slate-200 text-slate-600 text-xs font-bold hover:border-rose-300 hover:text-rose-600 hover:bg-rose-50 transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-chart-bar text-xs"></i> Compare Prices ▼
            </button>

            <div id="comp_<?=$pid?>" class="comp-drawer rounded-2xl overflow-hidden">
                <div class="bg-slate-50 px-3 py-2 border-b border-slate-100 flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">📊 Price Comparison — Bangladesh</span>
                    <span id="cache_note_<?=$pid?>" class="text-[9px] text-slate-400 italic"></span>
                </div>

                <div id="comp_loading_<?=$pid?>" class="p-3 space-y-2.5">
                    <?php for($i=0;$i<5;$i++):?>
                    <div class="flex items-center gap-2">
                        <div class="skeleton w-16 h-4 rounded"></div>
                        <div class="skeleton flex-1 h-2 rounded-full"></div>
                        <div class="skeleton w-14 h-4 rounded"></div>
                    </div>
                    <?php endfor;?>
                </div>

                <div id="comp_rows_<?=$pid?>" class="hidden">
                    <div class="our-row flex items-center gap-3 px-3 py-2.5 border-b border-rose-100">
                        <div class="w-20 shrink-0">
                            <span style="font-size:11px;font-weight:800;color:#e11d48;">✨ GlowLink</span>
                        </div>
                        <div class="flex-1">
                            <div id="our_bar_<?=$pid?>" class="price-bar bg-rose-500" style="width:0%"></div>
                        </div>
                        <div class="w-28 text-right shrink-0">
                            <span style="font-size:14px;font-weight:900;color:#e11d48;">৳<?=number_format($our_price,0)?></span>
                            <span id="our_label_<?=$pid?>" class="block text-[9px] font-bold text-emerald-600"></span>
                        </div>
                    </div>
                    <div id="retailer_rows_<?=$pid?>"></div>
                    <div id="comp_summary_<?=$pid?>" class="px-3 py-2.5 text-center text-[10px] font-black uppercase tracking-wide rounded-b-2xl"></div>
                </div>
            </div>

            <div class="mt-auto flex gap-2">
                <a href="view_product.php?id=<?=$pid?>"
                   class="flex-1 py-2.5 bg-slate-100 text-slate-700 rounded-xl font-bold text-xs hover:bg-slate-200 transition flex justify-center items-center gap-1 border border-slate-200">
                    <i class="fa-regular fa-eye"></i> Details
                </a>
                
                <?php if($in_stock): ?>
                <form action="shop_now.php" method="POST" class="flex-[2]">
                    <input type="hidden" name="product_id" value="<?=$pid?>">
                    <button type="submit" class="w-full py-2.5 bg-slate-900 text-white rounded-xl font-bold text-xs hover:bg-rose-500 transition shadow-md flex justify-center items-center gap-2">
                        <i class="fa-solid fa-bag-shopping"></i> Shop Now
                    </button>
                </form>
                <?php else: ?>
                <button disabled class="flex-[2] w-full py-2.5 bg-slate-200 text-slate-400 rounded-xl font-bold text-xs cursor-not-allowed flex justify-center items-center gap-2">
                    <i class="fa-solid fa-box-open"></i> Out of Stock
                </button>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
    <?php endforeach;?>
    </div>
    <?php endif;?>

</div>
</main>

<script>
// ── Sidebar ──────────────────────────────────────────────────────────
const sidebar=document.getElementById('sidebar'),overlay=document.getElementById('sidebarOverlay');
document.getElementById('openSidebarBtn').onclick=()=>toggleSb(true);
document.getElementById('closeSidebarBtn').onclick=()=>toggleSb(false);
overlay.onclick=()=>toggleSb(false);
function toggleSb(open){sidebar.classList.toggle('sidebar-closed',!open);sidebar.classList.toggle('sidebar-open',open);overlay.classList.toggle('hidden',!open);}

const fetched={},openPanels={};

// ── Toggle panel ─────────────────────────────────────────────────────
function toggleComp(pid,productName,ourPrice){
    const drawer=document.getElementById('comp_'+pid);
    const btn=document.getElementById('compbtn_'+pid);
    const isOpen=openPanels[pid];
    openPanels[pid]=!isOpen;
    
    drawer.classList.toggle('open',!isOpen);
    btn.innerHTML=isOpen
        ?'<i class="fa-solid fa-chart-bar text-xs"></i> Compare Prices ▼'
        :'<i class="fa-solid fa-chart-bar text-xs"></i> Hide Comparison ▲';
        
    if(!isOpen) {
        if(!fetched[pid]) {
            loadPrices(pid,productName,ourPrice);
        } else {
            renderComp(pid, ourPrice, fetched[pid]);
        }
    }
}

// ── Fetch from get_prices.php ─────────────────────────────────────────
async function loadPrices(pid,productName,ourPrice){
    try{
        const r=await fetch(`get_prices.php?product_id=${pid}&product_name=${encodeURIComponent(productName)}`);
        const data=await r.json();
        if(data.error){showError(pid);return;}
        fetched[pid]=data;
        renderComp(pid,ourPrice,data);
        updateBadge(pid,ourPrice,data);
    }catch(e){showError(pid);}
}

// ── Render full comparison panel ──────────────────────────────────────
function renderComp(pid,ourPrice,data){
    const allPrices=[ourPrice,...Object.values(data).filter(d=>d.price>0).map(d=>d.price)];
    const maxPrice=Math.max(...allPrices)||1;

    // Our bar
    document.getElementById('our_bar_'+pid).style.width=Math.round((ourPrice/maxPrice)*100)+'%';

    let maxSaving=0,maxSavingPct=0;
    let html='';

    // Sort by price asc (not found = last)
    const sorted=Object.entries(data).sort((a,b)=>{
        const pa=a[1].price||999999,pb=b[1].price||999999;
        return pa-pb;
    });

    sorted.forEach(([name,d])=>{
        const price=d.price;
        const notFound=price===0;
        const diff=notFound?0:(price-ourPrice);
        const cheaper=diff>0; // competitor more expensive = we cheaper
        const pct=price>0?Math.round((Math.abs(diff)/price)*100):0;
        if(cheaper&&diff>maxSaving){maxSaving=diff;maxSavingPct=pct;}

        const barPct=price>0?Math.round((price/maxPrice)*100):0;
        const barColor=notFound?'#e2e8f0':cheaper?'#94a3b8':'#f87171';
        const priceStr=notFound?'—':'৳'+Math.round(price).toLocaleString('en-BD');
        const linkS=d.url?`<a href="${escHtml(d.url)}" target="_blank" rel="noopener">`:'<span>';
        const linkE=d.url?'</a>':'</span>';

        let badge='';
        if(notFound) badge=`<span style="font-size:9px;color:#94a3b8;">Not listed</span>`;
        else if(cheaper) badge=`<span style="font-size:9px;color:#e11d48;font-weight:800;">+৳${Math.round(diff).toLocaleString('en-BD')} pricier</span>`;
        else badge=`<span style="font-size:9px;color:#16a34a;font-weight:800;">৳${Math.round(Math.abs(diff)).toLocaleString('en-BD')} cheaper</span>`;

        const titleSnip=d.title&&!notFound?`<div style="font-size:8px;color:#94a3b8;max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${escAttr(d.title)}">📌 ${d.title.substring(0,22)}…</div>`:'';

        html+=`<div class="retailer-row flex items-center gap-3 px-3 py-2.5 border-b border-slate-100 last:border-0">
            <div class="w-20 shrink-0">${linkS}<span style="font-size:11px;font-weight:800;color:${escHtml(d.color)}">${escHtml(d.icon)} ${escHtml(name)}</span>${linkE}</div>
            <div class="flex-1"><div class="price-bar" style="width:0%;background:${barColor}" data-w="${barPct}%"></div></div>
            <div class="w-28 text-right shrink-0">
                <span style="font-size:13px;font-weight:700;color:${notFound?'#94a3b8':cheaper?'#475569':'#dc2626'}">${priceStr}</span>
                <div>${badge}</div>${titleSnip}
            </div>
        </div>`;
    });

    document.getElementById('retailer_rows_'+pid).innerHTML=html;

    // Animate bars
    setTimeout(()=>{
        document.querySelectorAll('#retailer_rows_'+pid+' .price-bar').forEach(b=>{b.style.width=b.dataset.w;});
    },60);

    // Our label
    const ourLbl=document.getElementById('our_label_'+pid);
    ourLbl.textContent=maxSaving>0?'✓ Cheapest!':'✓ Our Price';
    ourLbl.style.color=maxSaving>0?'#16a34a':'#6b7280';

    // Summary
    const sumEl=document.getElementById('comp_summary_'+pid);
    if(maxSaving>0){
        sumEl.innerHTML=`🎉 You save up to ৳${Math.round(maxSaving).toLocaleString('en-BD')} (${maxSavingPct}%) buying here!`;
        sumEl.style.cssText='background:#f0fdf4;color:#15803d;';
    } else {
        sumEl.innerHTML=`✅ GlowLink already offers the best price!`;
        sumEl.style.cssText='background:#eff6ff;color:#1d4ed8;';
    }

    // Cache indicator
    const allCached=Object.values(data).every(d=>d.from_cache);
    document.getElementById('cache_note_'+pid).textContent=allCached?'⚡ Cached (24h)':'🔴 Live';

    document.getElementById('comp_loading_'+pid).classList.add('hidden');
    document.getElementById('comp_rows_'+pid).classList.remove('hidden');
}

// ── Update badge & strikethrough ──────────────────────────────────────
function updateBadge(pid,ourPrice,data){
    let maxSaving=0,maxSavingPct=0;
    Object.values(data).forEach(d=>{
        const diff=d.price-ourPrice;
        const pct=d.price>0?Math.round((diff/d.price)*100):0;
        if(diff>0&&diff>maxSaving){maxSaving=diff;maxSavingPct=pct;}
    });
    const badge=document.getElementById('badge_'+pid);
    badge.classList.remove('skeleton');
    if(maxSaving>0){
        badge.className='absolute top-3 left-3 z-10 text-xs font-black px-3 py-1 rounded-full border badge-save';
        badge.textContent=`Save ৳${Math.round(maxSaving).toLocaleString('en-BD')} (${maxSavingPct}%)`;
        const sl=document.getElementById('slash_price_'+pid);
        if(sl){sl.textContent='৳'+(ourPrice+maxSaving).toLocaleString('en-BD');sl.classList.remove('hidden');}
    } else {
        badge.className='absolute top-3 left-3 z-10 text-xs font-black px-3 py-1 rounded-full border badge-best';
        badge.textContent='✓ Best Price';
    }
}

function showError(pid){
    document.getElementById('comp_loading_'+pid).innerHTML=
        '<p class="text-center text-xs text-slate-400 py-4">⚠️ Price fetch failed. Ensure cURL is enabled & get_prices.php exists.</p>';
    const b=document.getElementById('badge_'+pid);
    b.classList.remove('skeleton');
    b.className='absolute top-3 left-3 z-10 text-xs font-black px-3 py-1 rounded-full border badge-best';
    b.textContent='✓ Best Price';
}

function escHtml(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function escAttr(s){return String(s).replace(/"/g,"'");}

// ── Background badge prefetch on page load ────────────────────────────
document.addEventListener('DOMContentLoaded',()=>{
    document.querySelectorAll('[id^="compbtn_"]').forEach((btn,i)=>{
        const pid=btn.id.replace('compbtn_','');
        // Parse args from onclick attribute
        const oc=btn.getAttribute('onclick');
        const m=oc.match(/toggleComp\((\d+),\s*(.+?),\s*([\d.]+)\)/);
        if(!m)return;
        const pname=JSON.parse(m[2]);
        const ourPrice=parseFloat(m[3]);
        setTimeout(()=>{
            fetch(`get_prices.php?product_id=${pid}&product_name=${encodeURIComponent(pname)}`)
                .then(r=>r.json())
                .then(data=>{fetched[pid]=data;updateBadge(pid,ourPrice,data);})
                .catch(()=>{
                    const b=document.getElementById('badge_'+pid);
                    if(b){b.classList.remove('skeleton');b.className='absolute top-3 left-3 z-10 text-xs font-black px-3 py-1 rounded-full border badge-best';b.textContent='✓ Best Price';}
                });
        },i*350);
    });
});
</script>
</body>
</html>