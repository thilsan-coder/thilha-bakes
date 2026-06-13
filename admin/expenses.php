<?php
require_once '../includes/db.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM expenses WHERE expense_id=$id");
    redirect('expenses.php?deleted=1');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    $cat    = e($conn, $_POST['category']);
    $desc   = e($conn, $_POST['description']);
    $amount = (float)$_POST['amount'];
    $date   = e($conn, $_POST['expense_date']);
    $method = e($conn, $_POST['payment_method']);
    $ref    = e($conn, $_POST['reference'] ?? '');
    $uid    = (int)$_SESSION['user_id'];
    if (isset($_POST['expense_id']) && $_POST['expense_id']) {
        $id = (int)$_POST['expense_id'];
        mysqli_query($conn, "UPDATE expenses SET category='$cat',description='$desc',amount=$amount,expense_date='$date',payment_method='$method',reference='$ref' WHERE expense_id=$id");
        redirect('expenses.php?updated=1');
    } else {
        mysqli_query($conn, "INSERT INTO expenses (category,description,amount,expense_date,payment_method,reference,added_by) VALUES ('$cat','$desc',$amount,'$date','$method','$ref',$uid)");
        redirect('expenses.php?added=1');
    }
}

$from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$to   = isset($_GET['to'])   ? $_GET['to']   : date('Y-m-d');
$cf   = isset($_GET['cat'])  ? $_GET['cat']  : '';
$where = "WHERE expense_date BETWEEN '$from' AND '$to'";
if ($cf) $where .= " AND category='$cf'";
$expenses      = mysqli_query($conn, "SELECT * FROM expenses $where ORDER BY expense_date DESC");
$total_expense = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as amt FROM expenses WHERE expense_date BETWEEN '$from' AND '$to'"))["amt"];
$today_expense = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as amt FROM expenses WHERE expense_date=CURDATE()"))["amt"];
$month_expense = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as amt FROM expenses WHERE MONTH(expense_date)=MONTH(NOW()) AND YEAR(expense_date)=YEAR(NOW())"))["amt"];
$revenue       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(b.total),0) as amt FROM bills b JOIN orders o ON b.order_id=o.order_id WHERE DATE(o.created_at) BETWEEN '$from' AND '$to'"))["amt"];
$profit = (float)$revenue - (float)$total_expense;
$by_cat = mysqli_query($conn, "SELECT category, SUM(amount) as total FROM expenses WHERE expense_date BETWEEN '$from' AND '$to' GROUP BY category ORDER BY total DESC");
$cat_labels=[]; $cat_data=[]; $cat_colors=[];
$colors=['#c9973a','#e8789a','#5090d0','#50c090','#c070d0','#ef5350','#66bb6a']; $ci=0;
while($r=mysqli_fetch_assoc($by_cat)){$cat_labels[]=ucfirst($r['category']);$cat_data[]=(float)$r['total'];$cat_colors[]=$colors[$ci%count($colors)];$ci++;}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Expenses — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*{box-sizing:border-box;}body{background:#f5f0eb;font-family:'Segoe UI',sans-serif;color:#2a1a10;margin:0;}
.page-title{font-size:22px;font-weight:700;color:#1a0a0f;margin-bottom:2px;}.page-sub{font-size:13px;color:#8a6a4a;margin-bottom:24px;}
.stat-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:24px;}
.stat-card{background:#fff;border:1px solid #e8d8c0;border-top:4px solid #c9973a;border-radius:12px;padding:16px 18px;}
.stat-num{font-size:22px;font-weight:700;color:#1a0a0f;margin-bottom:3px;}.stat-label{font-size:12px;color:#8a6a4a;font-weight:500;}
.card-box{background:#fff;border:1px solid #e8d8c0;border-radius:14px;padding:20px;margin-bottom:20px;}
.card-title{font-size:16px;font-weight:600;color:#1a0a0f;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f0e4d0;display:flex;align-items:center;justify-content:space-between;}
.filter-card{background:#fff;border:1px solid #e8d8c0;border-radius:12px;padding:16px 20px;margin-bottom:20px;}
.form-control,.form-select{border:1px solid #e8d8c0;border-radius:8px;font-size:13px;color:#2a1a10;padding:8px 12px;}
.form-control:focus,.form-select:focus{border-color:#c9973a;box-shadow:0 0 0 3px rgba(201,151,58,0.15);}
.form-label{font-size:12px;color:#8a6a4a;font-weight:600;}
.btn-gold{background:#c9973a;border:none;border-radius:8px;color:#fff;font-weight:600;padding:8px 18px;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none;}
.btn-gold:hover{background:#a07828;color:#fff;}
.btn-outline{background:#fff;border:1px solid #c9973a;border-radius:8px;color:#c9973a;font-weight:600;padding:8px 16px;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
.btn-edit{background:#e3f2fd;border:none;border-radius:6px;color:#1565c0;font-weight:600;padding:5px 12px;font-size:12px;cursor:pointer;}
.btn-del{background:#ffebee;border:none;border-radius:6px;color:#c62828;font-weight:600;padding:5px 12px;font-size:12px;cursor:pointer;text-decoration:none;display:inline-block;}
.table{color:#2a1a10;margin-bottom:0;}.table thead th{background:#fdf5ec;color:#8a5a20;font-size:12px;font-weight:600;border-color:#e8d8c0;padding:10px 14px;}
.table tbody td{border-color:#f5ede0;font-size:13px;padding:10px 14px;vertical-align:middle;}.table tbody tr:hover{background:#fdf5ec;}
.cat-pill{font-size:11px;padding:3px 10px;border-radius:99px;font-weight:500;}
.cat-ingredients{background:#fff8e1;color:#f57f17;}.cat-utilities{background:#e3f2fd;color:#1565c0;}
.cat-packaging{background:#f3e5f5;color:#6a1b9a;}.cat-equipment{background:#e8f5e9;color:#2e7d32;}
.cat-salary{background:#fce4ec;color:#880e4f;}.cat-rent{background:#fff3e0;color:#e65c00;}.cat-other{background:#f5f5f5;color:#616161;}
.pay-pill{font-size:11px;padding:3px 9px;border-radius:99px;font-weight:500;}
.pay-cash{background:#fff8e1;color:#f57f17;}.pay-card{background:#e3f2fd;color:#1565c0;}.pay-online{background:#e8f5e9;color:#2e7d32;}
.alert-box{border-radius:10px;padding:12px 16px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
.alert-success{background:#e8f5e9;border:1px solid #a5d6a7;border-left:4px solid #2e7d32;color:#1b5e20;}
.alert-deleted{background:#ffebee;border:1px solid #ffcdd2;border-left:4px solid #c62828;color:#b71c1c;}
.modal-content{border-radius:14px;border:1px solid #e8d8c0;}.modal-header{background:#fdf5ec;border-bottom:1px solid #e8d8c0;border-radius:14px 14px 0 0;}
</style></head><body>
<?php include '../includes/sidebar.php'; ?>
<div class="main">
<div class="page-title"><i class="bi bi-wallet2 me-2" style="color:#c9973a;"></i>Expense Tracker</div>
<div class="page-sub">Track and manage all bakery expenses — <?= date('l, d F Y') ?></div>
<?php if(isset($_GET['added'])): ?><div class="alert-box alert-success"><i class="bi bi-check-circle-fill"></i> Expense added!</div>
<?php elseif(isset($_GET['updated'])): ?><div class="alert-box alert-success"><i class="bi bi-check-circle-fill"></i> Updated!</div>
<?php elseif(isset($_GET['deleted'])): ?><div class="alert-box alert-deleted"><i class="bi bi-trash-fill"></i> Deleted!</div>
<?php endif; ?>
<div style="background:<?= $profit>=0?'#e8f5e9':'#ffebee' ?>;border:1px solid <?= $profit>=0?'#a5d6a7':'#ffcdd2' ?>;border-radius:12px;padding:16px 20px;display:flex;align-items:center;gap:14px;margin-bottom:20px;">
  <div style="width:48px;height:48px;border-radius:12px;background:<?= $profit>=0?'#2e7d32':'#c62828' ?>;display:flex;align-items:center;justify-content:center;font-size:22px;"><?= $profit>=0?'📈':'📉' ?></div>
  <div>
    <div style="font-size:14px;font-weight:600;color:<?= $profit>=0?'#1b5e20':'#b71c1c' ?>;"><?= $profit>=0?'Profit':'Loss' ?> — Selected Period</div>
    <div style="font-size:22px;font-weight:700;color:<?= $profit>=0?'#2e7d32':'#c62828' ?>;">Rs. <?= number_format(abs($profit),2) ?></div>
    <div style="font-size:12px;color:#8a6a4a;">Revenue: Rs. <?= number_format($revenue,2) ?> &nbsp;·&nbsp; Expenses: Rs. <?= number_format($total_expense,2) ?></div>
  </div>
</div>
<div class="stat-row">
  <div class="stat-card"><div class="stat-label">Today</div><div class="stat-num" style="color:#ef5350;">Rs. <?= number_format($today_expense,0) ?></div></div>
  <div class="stat-card" style="border-top-color:#e8789a;"><div class="stat-label">This Month</div><div class="stat-num">Rs. <?= number_format($month_expense,0) ?></div></div>
  <div class="stat-card" style="border-top-color:#42a5f5;"><div class="stat-label">Period Total</div><div class="stat-num">Rs. <?= number_format($total_expense,0) ?></div></div>
  <div class="stat-card" style="border-top-color:<?= $profit>=0?'#66bb6a':'#ef5350' ?>;"><div class="stat-label">Net <?= $profit>=0?'Profit':'Loss' ?></div><div class="stat-num" style="color:<?= $profit>=0?'#2e7d32':'#ef5350' ?>;">Rs. <?= number_format(abs($profit),0) ?></div></div>
</div>
<div class="filter-card">
  <form method="GET" class="row g-2 align-items-end">
    <div class="col-md-2"><label class="form-label">From</label><input type="date" name="from" class="form-control" value="<?= $from ?>"></div>
    <div class="col-md-2"><label class="form-label">To</label><input type="date" name="to" class="form-control" value="<?= $to ?>"></div>
    <div class="col-md-3"><label class="form-label">Category</label>
      <select name="cat" class="form-select"><option value="">All</option>
        <?php foreach(['ingredients','utilities','packaging','equipment','salary','rent','other'] as $c): ?>
        <option value="<?= $c ?>" <?= $cf===$c?'selected':'' ?>><?= ucfirst($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-5 d-flex gap-2 flex-wrap">
      <button type="submit" class="btn-gold"><i class="bi bi-funnel"></i> Filter</button>
      <a href="expenses.php" class="btn-outline">Reset</a>
      <a href="expenses.php?from=<?= date('Y-m-d',strtotime('-7 days')) ?>&to=<?= date('Y-m-d') ?>" class="btn-outline">7 Days</a>
    </div>
  </form>
</div>
<div class="row g-3">
  <div class="col-md-8">
    <div class="card-box">
      <div class="card-title"><span><i class="bi bi-list-ul me-2" style="color:#c9973a;"></i>All Expenses</span>
        <button class="btn-gold" onclick="openAddModal()"><i class="bi bi-plus-lg"></i> Add</button>
      </div>
      <div class="table-responsive"><table class="table table-hover">
        <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th>Payment</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if(mysqli_num_rows($expenses)===0): ?><tr><td colspan="6" style="text-align:center;color:#8a6a4a;padding:24px;">No expenses!</td></tr>
          <?php else: while($ex=mysqli_fetch_assoc($expenses)): ?>
          <tr>
            <td style="font-size:12px;color:#8a6a4a;"><?= date('d M Y',strtotime($ex['expense_date'])) ?></td>
            <td><span class="cat-pill cat-<?= $ex['category'] ?>"><?= ucfirst($ex['category']) ?></span></td>
            <td><?= htmlspecialchars($ex['description']) ?><?php if($ex['reference']): ?><br><small style="color:#8a6a4a;">Ref: <?= htmlspecialchars($ex['reference']) ?></small><?php endif; ?></td>
            <td><strong style="color:#ef5350;">Rs. <?= number_format($ex['amount'],2) ?></strong></td>
            <td><span class="pay-pill pay-<?= $ex['payment_method'] ?>"><?= ucfirst($ex['payment_method']) ?></span></td>
            <td style="display:flex;gap:6px;">
              <button class="btn-edit" onclick="openEditModal(<?= $ex['expense_id'] ?>,'<?= addslashes($ex['category']) ?>','<?= addslashes($ex['description']) ?>',<?= $ex['amount'] ?>,'<?= $ex['expense_date'] ?>','<?= $ex['payment_method'] ?>','<?= addslashes($ex['reference']) ?>')"><i class="bi bi-pencil"></i></button>
              <a href="expenses.php?delete=<?= $ex['expense_id'] ?>" class="btn-del" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
            </td>
          </tr>
          <?php endwhile; endif; ?>
        </tbody>
      </table></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card-box">
      <div class="card-title"><span><i class="bi bi-pie-chart me-2" style="color:#c9973a;"></i>By Category</span></div>
      <?php if(!empty($cat_data)): ?><canvas id="catChart" height="220" style="margin-bottom:16px;"></canvas>
      <?php $bc=mysqli_query($conn,"SELECT category,SUM(amount) as total FROM expenses WHERE expense_date BETWEEN '$from' AND '$to' GROUP BY category ORDER BY total DESC");
      while($r=mysqli_fetch_assoc($bc)):$pct=$total_expense>0?round(($r['total']/$total_expense)*100):0; ?>
      <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f5ede0;font-size:13px;">
        <span class="cat-pill cat-<?= $r['category'] ?>"><?= ucfirst($r['category']) ?></span>
        <span style="font-weight:600;">Rs. <?= number_format($r['total'],2) ?></span>
        <span style="color:#8a6a4a;"><?= $pct ?>%</span>
      </div>
      <?php endwhile; ?>
      <?php else: ?><div style="text-align:center;padding:30px;color:#8a6a4a;font-size:13px;">No data</div><?php endif; ?>
    </div>
  </div>
</div>
</div>
<div class="modal fade" id="expenseModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="modalTitle"><i class="bi bi-wallet2 me-2" style="color:#c9973a;"></i>Add Expense</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" style="padding:24px;"><form method="POST">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
      <input type="hidden" name="expense_id" id="expense_id">
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Category *</label>
          <select name="category" id="f_cat" class="form-select">
            <?php foreach(['ingredients','utilities','packaging','equipment','salary','rent','other'] as $c): ?><option value="<?= $c ?>"><?= ucfirst($c) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6"><label class="form-label">Amount (Rs.) *</label><input type="number" name="amount" id="f_amount" class="form-control" step="0.01" min="0" required></div>
        <div class="col-12"><label class="form-label">Description *</label><input type="text" name="description" id="f_desc" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Date *</label><input type="date" name="expense_date" id="f_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
        <div class="col-md-6"><label class="form-label">Payment</label>
          <select name="payment_method" id="f_pay" class="form-select"><option value="cash">Cash</option><option value="card">Card</option><option value="online">Online</option></select>
        </div>
        <div class="col-12"><label class="form-label">Reference</label><input type="text" name="reference" id="f_ref" class="form-control"></div>
      </div>
      <button type="submit" class="btn-gold w-100 mt-3" style="padding:12px;justify-content:center;"><i class="bi bi-check-lg me-2"></i>Save Expense</button>
    </form></div>
  </div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
<?php if(!empty($cat_data)): ?>
new Chart(document.getElementById('catChart').getContext('2d'),{type:'doughnut',data:{labels:<?= json_encode($cat_labels) ?>,datasets:[{data:<?= json_encode($cat_data) ?>,backgroundColor:<?= json_encode($cat_colors) ?>,borderWidth:2,borderColor:'#fff'}]},options:{plugins:{legend:{position:'bottom',labels:{color:'#2a1a10',font:{size:11},padding:8}}}}});
<?php endif; ?>
function openAddModal(){
  document.getElementById('modalTitle').innerHTML='<i class="bi bi-plus-lg me-2" style="color:#c9973a;"></i>Add Expense';
  ['expense_id','f_amount','f_desc','f_ref'].forEach(id=>document.getElementById(id).value='');
  document.getElementById('f_cat').value='ingredients';document.getElementById('f_date').value='<?= date("Y-m-d") ?>';document.getElementById('f_pay').value='cash';
  new bootstrap.Modal(document.getElementById('expenseModal')).show();
}
function openEditModal(id,cat,desc,amount,date,pay,ref){
  document.getElementById('modalTitle').innerHTML='<i class="bi bi-pencil me-2" style="color:#c9973a;"></i>Edit Expense';
  document.getElementById('expense_id').value=id;document.getElementById('f_cat').value=cat;document.getElementById('f_desc').value=desc;
  document.getElementById('f_amount').value=amount;document.getElementById('f_date').value=date;document.getElementById('f_pay').value=pay;document.getElementById('f_ref').value=ref;
  new bootstrap.Modal(document.getElementById('expenseModal')).show();
}
</script>
</body></html>
