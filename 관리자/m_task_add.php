<?php
session_start();
include 'db.php';

// 로그인 여부 확인
if (!isset($_SESSION['login_id'])) {
    header("Location: login.php");
    exit();
}

$project_id = $_GET['project_id'] ?? null;
if (!$project_id) {
    die("잘못된 접근입니다.");
}

// 테스크 추가 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_name = $_POST['task_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $start = $_POST['start'] ?? null;
    $end = $_POST['end'] ?? null;
    $notification_percentage = $_POST['notification_percentage'] ?? null;

    if ($task_name && $start && $end && is_numeric($notification_percentage)) {
        $insertQuery = "
            INSERT INTO task (project_id, task_name, description, start, end, is_completed, Notification_Percentage)
            VALUES (?, ?, ?, ?, ?, 0, ?)
        ";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("issssi", $project_id, $task_name, $description, $start, $end, $notification_percentage);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            header("Location: m_project.php?project_id=$project_id");
            exit();
        } else {
            $error = "테스크 추가 중 오류가 발생했습니다.";
        }
    } else {
        $error = "모든 필드를 올바르게 입력해주세요.";
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>테스크 추가</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 20px; }
        .form-container { max-width: 500px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        .form-container h1 { font-size: 24px; color: #004d99; margin-bottom: 20px; }
        .form-container label { display: block; margin-bottom: 8px; font-weight: bold; }
        .form-container input, .form-container textarea, .form-container button {
            width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px;
        }
        .form-container button { background-color: #004d99; color: white; border: none; cursor: pointer; }
        .form-container button:hover { background-color: #003366; }
        .error { color: red; font-size: 14px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>테스크 추가</h1>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="task_name">테스크 이름</label>
            <input type="text" name="task_name" id="task_name" required>
            
            <label for="description">설명</label>
            <textarea name="description" id="description"></textarea>
            
            <label for="start">시작 날짜</label>
            <input type="date" name="start" id="start" required>
            
            <label for="end">종료 날짜</label>
            <input type="date" name="end" id="end" required>
            
            <label for="notification_percentage">알림 비율 (%)</label>
            <input type="number" name="notification_percentage" id="notification_percentage" min="0" max="100" required>
            
            <button type="submit">추가</button>
        </form>
        <button onclick="location.href='m_project.php?project_id=<?php echo $project_id; ?>'">취소</button>
    </div>
</body>
</html>
