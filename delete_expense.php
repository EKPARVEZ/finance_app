<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('Location: expenses.php');
    exit();
}

$expense_id = $_GET['id'];

// Verify this expense belongs to the logged-in user before deleting
$stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
$stmt->execute([$expense_id, $user_id]);

// Set success message
$_SESSION['message'] = 'Expense deleted successfully!';
$_SESSION['message_type'] = 'success';

// Redirect back to expenses page
header('Location: expenses.php');
exit();
?>