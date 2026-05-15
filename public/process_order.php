<?php
session_start();
include 'connection.php';

$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $user_id = $_SESSION['user_id'] ?? 1;
    $cart_data = $_POST['cart_data'] ?? '';
    $total_amount = $_POST['total_amount'] ?? 0;
    $delivery_fee = $_POST['delivery_zone'] ?? 0;
    $address = trim($_POST['detailed_address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'cod';
    
    $order_number = 'GLOW-' . rand(100000, 999999);
    $payment_status = ($payment_method === 'cod') ? 'Pending' : 'Completed'; 
    $order_status = 'Pending';
    $created_at = date('Y-m-d H:i:s');

    $sql = "INSERT INTO orders (user_id, order_number, cart_items, total_amount, delivery_fee, address, payment_method, payment_status, order_status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    
    if($stmt) {
        $stmt->bind_param("issddsssss", $user_id, $order_number, $cart_data, $total_amount, $delivery_fee, $address, $payment_method, $payment_status, $order_status, $created_at);

        if ($stmt->execute()) {
            echo "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Order Success</title>
                <script src='https://cdn.tailwindcss.com'></script>
            </head>
            <body class='bg-slate-50 flex items-center justify-center min-h-screen'>
                <div class='bg-white p-10 rounded-[32px] shadow-xl max-w-md w-full text-center border border-slate-100'>
                    <div class='w-20 h-20 bg-green-50 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm'>
                        <svg class='w-10 h-10' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'>
                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M5 13l4 4L19 7'></path>
                        </svg>
                    </div>
                    <h2 class='text-3xl font-black text-slate-900 mb-2'>Success!</h2>
                    <p class='text-slate-500 mb-8 leading-relaxed'>Your order has been placed. Order Number: <br><span class='font-black text-rose-500 text-xl'>$order_number</span></p>
                    <div class='space-y-3'>
                        <a href='my_orders.php' class='block w-full py-4 bg-slate-900 text-white rounded-2xl font-bold hover:bg-slate-800 transition shadow-lg shadow-slate-200'>View My Orders</a>
                        <a href='shop.php' class='block w-full py-4 bg-white text-slate-600 rounded-2xl font-bold hover:bg-slate-50 transition border border-slate-200'>Back to Shop</a>
                    </div>
                </div>
            </body>
            </html>
            ";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
         echo "SQL Error: " . $conn->error;
    }
} else {
    header("Location: shop.php");
}

$conn->close();
?>