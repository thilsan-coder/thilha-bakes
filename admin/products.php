<?php
require_once '../includes/db.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

// delete
if (isset($_GET['delete'])) {
    $pid = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM products WHERE product_id=$pid");
    redirect('products.php?deleted=1');
}

// toggle stock
if (isset($_GET['toggle'])) {
    $pid  = (int)$_GET['toggle'];
    $curr = mysqli_fetch_assoc(mysqli_query($conn,"SELECT stock_status FROM products WHERE product_id=$pid"));
    $new  = $curr['stock_status']==='available' ? 'out_of_stock' : 'available';
    mysqli_query($conn,"UPDATE products SET stock_status='$new' WHERE product_id=$pid");
    redirect('products.php?toggled=1');
}

// add / edit
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name    = e($conn,$_POST['name']);
    $cat     = e($conn,$_POST['category']);
    $price   = (float)$_POST['price'];
    $desc    = e($conn,$_POST['description']??'');
    $status  = e($conn,$_POST['stock_status']);
    $barcode = e($conn,$_POST['barcode']??'');
    $image   = '';
    if (!empty($_FILES['image']['name'])) {
        $img = uploadImage($_FILES['image']);
        if ($img) $image = $img;
    }
    if (isset($_POST['product_id']) && $_POST['product_id']) {
        $pid     = (int)$_POST['product_id'];
        $img_sql = $image ? ", image='$image'" : '';
        $bc_sql  = $barcode ? ", barcode='$barcode'" : '';
        mysqli_query($conn,"UPDATE products SET name='$name',category='$cat',price=$price,description='$desc',stock_status='$status' $img_sql $bc_sql WHERE product_id=$pid");
        redirect('products.php?updated=1');
    } else {
        // auto generate barcode if empty
        if (empty($barcode)) {
            $last = mysqli_fetch_assoc(mysqli_query($conn,"SELECT MAX(product_id) as mid FROM products"));
            $next = ($last['mid'] ?? 0) + 1;
            $barcode = 'TDB'.str_pad($next,6,'0',STR_PAD_LEFT);
        }
        mysqli_query($conn,"INSERT INTO products (name,category,price,description,image,stock_status,barcode) VALUES ('$name','$cat',$price,'$desc','$image','$status','$barcode')");
        redirect('products.php?added=1');
    }
}

$products = mysqli_query($conn,"SELECT * FROM products ORDER BY category,name");
$total_p  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM products"))['c'];
$avail_p  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM products WHERE stock_status='available'"))['c'];
$out_p    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM products WHERE stock_status='out_of_stock'"))['c'];

