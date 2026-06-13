<?php
require_once '../includes/db.php';
if (!isLoggedIn() || (!isAdmin() && !isStaff())) redirect('../login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pos_sale'])) {
    header('Content-Type: application/json');
    $items    = json_decode($_POST['items'], true);
    $payment  = e($conn, $_POST['payment_method']);
    $discount = (float)$_POST['discount'];
    $notes    = e($conn, $_POST['notes'] ?? '');
    if (!empty($items)) {
        $total = 0;
        foreach ($items as $item) $total += (float)$item['price'] * (int)$item['qty'];
        $total -= $discount;
        if ($total < 0) $total = 0;
        $stmt = mysqli_prepare($conn,
            "INSERT INTO orders (user_id,total_amount,status,order_type,payment_method,payment_status,notes) VALUES (NULL,?,'delivered','walkin',?,'paid',?)");
        mysqli_stmt_bind_param($stmt,'dss',$total,$payment,$notes);
        mysqli_stmt_execute($stmt);
        $order_id = mysqli_insert_id($conn);
        foreach ($items as $item) {
            $pid=(int)$item['product_id']; $qty=(int)$item['qty']; $price=(float)$item['price'];
            $s2=mysqli_prepare($conn,"INSERT INTO order_items (order_id,product_id,quantity,unit_price) VALUES (?,?,?,?)");
            mysqli_stmt_bind_param($s2,'iiid',$order_id,$pid,$qty,$price);
            mysqli_stmt_execute($s2);
        }
        $subtotal    = $total + $discount;
        $last        = mysqli_fetch_assoc(mysqli_query($conn,"SELECT bill_id FROM bills ORDER BY bill_id DESC LIMIT 1"));
        $next_id     = $last ? $last['bill_id']+1 : 1;
        $bill_number = 'TDB-'.date('Y').'-'.str_pad($next_id,4,'0',STR_PAD_LEFT);
        $s3=mysqli_prepare($conn,"INSERT INTO bills (order_id,bill_number,subtotal,discount,total) VALUES (?,?,?,?,?)");
        mysqli_stmt_bind_param($s3,'isddd',$order_id,$bill_number,$subtotal,$discount,$total);
        mysqli_stmt_execute($s3);
        $bill_id = mysqli_insert_id($conn);
        echo json_encode(['success'=>true,'order_id'=>$order_id,'bill_id'=>$bill_id,'bill_number'=>$bill_number,'total'=>$total]);
    } else {
        echo json_encode(['success'=>false,'msg'=>'No items!']);
    }
    exit();
}

// BARCODE LOOKUP via AJAX
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['barcode_lookup'])) {
    header('Content-Type: application/json');
    $bc = e($conn, $_POST['barcode']);
    $p  = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM products WHERE barcode='$bc' AND stock_status='available' LIMIT 1"));
    if ($p) {
        echo json_encode(['success'=>true,'product'=>[
            'product_id' => $p['product_id'],
            'name'       => $p['name'],
            'price'      => $p['price'],
            'category'   => $p['category']
        ]]);
    } else {
        echo json_encode(['success'=>false,'msg'=>'Product not found!']);
    }
    exit();
}

