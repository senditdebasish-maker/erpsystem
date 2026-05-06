<?php include '../includes/db.php';
$conn->query("INSERT INTO expenses (description, amount) VALUES ('{$_POST['desc']}', '{$_POST['amount']}')");
header("Location: ../expenses.php");
?>