$icons = ['cakes'=>'🎂','brownies'=>'🍫','cupcakes'=>'🧁','pastries'=>'🥐','breads'=>'🍞'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Products — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<!-- JsBarcode library for barcode generation -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<style>
*{box-sizing:border-box;}
body{background:#f5f0eb;font-family:'Segoe UI',sans-serif;color:#2a1a10;margin:0;}
.page-title{font-size:22px;font-weight:700;color:#1a0a0f;margin-bottom:2px;}
.page-sub{font-size:13px;color:#8a6a4a;margin-bottom:24px;}
.stat-row{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px;}
.stat-card{background:#fff;border:1px solid #e8d8c0;border-top:4px solid #c9973a;border-radius:12px;padding:16px 20px;text-align:center;}
.stat-num{font-size:26px;font-weight:700;color:#1a0a0f;}
.stat-label{font-size:12px;color:#8a6a4a;margin-top:3px;}
.card-box{background:#fff;border:1px solid #e8d8c0;border-radius:14px;padding:20px;margin-bottom:24px;}
.card-title{font-size:16px;font-weight:600;color:#1a0a0f;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f0e4d0;display:flex;align-items:center;justify-content:space-between;}
.btn-gold{background:#c9973a;border:none;border-radius:8px;color:#fff;font-weight:600;padding:8px 18px;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;}
.btn-gold:hover{background:#a07828;color:#fff;}
.btn-edit{background:#e3f2fd;border:none;border-radius:6px;color:#1565c0;font-weight:600;padding:5px 10px;font-size:12px;cursor:pointer;}
.btn-del{background:#ffebee;border:none;border-radius:6px;color:#c62828;font-weight:600;padding:5px 10px;font-size:12px;cursor:pointer;text-decoration:none;display:inline-block;}
.btn-barcode{background:#e8f5e9;border:none;border-radius:6px;color:#2e7d32;font-weight:600;padding:5px 10px;font-size:12px;cursor:pointer;}
.btn-toggle-on{background:#e8f5e9;border:none;border-radius:6px;color:#2e7d32;font-weight:600;padding:5px 10px;font-size:12px;cursor:pointer;text-decoration:none;display:inline-block;}
.btn-toggle-off{background:#fff3e0;border:none;border-radius:6px;color:#e65c00;font-weight:600;padding:5px 10px;font-size:12px;cursor:pointer;text-decoration:none;display:inline-block;}
.table{color:#2a1a10;margin-bottom:0;}
.table thead th{background:#fdf5ec;color:#8a5a20;font-size:12px;font-weight:600;border-color:#e8d8c0;padding:10px 14px;}
.table tbody td{border-color:#f5ede0;font-size:13px;padding:10px 14px;vertical-align:middle;}
.table tbody tr:hover{background:#fdf5ec;}
.cat-badge{font-size:11px;padding:3px 10px;border-radius:99px;font-weight:500;}
.cat-cakes{background:#fce4ec;color:#880e4f;}
.cat-brownies{background:#fff8e1;color:#f57f17;}
.cat-cupcakes{background:#f3e5f5;color:#6a1b9a;}
.cat-pastries{background:#e8f5e9;color:#1b5e20;}
.cat-breads{background:#e3f2fd;color:#0d47a1;}
.stock-yes{background:#e8f5e9;color:#2e7d32;font-size:11px;padding:3px 10px;border-radius:99px;font-weight:600;}
.stock-no{background:#ffebee;color:#c62828;font-size:11px;padding:3px 10px;border-radius:99px;font-weight:600;}
.product-img{width:42px;height:42px;border-radius:8px;object-fit:cover;border:1px solid #e8d8c0;}
.product-img-placeholder{width:42px;height:42px;border-radius:8px;background:#fdf5ec;border:1px solid #e8d8c0;display:flex;align-items:center;justify-content:center;font-size:18px;}
.alert-box{border-radius:10px;padding:12px 16px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
.alert-success{background:#e8f5e9;border:1px solid #a5d6a7;border-left:4px solid #2e7d32;color:#1b5e20;}
.alert-deleted{background:#ffebee;border:1px solid #ffcdd2;border-left:4px solid #c62828;color:#b71c1c;}
.search-input{width:100%;border:1px solid #e8d8c0;border-radius:8px;font-size:13px;color:#2a1a10;padding:9px 14px;margin-bottom:14px;outline:none;}
.search-input:focus{border-color:#c9973a;}
.form-label{font-size:13px;color:#8a6a4a;font-weight:600;}
.form-control,.form-select{border:1px solid #e8d8c0;border-radius:8px;font-size:13px;color:#2a1a10;padding:9px 12px;}
.form-control:focus,.form-select:focus{border-color:#c9973a;box-shadow:0 0 0 3px rgba(201,151,58,0.15);}
.modal-content{border-radius:14px;border:1px solid #e8d8c0;}
.modal-header{background:#fdf5ec;border-bottom:1px solid #e8d8c0;border-radius:14px 14px 0 0;}

/* BARCODE PRINT MODAL */
.barcode-preview{text-align:center;padding:20px;background:#fff;border:1px solid #e8d8c0;border-radius:10px;margin:16px 0;}
.barcode-product-name{font-size:16px;font-weight:700;color:#1a0a0f;margin-bottom:4px;}
.barcode-product-price{font-size:14px;color:#c9973a;font-weight:600;margin-bottom:12px;}
.barcode-code{font-size:12px;color:#8a6a4a;margin-top:8px;letter-spacing:2px;}

/* BARCODE PRINT AREA */
@media print {
  body * { visibility: hidden; }
  #barcodePrintArea, #barcodePrintArea * { visibility: visible; }
  #barcodePrintArea { position: fixed; top: 0; left: 0; width: 100%; }
}
</style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<div class="main">
  <div class="page-title"><i class="bi bi-box-seam me-2" style="color:#c9973a;"></i>Products</div>
  <div class="page-sub">Manage bakery products and barcodes — <?= date('l, d F Y') ?></div>

  <?php if (isset($_GET['added'])): ?>
  <div class="alert-box alert-success"><i class="bi bi-check-circle-fill"></i> Product added!</div>
  <?php elseif (isset($_GET['updated'])): ?>
  <div class="alert-box alert-success"><i class="bi bi-check-circle-fill"></i> Product updated!</div>
  <?php elseif (isset($_GET['deleted'])): ?>
  <div class="alert-box alert-deleted"><i class="bi bi-trash-fill"></i> Product deleted!</div>
  <?php elseif (isset($_GET['toggled'])): ?>
  <div class="alert-box alert-success"><i class="bi bi-check-circle-fill"></i> Stock status updated!</div>
  <?php endif; ?>

  <!-- STATS -->
  <div class="stat-row">
    <div class="stat-card"><div class="stat-num"><?= $total_p ?></div><div class="stat-label">Total Products</div></div>
    <div class="stat-card" style="border-top-color:#2e7d32;"><div class="stat-num" style="color:#2e7d32;"><?= $avail_p ?></div><div class="stat-label">Available</div></div>
    <div class="stat-card" style="border-top-color:#ef5350;"><div class="stat-num" style="color:#ef5350;"><?= $out_p ?></div><div class="stat-label">Out of Stock</div></div>
  </div>

  <!-- PRODUCTS TABLE -->
  <div class="card-box">
    <div class="card-title">
      <span><i class="bi bi-list-ul me-2" style="color:#c9973a;"></i>All Products</span>
      <button class="btn-gold" onclick="openAddModal()"><i class="bi bi-plus-lg"></i> Add Product</button>
    </div>
    <input type="text" class="search-input" id="searchInput"
           placeholder="🔍 Search products..."
           onkeyup="searchProducts()">
    <div class="table-responsive">
      <table class="table table-hover" id="productsTable">
        <thead>
          <tr>
            <th>Image</th>
            <th>Product Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Barcode</th>
            <th>Stock</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($p = mysqli_fetch_assoc($products)):
            $barcode = $p['barcode'] ?: 'TDB'.str_pad($p['product_id'],6,'0',STR_PAD_LEFT);
          ?>
          <tr>
            <td>
              <?php if ($p['image'] && file_exists('../assets/images/'.$p['image'])): ?>
                <img src="../assets/images/<?= $p['image'] ?>" class="product-img">
              <?php else: ?>
                <div class="product-img-placeholder"><?= $icons[$p['category']]??'🍰' ?></div>
              <?php endif; ?>
            </td>
            <td>
              <strong><?= htmlspecialchars($p['name']) ?></strong>
              <?php if ($p['description']): ?>
                <br><small style="color:#8a6a4a;"><?= htmlspecialchars(substr($p['description'],0,40)) ?>...</small>
              <?php endif; ?>
            </td>
            <td><span class="cat-badge cat-<?= $p['category'] ?>"><?= ucfirst($p['category']) ?></span></td>
            <td><strong>Rs. <?= number_format($p['price'],2) ?></strong></td>
            <td>
              <div style="font-size:12px;font-weight:600;color:#1a0a0f;letter-spacing:1px;">
                <?= htmlspecialchars($barcode) ?>
              </div>
            </td>
            <td>
              <span class="<?= $p['stock_status']==='available'?'stock-yes':'stock-no' ?>">
                <?= $p['stock_status']==='available'?'Available':'Out of Stock' ?>
              </span>
            </td>
            <td>
              <div style="display:flex;gap:5px;flex-wrap:wrap;">
                <!-- Barcode Print -->
                <button class="btn-barcode"
                  onclick="showBarcode('<?= addslashes($p['name']) ?>',<?= $p['price'] ?>,'<?= $barcode ?>')"
                  title="Print Barcode">
                  <i class="bi bi-upc-scan"></i>
                </button>
                <!-- Toggle Stock -->
                <a href="products.php?toggle=<?= $p['product_id'] ?>"
                   class="<?= $p['stock_status']==='available'?'btn-toggle-on':'btn-toggle-off' ?>"
                   title="Toggle Stock">
                  <i class="bi <?= $p['stock_status']==='available'?'bi-toggle-on':'bi-toggle-off' ?>"></i>
                </a>
                <!-- Edit -->
                <button class="btn-edit"
                  onclick="openEditModal(<?= $p['product_id'] ?>,'<?= addslashes($p['name']) ?>','<?= $p['category'] ?>',<?= $p['price'] ?>,'<?= addslashes($p['description']??'') ?>','<?= $p['stock_status'] ?>','<?= $barcode ?>')"
                  title="Edit">
                  <i class="bi bi-pencil"></i>
                </button>
                <!-- Delete -->
                <a href="products.php?delete=<?= $p['product_id'] ?>"
                   class="btn-del"
                   onclick="return confirm('Delete this product?')"
                   title="Delete">
                  <i class="bi bi-trash"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ADD/EDIT PRODUCT MODAL -->
<div class="modal fade" id="productModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">
          <i class="bi bi-box-seam me-2" style="color:#c9973a;"></i>Add Product
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:24px;">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="product_id" id="product_id">
          <div class="mb-3">
            <label class="form-label">Product Name *</label>
            <input type="text" name="name" id="f_name" class="form-control" required>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label">Category</label>
              <select name="category" id="f_cat" class="form-select">
                <option value="cakes">🎂 Cakes</option>
                <option value="brownies">🍫 Brownies</option>
                <option value="cupcakes">🧁 Cupcakes</option>
                <option value="pastries">🥐 Pastries</option>
                <option value="breads">🍞 Breads</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">Price (Rs.) *</label>
              <input type="number" name="price" id="f_price" class="form-control" step="0.01" min="0" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Barcode
              <small style="color:#8a6a4a;font-weight:400;">(auto-generated if empty)</small>
            </label>
            <div style="display:flex;gap:8px;">
              <input type="text" name="barcode" id="f_barcode" class="form-control"
                     placeholder="e.g. TDB000001" style="font-family:monospace;letter-spacing:2px;">
              <button type="button" class="btn-gold" style="white-space:nowrap;" onclick="genBarcode()">
                <i class="bi bi-upc"></i> Auto
              </button>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" id="f_desc" class="form-control" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Product Image</label>
            <input type="file" name="image" class="form-control" accept="image/*">
          </div>
          <div class="mb-4">
            <label class="form-label">Stock Status</label>
            <select name="stock_status" id="f_status" class="form-select">
              <option value="available">✅ Available</option>
              <option value="out_of_stock">❌ Out of Stock</option>
            </select>
          </div>
          <button type="submit" class="btn-gold w-100" style="padding:12px;font-size:15px;justify-content:center;">
            <i class="bi bi-check-lg me-2"></i>Save Product
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- BARCODE PRINT MODAL -->
<div class="modal fade" id="barcodeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-upc-scan me-2" style="color:#c9973a;"></i>Product Barcode
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:20px;">
        <div class="barcode-preview" id="barcodePrintArea">
          <div class="barcode-product-name" id="bc-pname">—</div>
          <div class="barcode-product-price" id="bc-pprice">—</div>
          <svg id="barcodesvg"></svg>
          <div class="barcode-code" id="bc-code">—</div>
          <div style="font-size:11px;color:#c9973a;margin-top:6px;letter-spacing:1px;">THILHA DIVINE BAKES</div>
        </div>
        <div style="display:flex;gap:10px;margin-top:16px;">
          <button onclick="window.print()" class="btn-gold w-100" style="justify-content:center;">
            <i class="bi bi-printer me-2"></i>Print Barcode
          </button>
        </div>
        <div style="font-size:12px;color:#8a6a4a;text-align:center;margin-top:10px;">
          <i class="bi bi-info-circle me-1"></i>
          Scan this barcode in POS to add product instantly!
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ===== SEARCH =====
function searchProducts() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  document.querySelectorAll('#productsTable tbody tr').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

// ===== ADD MODAL =====
function openAddModal() {
  document.getElementById('modalTitle').innerHTML =
    '<i class="bi bi-plus-lg me-2" style="color:#c9973a;"></i>Add Product';
  ['product_id','f_name','f_price','f_desc','f_barcode'].forEach(id =>
    document.getElementById(id).value = '');
  document.getElementById('f_cat').value    = 'cakes';
  document.getElementById('f_status').value = 'available';
  new bootstrap.Modal(document.getElementById('productModal')).show();
}

// ===== EDIT MODAL =====
function openEditModal(id, name, cat, price, desc, status, barcode) {
  document.getElementById('modalTitle').innerHTML =
    '<i class="bi bi-pencil me-2" style="color:#c9973a;"></i>Edit Product';
  document.getElementById('product_id').value = id;
  document.getElementById('f_name').value     = name;
  document.getElementById('f_cat').value      = cat;
  document.getElementById('f_price').value    = price;
  document.getElementById('f_desc').value     = desc;
  document.getElementById('f_status').value   = status;
  document.getElementById('f_barcode').value  = barcode;
  new bootstrap.Modal(document.getElementById('productModal')).show();
}

// ===== AUTO GENERATE BARCODE =====
function genBarcode() {
  const ts  = Date.now().toString().slice(-6);
  const bc  = 'TDB' + ts;
  document.getElementById('f_barcode').value = bc;
}

// ===== SHOW BARCODE MODAL =====
function showBarcode(name, price, barcode) {
  document.getElementById('bc-pname').textContent  = name;
  document.getElementById('bc-pprice').textContent = 'Rs. ' + parseFloat(price).toFixed(2);
  document.getElementById('bc-code').textContent   = barcode;

  // Generate barcode using JsBarcode
  JsBarcode('#barcodesvg', barcode, {
    format:      'CODE128',
    width:       2,
    height:      60,
    displayValue: false,
    margin:      4,
    background:  '#ffffff',
    lineColor:   '#1a0a0f'
  });

  new bootstrap.Modal(document.getElementById('barcodeModal')).show();
}
</script>
</body>
</html>
