<?php
session_start();
$conn = new mysqli("localhost", "root", "", "erp_system");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
?>