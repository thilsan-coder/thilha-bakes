<?php
require_once '../includes/db.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

if (isset($_GET['delete_sup'])) { $id=(int)$_GET['delete_sup']; mysqli_query($conn,"DELETE FROM suppliers WHERE supplier_id=$id"); redirect('suppliers.php?deleted=1'); }
if (isset($_GET['delete_po']))  { $id=(int)$_GET['delete_po'];  mysqli_query($conn,"DELETE FROM purchase_orders WHERE po_id=$id"); redirect('suppliers.php?po_deleted=1'); }

if (isset($_POST['update_po'])) {
    checkCSRF(); $po_id=(int)$_POST['po_id']; $status=e($conn,$_POST['po_status']);
    $recv = $status==='received' ? ", received_date=CURDATE()" : '';
    mysqli_query($conn,"UPDATE purchase_orders SET status='$status' $recv WHERE po_id=$po_id");
    redirect('suppliers.php?po_updated=1');
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_supplier'])) {
    checkCSRF();
    $name=e($conn,$_POST['name']); $company=e($conn,$_POST['company']??''); $phone=e($conn,$_POST['phone']??'');
    $email=e($conn,$_POST['email']??''); $address=e($conn,$_POST['address']??''); $cat=e($conn,$_POST['category']??'');
    $notes=e($conn,$_POST['notes']??''); $status=e($conn,$_POST['status']??'active');
    if (isset($_POST['supplier_id']) && $_POST['supplier_id']) {
        $id=(int)$_POST['supplier_id'];
        mysqli_query($conn,"UPDATE suppliers SET name='$name',company='$company',phone='$phone',email='$email',address='$address',category='$cat',notes='$notes',status='$status' WHERE supplier_id=$id");
        redirect('suppliers.php?updated=1');
    } else {
        mysqli_query($conn,"INSERT INTO suppliers (name,company,phone,email,address,category,notes,status) VALUES ('$name','$company','$phone','$email','$address','$cat','$notes','$status')");
        redirect('suppliers.php?added=1');
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_po'])) {
    checkCSRF();
    $sup_id=(int)$_POST['supplier_id_po']; $item=e($conn,$_POST['item_name']);
    $qty=(float)$_POST['quantity']; $unit=e($conn,$_POST['unit']); $uprice=(float)$_POST['unit_price'];
    $total=$qty*$uprice; $date=e($conn,$_POST['order_date']); $notes=e($conn,$_POST['po_notes']??'');
    mysqli_query($conn,"INSERT INTO purchase_orders (supplier_id,item_name,quantity,unit,unit_price,total_price,order_date,notes) VALUES ($sup_id,'$item',$qty,'$unit',$uprice,$total,'$date','$notes')");
    redirect('suppliers.php?po_added=1');
}

$suppliers = mysqli_query($conn,"SELECT s.*,COUNT(po.po_id) as total_orders,COALESCE(SUM(po.total_price),0) as total_spent FROM suppliers s LEFT JOIN purchase_orders po ON s.supplier_id=po.supplier_id GROUP BY s.supplier_id ORDER BY s.name");
$purchase_orders = mysqli_query($conn,"SELECT po.*,s.name as supplier_name,s.phone as supplier_phone FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id=s.supplier_id ORDER BY po.order_date DESC,po.po_id DESC");
$all_suppliers = mysqli_query($conn,"SELECT supplier_id,name,company FROM suppliers WHERE status='active' ORDER BY name");
$total_sup = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM suppliers WHERE status='active'"))['c'];
$pending_po= mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM purchase_orders WHERE status='ordered'"))['c'];
$total_pur = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(total_price),0) as amt FROM purchase_orders WHERE status='received'"))['amt'];
$this_mon  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(total_price),0) as amt FROM purchase_orders WHERE MONTH(order_date)=MONTH(NOW()) AND YEAR(order_date)=YEAR(NOW())"))['amt'];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Suppliers — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
*{box-sizing:border-box;}body{background:#f5f0eb;font-family:'Segoe UI',sans-serif;color:#2a1a10;margin:0;}
.page-title{font-size:22px;font-weight:700;color:#1a0a0f;margin-bottom:2px;}.page-sub{font-size:13px;color:#8a6a4a;margin-bottom:24px;}
.stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;}
.stat-card{background:#fff;border:1px solid #e8d8c0;border-top:4px solid #c9973a;border-radius:12px;padding:16px 18px;}
.stat-num{font-size:22px;font-weight:700;color:#1a0a0f;margin-bottom:3px;}.stat-label{font-size:12px;color:#8a6a4a;font-weight:500;}
.card-box{background:#fff;border:1px solid #e8d8c0;border-radius:14px;padding:20px;margin-bottom:20px;}
.supplier-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-bottom:24px;}
.sup-card{background:#fff;border:1px solid #e8d8c0;border-radius:14px;padding:18px;}
.sup-avatar{width:48px;height:48px;border-radius:50%;background:#fdf5ec;border:2px solid #c9973a;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#c9973a;flex-shrink:0;}
.sup-name{font-size:15px;font-weight:600;color:#1a0a0f;}.sup-company{font-size:12px;color:#8a6a4a;}
.sup-info{font-size:13px;color:#5a3a28;margin-top:10px;display:flex;flex-direction:column;gap:4px;}
.sup-info span{display:flex;align-items:center;gap:6px;}
.sup-stats{display:flex;gap:12px;margin-top:12px;padding-top:12px;border-top:1px solid #f0e4d0;}
.sup-stat .num{font-size:16px;font-weight:700;color:#c9973a;}.sup-stat .lbl{font-size:11px;color:#8a6a4a;}
.sup-actions{margin-top:12px;display:flex;gap:8px;}
.status-active{background:#e8f5e9;color:#2e7d32;font-size:11px;padding:2px 10px;border-radius:99px;font-weight:600;}
.status-inactive{background:#ffebee;color:#c62828;font-size:11px;padding:2px 10px;border-radius:99px;font-weight:600;}
.table{color:#2a1a10;margin-bottom:0;}.table thead th{background:#fdf5ec;color:#8a5a20;font-size:12px;font-weight:600;border-color:#e8d8c0;padding:10px 14px;}
.table tbody td{border-color:#f5ede0;font-size:13px;padding:10px 14px;vertical-align:middle;}.table tbody tr:hover{background:#fdf5ec;}
.po-ordered{background:#fff3e0;color:#e65c00;font-size:11px;padding:3px 10px;border-radius:99px;font-weight:600;}
.po-received{background:#e8f5e9;color:#2e7d32;font-size:11px;padding:3px 10px;border-radius:99px;font-weight:600;}
.po-cancelled{background:#ffebee;color:#c62828;font-size:11px;padding:3px 10px;border-radius:99px;font-weight:600;}
.btn-gold{background:#c9973a;border:none;border-radius:8px;color:#fff;font-weight:600;padding:8px 18px;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none;}
.btn-gold:hover{background:#a07828;color:#fff;}
.btn-edit{background:#e3f2fd;border:none;border-radius:6px;color:#1565c0;font-weight:600;padding:5px 12px;font-size:12px;cursor:pointer;flex:1;text-align:center;}
.btn-del{background:#ffebee;border:none;border-radius:6px;color:#c62828;font-weight:600;padding:5px 12px;font-size:12px;cursor:pointer;text-decoration:none;display:inline-block;}
.alert-box{border-radius:10px;padding:12px 16px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
.alert-success{background:#e8f5e9;border:1px solid #a5d6a7;border-left:4px solid #2e7d32;color:#1b5e20;}
.alert-deleted{background:#ffebee;border:1px solid #ffcdd2;border-left:4px solid #c62828;color:#b71c1c;}
.form-label{font-size:13px;color:#8a6a4a;font-weight:600;}.form-control,.form-select{border:1px solid #e8d8c0;border-radius:8px;font-size:13px;color:#2a1a10;padding:9px 12px;}
.form-control:focus,.form-select:focus{border-color:#c9973a;box-shadow:0 0 0 3px rgba(201,151,58,0.15);}
.modal-content{border-radius:14px;border:1px solid #e8d8c0;}.modal-header{background:#fdf5ec;border-bottom:1px solid #e8d8c0;border-radius:14px 14px 0 0;}
.tabs{display:flex;gap:8px;margin-bottom:24px;}
.tab-btn{padding:8px 20px;border-radius:99px;border:1px solid #e8d8c0;background:#fff;font-size:13px;font-weight:600;cursor:pointer;color:#8a6a4a;}
.tab-btn.active{background:#c9973a;border-color:#c9973a;color:#fff;}
.tab-content{display:none;}.tab-content.active{display:block;}
</style></head><body>
<?php include '../includes/sidebar.php'; ?>
<div class="main">
<div class="page-title"><i class="bi bi-building me-2" style="color:#c9973a;"></i>Supplier Management</div>
<div class="page-sub">Manage suppliers and purchase orders — <?= date('l, d F Y') ?></div>
<?php if(isset($_GET['added'])): ?><div class="alert-box alert-success"><i class="bi bi-check-circle-fill"></i> Supplier added!</div>
<?php elseif(isset($_GET['updated'])): ?><div class="alert-box alert-success"><i class="bi bi-check-circle-fill"></i> Updated!</div>
<?php elseif(isset($_GET['deleted'])): ?><div class="alert-box alert-deleted"><i class="bi bi-trash-fill"></i> Deleted!</div>
<?php elseif(isset($_GET['po_added'])): ?><div class="alert-box alert-success"><i class="bi bi-check-circle-fill"></i> Purchase order added!</div>
<?php elseif(isset($_GET['po_updated'])): ?><div class="alert-box alert-success"><i class="bi bi-check-circle-fill"></i> Order updated!</div>
<?php elseif(isset($_GET['po_deleted'])): ?><div class="alert-box alert-deleted"><i class="bi bi-trash-fill"></i> Order deleted!</div>
<?php endif; ?>
<div class="stat-row">
  <div class="stat-card"><div class="stat-label">Active Suppliers</div><div class="stat-num"><?= $total_sup ?></div></div>
  <div class="stat-card" style="border-top-color:#e65c00;"><div class="stat-label">Pending Orders</div><div class="stat-num" style="color:#e65c00;"><?= $pending_po ?></div></div>
  <div class="stat-card" style="border-top-color:#66bb6a;"><div class="stat-label">Total Purchased</div><div class="stat-num">Rs. <?= number_format($total_pur,0) ?></div></div>
  <div class="stat-card" style="border-top-color:#e8789a;"><div class="stat-label">This Month</div><div class="stat-num">Rs. <?= number_format($this_mon,0) ?></div></div>
</div>
<div class="tabs">
  <button class="tab-btn active" onclick="switchTab('suppliers',this)"><i class="bi bi-people me-1"></i> Suppliers</button>
  <button class="tab-btn" onclick="switchTab('orders',this)"><i class="bi bi-cart me-1"></i> Purchase Orders</button>
</div>
<div class="tab-content active" id="tab-suppliers">
  <div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
    <button class="btn-gold" onclick="openAddSupplier()"><i class="bi bi-plus-lg"></i> Add Supplier</button>
  </div>
  <div class="supplier-grid">
    <?php mysqli_data_seek($suppliers,0); while($s=mysqli_fetch_assoc($suppliers)): ?>
    <div class="sup-card">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:4px;">
        <div class="sup-avatar"><?= strtoupper(substr($s['name'],0,1)) ?></div>
        <div>
          <div class="sup-name"><?= htmlspecialchars($s['name']) ?></div>
          <div class="sup-company"><?= htmlspecialchars($s['company']??'') ?></div>
          <span class="status-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span>
        </div>
      </div>
      <div class="sup-info">
        <?php if($s['phone']): ?><span><i class="bi bi-telephone" style="color:#c9973a;"></i><?= $s['phone'] ?></span><?php endif; ?>
        <?php if($s['email']): ?><span><i class="bi bi-envelope" style="color:#c9973a;"></i><?= $s['email'] ?></span><?php endif; ?>
        <?php if($s['address']): ?><span><i class="bi bi-geo-alt" style="color:#c9973a;"></i><?= htmlspecialchars($s['address']) ?></span><?php endif; ?>
        <?php if($s['category']): ?><span><i class="bi bi-tag" style="color:#c9973a;"></i><?= ucfirst($s['category']) ?></span><?php endif; ?>
      </div>
      <div class="sup-stats">
        <div class="sup-stat"><div class="num"><?= $s['total_orders'] ?></div><div class="lbl">Orders</div></div>
        <div class="sup-stat"><div class="num">Rs. <?= number_format($s['total_spent'],0) ?></div><div class="lbl">Total Spent</div></div>
      </div>
      <div class="sup-actions">
        <button class="btn-edit" onclick="openEditSupplier(<?= $s['supplier_id'] ?>,'<?= addslashes($s['name']) ?>','<?= addslashes($s['company']??'') ?>','<?= $s['phone'] ?>','<?= $s['email'] ?>','<?= addslashes($s['address']??'') ?>','<?= $s['category'] ?>','<?= addslashes($s['notes']??'') ?>','<?= $s['status'] ?>')"><i class="bi bi-pencil me-1"></i>Edit</button>
        <a href="suppliers.php?delete_sup=<?= $s['supplier_id'] ?>" class="btn-del" onclick="return confirm('Delete?')"><i class="bi bi-trash me-1"></i>Delete</a>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
</div>
<div class="tab-content" id="tab-orders">
  <div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
    <button class="btn-gold" onclick="openAddPO()"><i class="bi bi-plus-lg"></i> New Purchase Order</button>
  </div>
  <div class="card-box">
    <div class="table-responsive"><table class="table table-hover">
      <thead><tr><th>PO #</th><th>Supplier</th><th>Item</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
        <?php if(mysqli_num_rows($purchase_orders)===0): ?><tr><td colspan="9" style="text-align:center;color:#8a6a4a;padding:24px;">No purchase orders!</td></tr>
        <?php else: while($po=mysqli_fetch_assoc($purchase_orders)): ?>
        <tr>
          <td><strong>#<?= $po['po_id'] ?></strong></td>
          <td><strong><?= htmlspecialchars($po['supplier_name']) ?></strong><?php if($po['supplier_phone']): ?><br><small style="color:#8a6a4a;"><?= $po['supplier_phone'] ?></small><?php endif; ?></td>
          <td><?= htmlspecialchars($po['item_name']) ?></td>
          <td><?= $po['quantity'] ?> <?= $po['unit'] ?></td>
          <td>Rs. <?= number_format($po['unit_price'],2) ?></td>
          <td><strong>Rs. <?= number_format($po['total_price'],2) ?></strong></td>
          <td style="font-size:12px;color:#8a6a4a;"><?= date('d M Y',strtotime($po['order_date'])) ?></td>
          <td>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
              <input type="hidden" name="po_id" value="<?= $po['po_id'] ?>">
              <input type="hidden" name="update_po" value="1">
              <select name="po_status" onchange="this.form.submit()" style="font-size:12px;border:1px solid #e8d8c0;border-radius:6px;padding:4px 8px;background:#fff;cursor:pointer;">
                <option value="ordered"   <?= $po['status']==='ordered'  ?'selected':'' ?>>Ordered</option>
                <option value="received"  <?= $po['status']==='received' ?'selected':'' ?>>Received</option>
                <option value="cancelled" <?= $po['status']==='cancelled'?'selected':'' ?>>Cancelled</option>
              </select>
            </form>
          </td>
          <td><a href="suppliers.php?delete_po=<?= $po['po_id'] ?>" class="btn-del" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a></td>
        </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table></div>
  </div>
</div>
</div>
<!-- SUPPLIER MODAL -->
<div class="modal fade" id="supplierModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="supModalTitle"><i class="bi bi-person-plus me-2" style="color:#c9973a;"></i>Add Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" style="padding:24px;"><form method="POST">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
      <input type="hidden" name="save_supplier" value="1">
      <input type="hidden" name="supplier_id" id="sup_id">
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" name="name" id="s_name" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Company</label><input type="text" name="company" id="s_company" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" id="s_phone" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" id="s_email" class="form-control"></div>
        <div class="col-12"><label class="form-label">Address</label><input type="text" name="address" id="s_address" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Category</label>
          <select name="category" id="s_cat" class="form-select"><option value="ingredients">Ingredients</option><option value="packaging">Packaging</option><option value="equipment">Equipment</option><option value="other">Other</option></select>
        </div>
        <div class="col-md-6"><label class="form-label">Status</label>
          <select name="status" id="s_status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select>
        </div>
        <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" id="s_notes" class="form-control" rows="2"></textarea></div>
      </div>
      <button type="submit" class="btn-gold w-100 mt-3" style="padding:12px;justify-content:center;"><i class="bi bi-check-lg me-2"></i>Save Supplier</button>
    </form></div>
  </div></div>
</div>
<!-- PO MODAL -->
<div class="modal fade" id="poModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-cart-plus me-2" style="color:#c9973a;"></i>New Purchase Order</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" style="padding:24px;"><form method="POST">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
      <input type="hidden" name="save_po" value="1">
      <div class="row g-3">
        <div class="col-12"><label class="form-label">Supplier *</label>
          <select name="supplier_id_po" class="form-select" required><option value="">Select Supplier</option>
            <?php mysqli_data_seek($all_suppliers,0); while($s=mysqli_fetch_assoc($all_suppliers)): ?><option value="<?= $s['supplier_id'] ?>"><?= htmlspecialchars($s['name']) ?><?= $s['company']?' — '.$s['company']:'' ?></option><?php endwhile; ?>
          </select>
        </div>
        <div class="col-12"><label class="form-label">Item Name *</label><input type="text" name="item_name" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Quantity *</label><input type="number" name="quantity" class="form-control" step="0.1" min="0" required oninput="calcPOTotal()"></div>
        <div class="col-md-4"><label class="form-label">Unit</label>
          <select name="unit" class="form-select"><option value="kg">kg</option><option value="g">g</option><option value="L">L</option><option value="pcs">pcs</option><option value="packets">packets</option><option value="boxes">boxes</option></select>
        </div>
        <div class="col-md-4"><label class="form-label">Unit Price *</label><input type="number" name="unit_price" id="po_unit_price" class="form-control" step="0.01" min="0" required oninput="calcPOTotal()"></div>
        <div class="col-12"><label class="form-label">Total</label><input type="text" id="po_total_display" class="form-control" readonly style="background:#fdf5ec;font-weight:700;color:#c9973a;"></div>
        <div class="col-12"><label class="form-label">Order Date *</label><input type="date" name="order_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
        <div class="col-12"><label class="form-label">Notes</label><textarea name="po_notes" class="form-control" rows="2"></textarea></div>
      </div>
      <button type="submit" class="btn-gold w-100 mt-3" style="padding:12px;justify-content:center;"><i class="bi bi-check-lg me-2"></i>Place Order</button>
    </form></div>
  </div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function switchTab(tab,btn){document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));document.querySelectorAll('.tab-content').forEach(c=>c.classList.remove('active'));btn.classList.add('active');document.getElementById('tab-'+tab).classList.add('active');}
function openAddSupplier(){
  document.getElementById('supModalTitle').innerHTML='<i class="bi bi-person-plus me-2" style="color:#c9973a;"></i>Add Supplier';
  ['sup_id','s_name','s_company','s_phone','s_email','s_address','s_notes'].forEach(id=>document.getElementById(id).value='');
  document.getElementById('s_cat').value='ingredients';document.getElementById('s_status').value='active';
  new bootstrap.Modal(document.getElementById('supplierModal')).show();
}
function openEditSupplier(id,name,company,phone,email,address,cat,notes,status){
  document.getElementById('supModalTitle').innerHTML='<i class="bi bi-pencil me-2" style="color:#c9973a;"></i>Edit Supplier';
  document.getElementById('sup_id').value=id;document.getElementById('s_name').value=name;document.getElementById('s_company').value=company;
  document.getElementById('s_phone').value=phone;document.getElementById('s_email').value=email;document.getElementById('s_address').value=address;
  document.getElementById('s_cat').value=cat;document.getElementById('s_notes').value=notes;document.getElementById('s_status').value=status;
  new bootstrap.Modal(document.getElementById('supplierModal')).show();
}
function openAddPO(){new bootstrap.Modal(document.getElementById('poModal')).show();}
function calcPOTotal(){
  const qty=parseFloat(document.querySelector('[name="quantity"]').value)||0;
  const price=parseFloat(document.getElementById('po_unit_price').value)||0;
  document.getElementById('po_total_display').value='Rs. '+(qty*price).toFixed(2);
}
</script>
</body></html>
