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
        // 테스크 추가
        $insertQuery = "
            INSERT INTO task (project_id, task_name, description, start, end, is_completed, Notification_Percentage)
            VALUES (?, ?, ?, ?, ?, 0, ?)
        ";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("issssi", $project_id, $task_name, $description, $start, $end, $notification_percentage);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // 히스토리 저장 여부 결정
            $shouldSaveHistory = $_POST['save_history'] ?? false;

            if ($shouldSaveHistory) {
                // 새로운 수정본 번호 계산
                $versionQuery = "SELECT COALESCE(MAX(version), 0) + 1 AS next_version FROM project_history WHERE project_id = ?";
                $versionStmt = $conn->prepare($versionQuery);
                $versionStmt->bind_param("i", $project_id);
                $versionStmt->execute();
                $versionResult = $versionStmt->get_result();
                $nextVersion = $versionResult->fetch_assoc()['next_version'];

                // 현재 프로젝트 정보 가져오기
                $projectQuery = "
                    SELECT project_name, description, start, end
                    FROM project
                    WHERE id = ?
                ";
                $projectStmt = $conn->prepare($projectQuery);
                $projectStmt->bind_param("i", $project_id);
                $projectStmt->execute();
                $projectResult = $projectStmt->get_result();
                $project = $projectResult->fetch_assoc();

                // 프로젝트 히스토리에 새로운 수정본 저장
                $historyInsertQuery = "
                    INSERT INTO project_history (project_id, version, manager_name, description, start, end, modified_date)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ";
                $managerName = $_SESSION['login_id']; // 현재 로그인된 사용자를 매니저로 기록
                $historyStmt = $conn->prepare($historyInsertQuery);
                $historyStmt->bind_param(
                    "iissss",
                    $project_id,
                    $nextVersion,
                    $managerName,
                    $project['description'],
                    $project['start'],
                    $project['end']
                );
                $historyStmt->execute();

                // 테스크 히스토리에 저장
                $taskHistoryQuery = "
                    INSERT INTO task_history (project_id, version, task_name, description)
                    SELECT project_id, ?, task_name, description
                    FROM task
                    WHERE project_id = ?
                ";
                $taskHistoryStmt = $conn->prepare($taskHistoryQuery);
                $taskHistoryStmt->bind_param("ii", $nextVersion, $project_id);
                $taskHistoryStmt->execute();
            }

            // 리다이렉트
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
            
            <input type="hidden" name="save_history" value="0">
            
            <button type="submit">추가</button>
        </form>
        <button onclick="location.href='m_project.php?project_id=<?php echo $project_id; ?>'">취소</button>
    </div>
</body>
</html>
