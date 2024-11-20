<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("데이터베이스 연결 실패: " . $conn->connect_error);
}
?>
