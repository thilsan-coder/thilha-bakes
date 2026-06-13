<?php
require_once 'includes/db.php';
if (isLoggedIn()) {
    if (isAdmin())      redirect('admin/dashboard.php');
    elseif (isStaff())  redirect('staff/dashboard.php');
    else                redirect('customer/home.php');
}
redirect('login.php');
?>
