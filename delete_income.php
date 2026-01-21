<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header('Location: income.php');
    exit();
}

$income_id = $_GET['id'];

// Verify this income belongs to the logged-in user before deleting
$stmt = $pdo->prepare("DELETE FROM income WHERE id = ? AND user_id = ?");
$stmt->execute([$income_id, $user_id]);

// Redirect back to income page
header('Location: income.php');
exit();
?>