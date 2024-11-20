<?php
session_start();
include 'db.php';

$project_id = $_GET['project_id'] ?? null;
$version = $_GET['version'] ?? null;

if (!$project_id || !$version) {
    die("잘못된 접근입니다.");
}

// 수정본 정보 조회
$historyQuery = "
    SELECT manager_name, description, start, end
    FROM project_history
    WHERE project_id = ? AND version = ?
";
$historyStmt = $conn->prepare($historyQuery);
$historyStmt->bind_param("ii", $project_id, $version);
$historyStmt->execute();
$historyResult = $historyStmt->get_result();
$history = $historyResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $version; ?>차 수정본</title>
</head>
<body>
    <h2><?php echo $version; ?>차 수정본</h2>
    <p><strong>매니저:</strong> <?php echo htmlspecialchars($history['manager_name']); ?></p>
    <p><strong>설명:</strong> <?php echo htmlspecialchars($history['description']); ?></p>
    <p><strong>시작일:</strong> <?php echo $history['start']; ?></p>
    <p><strong>종료일:</strong> <?php echo $history['end']; ?></p>
    <button onclick="history.back()">뒤로 가기</button>
</body>
</html>