$products = mysqli_query($conn,"SELECT * FROM products WHERE stock_status='available' ORDER BY category,name");
$cats = [];
while ($p = mysqli_fetch_assoc($products)) $cats[$p['category']][] = $p;
$icons = ['cakes'=>'🎂','brownies'=>'🍫','cupcakes'=>'🧁','pastries'=>'🥐','breads'=>'🍞'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>POS — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{background:#f5f0eb;font-family:'Segoe UI',sans-serif;color:#2a1a10;margin:0;height:100vh;overflow:hidden;}
.pos-wrap{display:flex;height:100vh;margin-left:240px;}
.pos-left{flex:1;display:flex;flex-direction:column;overflow:hidden;padding:18px;gap:12px;}
.pos-right{width:370px;background:#fff;border-left:2px solid #e8d8c0;display:flex;flex-direction:column;flex-shrink:0;}

/* HEADER */
.pos-header{display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.pos-title{font-size:20px;font-weight:700;color:#1a0a0f;display:flex;align-items:center;gap:10px;}
.pos-title i{color:#c9973a;font-size:22px;}
.pos-date{font-size:12px;color:#8a6a4a;background:#fff;padding:6px 14px;border-radius:99px;border:1px solid #e8d8c0;}

/* BARCODE SCANNER INPUT */
.barcode-scan-wrap{
  display:flex;gap:8px;flex-shrink:0;
  background:linear-gradient(135deg,#1a0a0f,#2a1018);
  border-radius:12px;padding:12px 16px;
  align-items:center;
}
.barcode-scan-wrap label{
  font-size:12px;font-weight:700;color:#e8c870;
  letter-spacing:1px;text-transform:uppercase;
  white-space:nowrap;display:flex;align-items:center;gap:6px;
  flex-shrink:0;
}
.barcode-scan-wrap label i{color:#c9973a;font-size:16px;}
.barcode-input{
  flex:1;background:rgba(255,255,255,0.1);
  border:1.5px solid rgba(201,151,58,0.3);
  border-radius:8px;padding:9px 14px;
  font-size:14px;color:#fff;
  outline:none;transition:all .2s;
  font-family:'Courier New',monospace;
  letter-spacing:2px;
}
.barcode-input:focus{border-color:#c9973a;background:rgba(255,255,255,0.15);}
.barcode-input::placeholder{color:rgba(255,255,255,0.3);letter-spacing:1px;font-size:12px;}
.barcode-status{
  font-size:12px;font-weight:600;
  padding:4px 12px;border-radius:99px;
  white-space:nowrap;flex-shrink:0;
}
.barcode-status.idle{background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.4);}
.barcode-status.found{background:rgba(46,125,50,0.3);color:#66bb6a;}
.barcode-status.error{background:rgba(239,83,80,0.3);color:#ef9090;}

/* SEARCH */
.pos-search{position:relative;flex-shrink:0;}
.pos-search input{width:100%;padding:11px 16px 11px 44px;border:1px solid #e8d8c0;border-radius:10px;font-size:14px;color:#2a1a10;background:#fff;outline:none;transition:border .2s;}
.pos-search input:focus{border-color:#c9973a;}
.pos-search i{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:#8a6a4a;font-size:16px;}

/* CATEGORY TABS */
.cat-tabs{display:flex;gap:8px;flex-wrap:wrap;flex-shrink:0;}
.cat-tab{padding:7px 16px;border-radius:99px;border:1px solid #e8d8c0;background:#fff;font-size:13px;color:#8a6a4a;cursor:pointer;transition:all .15s;font-weight:600;}
.cat-tab:hover{border-color:#c9973a;color:#c9973a;}
.cat-tab.active{background:#c9973a;border-color:#c9973a;color:#fff;}

/* PRODUCTS GRID */
.products-scroll{flex:1;overflow-y:auto;padding-right:4px;}
.products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(148px,1fr));gap:12px;}
.prod-card{background:#fff;border:2px solid #e8d8c0;border-radius:14px;padding:16px 12px;cursor:pointer;transition:all .2s;text-align:center;user-select:none;position:relative;}
.prod-card:hover{border-color:#c9973a;transform:translateY(-3px);box-shadow:0 6px 20px rgba(201,151,58,0.2);}
.prod-card:active{transform:scale(0.96);}
.prod-card.flash{border-color:#2e7d32;background:#f0fff4;transform:scale(1.04);}
.prod-icon{font-size:36px;margin-bottom:8px;display:block;}
.prod-name{font-size:13px;font-weight:700;color:#1a0a0f;margin-bottom:6px;line-height:1.3;}
.prod-price{font-size:16px;font-weight:700;color:#c9973a;}
.prod-add-badge{position:absolute;top:8px;right:8px;width:22px;height:22px;border-radius:50%;background:#c9973a;color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;opacity:0;transition:opacity .2s;}
.prod-card:hover .prod-add-badge{opacity:1;}

/* CART */
.cart-header{padding:16px 18px;border-bottom:1px solid #f0e4d0;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.cart-title{font-size:16px;font-weight:700;color:#1a0a0f;display:flex;align-items:center;gap:8px;}
.cart-title i{color:#c9973a;}
.cart-clear{background:#ffebee;border:none;color:#c62828;font-size:12px;cursor:pointer;font-weight:700;padding:5px 12px;border-radius:99px;}
.cart-clear:hover{background:#ffcdd2;}
.cart-items-wrap{flex:1;overflow-y:auto;padding:10px 14px;min-height:0;}
.cart-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 20px;color:#c9b090;height:100%;}
.cart-empty i{font-size:44px;display:block;margin-bottom:10px;color:#e8d8c0;}
.cart-empty p{font-size:14px;margin-bottom:4px;text-align:center;}
.cart-empty small{font-size:12px;color:#c9b090;}
.cart-item{display:flex;align-items:center;gap:8px;padding:10px 0;border-bottom:1px solid #f5ede0;}
.cart-item:last-child{border-bottom:none;}
.ci-icon{font-size:24px;flex-shrink:0;}
.ci-info{flex:1;min-width:0;}
.ci-name{font-size:13px;font-weight:700;color:#1a0a0f;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.ci-price{font-size:11px;color:#8a6a4a;margin-top:1px;}
.ci-qty{display:flex;align-items:center;gap:5px;flex-shrink:0;}
.qty-btn{width:26px;height:26px;border-radius:7px;border:1.5px solid #e8d8c0;background:#fdf5ec;font-size:15px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#2a1a10;font-weight:700;transition:all .2s;flex-shrink:0;line-height:1;}
.qty-btn:hover{background:#c9973a;color:#fff;border-color:#c9973a;}
.qty-num{font-size:14px;font-weight:700;min-width:22px;text-align:center;color:#1a0a0f;}
.ci-total{font-size:13px;font-weight:700;color:#c9973a;min-width:72px;text-align:right;flex-shrink:0;}
.ci-del{background:none;border:none;color:#e8d8c0;cursor:pointer;font-size:16px;padding:2px 4px;transition:color .2s;flex-shrink:0;}
.ci-del:hover{color:#ef5350;}

/* CART FOOTER */
.cart-footer{padding:14px 18px;border-top:1px solid #f0e4d0;flex-shrink:0;}
.summary-row{display:flex;justify-content:space-between;font-size:13px;padding:4px 0;color:#8a6a4a;}
.summary-row.grand{font-size:19px;font-weight:700;color:#1a0a0f;padding:10px 0 0;border-top:1.5px solid #e8d8c0;margin-top:8px;}
.discount-row{display:flex;align-items:center;gap:8px;margin:8px 0;}
.discount-row label{font-size:12px;color:#8a6a4a;white-space:nowrap;font-weight:600;}
.discount-row input{flex:1;border:1px solid #e8d8c0;border-radius:7px;padding:7px 10px;font-size:13px;color:#2a1a10;outline:none;transition:border .2s;}
.discount-row input:focus{border-color:#c9973a;}
.pay-methods{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin:10px 0;}
.pay-btn{border:1.5px solid #e8d8c0;background:#fff;border-radius:9px;padding:9px 4px;font-size:12px;font-weight:700;cursor:pointer;text-align:center;transition:all .2s;color:#2a1a10;}
.pay-btn:hover{border-color:#c9973a;}
.pay-btn.selected{background:#c9973a;border-color:#c9973a;color:#fff;}
.notes-inp{width:100%;border:1px solid #e8d8c0;border-radius:8px;padding:8px 10px;font-size:13px;color:#2a1a10;resize:none;margin:6px 0;outline:none;font-family:'Segoe UI',sans-serif;}
.notes-inp:focus{border-color:#c9973a;}
.btn-charge{width:100%;background:linear-gradient(135deg,#1a0a0f,#2a1018);border:none;border-radius:10px;color:#e8c870;font-weight:700;padding:14px;font-size:16px;cursor:pointer;margin-top:8px;letter-spacing:.5px;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-charge:hover:not(:disabled){background:linear-gradient(135deg,#c9973a,#a07828);color:#1a0a0f;transform:translateY(-1px);box-shadow:0 4px 16px rgba(201,151,58,0.35);}
.btn-charge:disabled{background:#e8d8c0;color:#b09070;cursor:not-allowed;}

/* RECEIPT OVERLAY */
.receipt-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.65);z-index:9999;align-items:center;justify-content:center;}
.receipt-overlay.show{display:flex;}
.receipt-box{background:#fff;border-radius:16px;width:100%;max-width:420px;overflow:hidden;margin:20px;box-shadow:0 20px 60px rgba(0,0,0,0.4);}
.receipt-hdr{background:#1a0a0f;padding:22px;text-align:center;}
.r-logo{font-size:36px;display:block;margin-bottom:8px;}
.r-name{font-family:Georgia,serif;font-size:20px;font-weight:900;color:#e8c870;letter-spacing:3px;}
.r-sub{font-size:10px;color:#c9973a;letter-spacing:4px;margin-top:2px;}
.r-loc{font-size:10px;color:#5a3a28;margin-top:3px;}
.r-billno{margin-top:12px;display:inline-block;background:#c9973a;color:#1a0a0f;font-size:11px;font-weight:700;letter-spacing:2px;padding:4px 16px;border-radius:99px;}
.receipt-body{padding:16px 20px;}
.r-meta{display:flex;justify-content:space-between;font-size:12px;color:#8a6a4a;margin-bottom:12px;}
.r-items{border-top:1px dashed #e8d8c0;border-bottom:1px dashed #e8d8c0;padding:10px 0;margin-bottom:10px;}
.r-item{display:flex;justify-content:space-between;font-size:13px;padding:4px 0;color:#2a1a10;}
.r-total-row{display:flex;justify-content:space-between;font-size:13px;padding:3px 0;color:#8a6a4a;}
.r-grand{display:flex;justify-content:space-between;font-size:18px;font-weight:700;padding:10px 0 0;border-top:1.5px solid #e8d8c0;margin-top:6px;}
.receipt-footer-msg{text-align:center;font-size:12px;color:#8a6a4a;padding:12px 20px;background:#fdf5ec;border-top:1px solid #f0e4d0;}
.receipt-actions{display:flex;gap:10px;padding:14px 20px;background:#fff;}
.btn-print-r{flex:1;background:#c9973a;border:none;border-radius:8px;color:#fff;font-weight:700;padding:11px;font-size:14px;cursor:pointer;}
.btn-print-r:hover{background:#a07828;}
.btn-new-sale{flex:1;background:#1a0a0f;border:none;border-radius:8px;color:#e8c870;font-weight:700;padding:11px;font-size:14px;cursor:pointer;}
.btn-new-sale:hover{background:#2a1018;}

@media print{
  body *{visibility:hidden;}
  .receipt-box,.receipt-box *{visibility:visible;}
  .receipt-box{position:fixed;top:0;left:0;width:100%;}
  .receipt-actions{display:none!important;}
}
</style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>

<div class="pos-wrap">

  <!-- LEFT: PRODUCTS -->
  <div class="pos-left">
    <div class="pos-header">
      <div class="pos-title">
        <i class="bi bi-cash-register"></i>
        POS — Point of Sale
      </div>
      <div class="pos-date">
        <i class="bi bi-clock me-1"></i><?= date('d M Y · h:i A') ?>
      </div>
    </div>

    <!-- BARCODE SCANNER -->
    <div class="barcode-scan-wrap">
      <label>
        <i class="bi bi-upc-scan"></i>
        Barcode Scanner
      </label>
      <input type="text" id="barcodeInput"
             class="barcode-input"
             placeholder="Scan or type barcode..."
             autocomplete="off"
             autofocus>
      <span class="barcode-status idle" id="barcodeStatus">
        <i class="bi bi-circle-fill me-1" style="font-size:8px;"></i>Ready
      </span>
    </div>

    <!-- PRODUCT SEARCH -->
    <div class="pos-search">
      <i class="bi bi-search"></i>
      <input type="text" id="searchInput"
             placeholder="Search products by name..."
             oninput="filterProducts()">
    </div>

    <!-- CATEGORY TABS -->
    <div class="cat-tabs">
      <button class="cat-tab active" onclick="filterCat('all',this)">🍽️ All</button>
      <?php foreach(array_keys($cats) as $cat): ?>
      <button class="cat-tab" onclick="filterCat('<?= $cat ?>',this)">
        <?= $icons[$cat]??'' ?> <?= ucfirst($cat) ?>
      </button>
      <?php endforeach; ?>
    </div>

    <!-- PRODUCTS GRID -->
    <div class="products-scroll">
      <div class="products-grid" id="productsGrid">
        <?php foreach($cats as $cat=>$prods): ?>
        <?php foreach($prods as $p): ?>
        <div class="prod-card"
             data-cat="<?= $p['category'] ?>"
             data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>"
             data-id="<?= $p['product_id'] ?>"
             onclick="addToCart(<?= (int)$p['product_id'] ?>,'<?= addslashes($p['name']) ?>',<?= (float)$p['price'] ?>,'<?= $p['category'] ?>')">
          <span class="prod-icon"><?= $icons[$p['category']]??'🍰' ?></span>
          <div class="prod-name"><?= htmlspecialchars($p['name']) ?></div>
          <div class="prod-price">Rs. <?= number_format($p['price'],2) ?></div>
          <div class="prod-add-badge">+</div>
        </div>
        <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- RIGHT: CART -->
  <div class="pos-right">
    <div class="cart-header">
      <div class="cart-title">
        <i class="bi bi-cart3"></i> Current Order
      </div>
      <button class="cart-clear" onclick="clearCart()">
        <i class="bi bi-trash me-1"></i>Clear
      </button>
    </div>

    <div class="cart-items-wrap" id="cartItemsWrap"></div>

    <div class="cart-footer">
      <div class="summary-row">
        <span>Items</span>
        <span id="itemCount">0 items</span>
      </div>
      <div class="summary-row">
        <span>Subtotal</span>
        <span id="subtotalDisplay">Rs. 0.00</span>
      </div>
      <div class="discount-row">
        <label>Discount (Rs.)</label>
        <input type="number" id="discountInput" value="0" min="0" oninput="updateTotals()">
      </div>
      <div class="summary-row grand">
        <span>TOTAL</span>
        <span id="totalDisplay">Rs. 0.00</span>
      </div>
      <div style="font-size:12px;color:#8a6a4a;margin:10px 0 5px;font-weight:700;">Payment Method</div>
      <div class="pay-methods">
        <button class="pay-btn selected" data-pay="cash" onclick="selectPay(this)">💵 Cash</button>
        <button class="pay-btn" data-pay="card" onclick="selectPay(this)">💳 Card</button>
        <button class="pay-btn" data-pay="online" onclick="selectPay(this)">📱 Online</button>
      </div>
      <textarea class="notes-inp" id="notesInput" rows="2" placeholder="Notes (optional)..."></textarea>
      <button class="btn-charge" id="chargeBtn" onclick="processSale()" disabled>
        <i class="bi bi-check-circle-fill"></i>
        Charge — Rs. <span id="chargeTotalBtn">0.00</span>
      </button>
    </div>
  </div>
</div>

<!-- RECEIPT -->
<div class="receipt-overlay" id="receiptOverlay">
  <div class="receipt-box">
    <div class="receipt-hdr">
      <span class="r-logo">🎂</span>
      <div class="r-name">THILHA</div>
      <div class="r-sub">DIVINE BAKES</div>
      <div class="r-loc">~ Central Camp, Ampara ~</div>
      <div class="r-billno" id="r-billno">—</div>
    </div>
    <div class="receipt-body">
      <div class="r-meta">
        <span>Order: <strong id="r-orderid">—</strong></span>
        <span id="r-date">—</span>
      </div>
      <div class="r-items" id="r-items"></div>
      <div>
        <div class="r-total-row"><span>Subtotal</span><span id="r-subtotal">—</span></div>
        <div class="r-total-row" id="r-disc-row" style="display:none;">
          <span>Discount</span><span id="r-discount" style="color:#ef5350;">—</span>
        </div>
        <div class="r-grand"><span>TOTAL</span><span id="r-total" style="color:#c9973a;">—</span></div>
      </div>
    </div>
    <div class="receipt-footer-msg">
      ✅ Walk-in Sale — Thank you! 🎂<br>
      <small>Central Camp, Ampara, Sri Lanka</small>
    </div>
    <div class="receipt-actions">
      <button class="btn-print-r" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
      <button class="btn-new-sale" onclick="newSale()"><i class="bi bi-plus-circle me-1"></i>New Sale</button>
    </div>
  </div>
</div>

<script>
var cart       = {};
var selectedPay = 'cash';
var icons      = <?= json_encode($icons) ?>;
var barcodeTimer = null;

// ===== ADD TO CART =====
function addToCart(id, name, price, cat) {
    id    = parseInt(id);
    price = parseFloat(price);
    if (cart[id]) {
        cart[id].qty += 1;
    } else {
        cart[id] = { product_id: id, name: name, price: price, qty: 1, cat: cat };
    }
    renderCart();
    flashCard(id);
}

// ===== FLASH CARD =====
function flashCard(id) {
    var card = document.querySelector('.prod-card[data-id="' + id + '"]');
    if (card) {
        card.classList.add('flash');
        setTimeout(function() { card.classList.remove('flash'); }, 400);
    }
}

// ===== CHANGE QTY =====
function changeQty(id, delta) {
    id = parseInt(id);
    if (!cart[id]) return;
    cart[id].qty += delta;
    if (cart[id].qty <= 0) delete cart[id];
    renderCart();
}

// ===== REMOVE =====
function removeFromCart(id) {
    delete cart[parseInt(id)];
    renderCart();
}

// ===== CLEAR =====
function clearCart() {
    cart = {};
    document.getElementById('discountInput').value = 0;
    document.getElementById('notesInput').value    = '';
    renderCart();
}

// ===== RENDER CART =====
function renderCart() {
    var wrap = document.getElementById('cartItemsWrap');
    var keys = Object.keys(cart);
    wrap.innerHTML = '';

    if (keys.length === 0) {
        wrap.innerHTML = '<div class="cart-empty"><i class="bi bi-cart-x"></i><p>No items added</p><small>Click products or scan barcode</small></div>';
        updateTotals();
        return;
    }

    var html = '';
    keys.forEach(function(id) {
        var item     = cart[id];
        var icon     = icons[item.cat] || '🍰';
        var lineTotal = (item.price * item.qty).toFixed(2);
        html += '<div class="cart-item">' +
            '<div class="ci-icon">' + icon + '</div>' +
            '<div class="ci-info">' +
                '<div class="ci-name">' + item.name + '</div>' +
                '<div class="ci-price">Rs. ' + parseFloat(item.price).toFixed(2) + ' each</div>' +
            '</div>' +
            '<div class="ci-qty">' +
                '<button class="qty-btn" onclick="changeQty(' + id + ',-1)">−</button>' +
                '<span class="qty-num">' + item.qty + '</span>' +
                '<button class="qty-btn" onclick="changeQty(' + id + ',1)">+</button>' +
            '</div>' +
            '<div class="ci-total">Rs. ' + lineTotal + '</div>' +
            '<button class="ci-del" onclick="removeFromCart(' + id + ')"><i class="bi bi-x-circle-fill"></i></button>' +
        '</div>';
    });
    wrap.innerHTML = html;
    updateTotals();
}

// ===== UPDATE TOTALS =====
function updateTotals() {
    var subtotal = 0, count = 0;
    Object.values(cart).forEach(function(item) {
        subtotal += item.price * item.qty;
        count    += item.qty;
    });
    var discount = parseFloat(document.getElementById('discountInput').value) || 0;
    var total    = Math.max(0, subtotal - discount);

    document.getElementById('subtotalDisplay').textContent = 'Rs. ' + subtotal.toFixed(2);
    document.getElementById('totalDisplay').textContent    = 'Rs. ' + total.toFixed(2);
    document.getElementById('chargeTotalBtn').textContent  = total.toFixed(2);
    document.getElementById('itemCount').textContent       = count + (count === 1 ? ' item' : ' items');
    document.getElementById('chargeBtn').disabled          = Object.keys(cart).length === 0;
}

// ===== SELECT PAYMENT =====
function selectPay(btn) {
    document.querySelectorAll('.pay-btn').forEach(function(b) { b.classList.remove('selected'); });
    btn.classList.add('selected');
    selectedPay = btn.getAttribute('data-pay');
}

// ===== FILTER CATEGORY =====
function filterCat(cat, btn) {
    document.querySelectorAll('.cat-tab').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
    document.querySelectorAll('.prod-card').forEach(function(card) {
        card.style.display = (cat === 'all' || card.dataset.cat === cat) ? '' : 'none';
    });
}

// ===== SEARCH =====
function filterProducts() {
    var q = document.getElementById('searchInput').value.toLowerCase().trim();
    document.querySelectorAll('.prod-card').forEach(function(card) {
        card.style.display = card.dataset.name.includes(q) ? '' : 'none';
    });
}

// ===== BARCODE SCANNER =====
var barcodeInput  = document.getElementById('barcodeInput');
var barcodeStatus = document.getElementById('barcodeStatus');

barcodeInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        var bc = barcodeInput.value.trim();
        if (bc.length > 0) {
            lookupBarcode(bc);
        }
    }
});

// Auto-lookup after typing stops (for USB scanners that don't send Enter)
barcodeInput.addEventListener('input', function() {
    clearTimeout(barcodeTimer);
    var bc = barcodeInput.value.trim();
    if (bc.length >= 6) {
        barcodeTimer = setTimeout(function() {
            lookupBarcode(bc);
        }, 300);
    }
});

function lookupBarcode(barcode) {
    barcodeStatus.className = 'barcode-status idle';
    barcodeStatus.innerHTML = '<i class="bi bi-hourglass-split me-1" style="font-size:10px;"></i>Searching...';

    var fd = new FormData();
    fd.append('barcode_lookup', '1');
    fd.append('barcode', barcode);
    fd.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');

    fetch('pos.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            var p = data.product;
            addToCart(p.product_id, p.name, p.price, p.category);
            barcodeStatus.className = 'barcode-status found';
            barcodeStatus.innerHTML = '<i class="bi bi-check-circle-fill me-1" style="font-size:10px;"></i>Added!';
            barcodeInput.value = '';
            setTimeout(function() {
                barcodeStatus.className = 'barcode-status idle';
                barcodeStatus.innerHTML = '<i class="bi bi-circle-fill me-1" style="font-size:8px;"></i>Ready';
            }, 1500);
        } else {
            barcodeStatus.className = 'barcode-status error';
            barcodeStatus.innerHTML = '<i class="bi bi-x-circle-fill me-1" style="font-size:10px;"></i>Not found!';
            barcodeInput.select();
            setTimeout(function() {
                barcodeStatus.className = 'barcode-status idle';
                barcodeStatus.innerHTML = '<i class="bi bi-circle-fill me-1" style="font-size:8px;"></i>Ready';
            }, 2000);
        }
    })
    .catch(function() {
        barcodeStatus.className = 'barcode-status error';
        barcodeStatus.innerHTML = 'Error!';
    });
}

// ===== PROCESS SALE =====
function processSale() {
    if (Object.keys(cart).length === 0) return;

    var btn = document.getElementById('chargeBtn');
    btn.disabled  = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processing...';

    var discount = parseFloat(document.getElementById('discountInput').value) || 0;
    var notes    = document.getElementById('notesInput').value;
    var items    = Object.values(cart);

    var fd = new FormData();
    fd.append('pos_sale',       '1');
    fd.append('items',          JSON.stringify(items));
    fd.append('payment_method', selectedPay);
    fd.append('discount',       discount);
    fd.append('notes',          notes);
    fd.append('csrf_token',     '<?= $_SESSION['csrf_token'] ?>');

    fetch('pos.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            showReceipt(data);
        } else {
            alert('Error: ' + (data.msg || 'Something went wrong!'));
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-check-circle-fill"></i>Charge — Rs. <span id="chargeTotalBtn">' + document.getElementById('totalDisplay').textContent.replace('Rs. ','') + '</span>';
        }
    })
    .catch(function() {
        alert('Network error!');
        btn.disabled  = false;
        btn.innerHTML = '<i class="bi bi-check-circle-fill"></i>Charge — Rs. <span id="chargeTotalBtn">0.00</span>';
    });
}

// ===== SHOW RECEIPT =====
function showReceipt(data) {
    var subtotal = 0;
    Object.values(cart).forEach(function(i) { subtotal += i.price * i.qty; });
    var discount = parseFloat(document.getElementById('discountInput').value) || 0;

    document.getElementById('r-billno').textContent  = data.bill_number;
    document.getElementById('r-orderid').textContent = '#' + data.order_id;
    document.getElementById('r-date').textContent    = new Date().toLocaleString();

    var html = '';
    Object.values(cart).forEach(function(item) {
        html += '<div class="r-item"><span>' + (icons[item.cat]||'🍰') + ' ' + item.name + ' ×' + item.qty + '</span><span>Rs. ' + (item.price*item.qty).toFixed(2) + '</span></div>';
    });
    document.getElementById('r-items').innerHTML    = html;
    document.getElementById('r-subtotal').textContent = 'Rs. ' + subtotal.toFixed(2);

    if (discount > 0) {
        document.getElementById('r-disc-row').style.display = 'flex';
        document.getElementById('r-discount').textContent   = '- Rs. ' + discount.toFixed(2);
    }
    document.getElementById('r-total').textContent = 'Rs. ' + parseFloat(data.total).toFixed(2);
    document.getElementById('receiptOverlay').classList.add('show');
}

// ===== NEW SALE =====
function newSale() {
    cart = {}; selectedPay = 'cash';
    document.getElementById('discountInput').value  = 0;
    document.getElementById('notesInput').value     = '';
    document.getElementById('r-disc-row').style.display = 'none';
    document.getElementById('receiptOverlay').classList.remove('show');
    document.querySelectorAll('.pay-btn').forEach(function(b) { b.classList.remove('selected'); });
    document.querySelector('[data-pay="cash"]').classList.add('selected');
    var btn = document.getElementById('chargeBtn');
    btn.disabled  = true;
    btn.innerHTML = '<i class="bi bi-check-circle-fill"></i>Charge — Rs. <span id="chargeTotalBtn">0.00</span>';
    renderCart();
    // Focus barcode input for next scan
    document.getElementById('barcodeInput').focus();
}

// Init
renderCart();
</script>
</body>
</html>
