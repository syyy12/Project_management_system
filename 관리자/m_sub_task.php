<?php
session_start();
include 'db.php';

$sub_task_id = $_GET['sub_task_id'] ?? null;
$task_id = $_GET['task_id'] ?? null;

if (!$sub_task_id || !$task_id) {
    die("잘못된 접근입니다.");
}

// 삭제 요청이 있는 경우 처리
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    // 삭제 쿼리 실행
    $deleteQuery = "DELETE FROM sub_task WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $sub_task_id);

    if ($stmt->execute()) {
        // 삭제 성공 시 목록 페이지로 리다이렉트
        header("Location: m_task.php?task_id=$task_id&message=삭제가 완료되었습니다.");
        exit();
    } else {
        die("서브 테스크 삭제 중 문제가 발생했습니다: " . $conn->error);
    }
}

// 서브 테스크 정보 조회
$subTaskQuery = "
    SELECT st.sub_task_name, u.user_name, st.start, st.end, st.min_days, st.description, pst.sub_task_name AS pre_task_name
    FROM sub_task AS st
    LEFT JOIN User AS u ON st.login_id = u.login_id
    LEFT JOIN sub_task AS pst ON st.pre_sub_task_id = pst.id
    WHERE st.id = ?
";
$subTaskStmt = $conn->prepare($subTaskQuery);
$subTaskStmt->bind_param("i", $sub_task_id);
$subTaskStmt->execute();
$subTaskResult = $subTaskStmt->get_result();
$subTask = $subTaskResult->fetch_assoc();

// 모든 서브 테스크와 관계 정보 조회
$allSubTaskQuery = "
    SELECT st.id, st.sub_task_name, st.min_days, st.is_completed, pst.id AS pre_id, pst.sub_task_name AS pre_task_name
    FROM sub_task AS st
    LEFT JOIN sub_task AS pst ON st.pre_sub_task_id = pst.id
    WHERE st.task_id = ?
";
$allSubTaskStmt = $conn->prepare($allSubTaskQuery);
$allSubTaskStmt->bind_param("i", $task_id);
$allSubTaskStmt->execute();
$allSubTaskResult = $allSubTaskStmt->get_result();
$subTasks = [];
while ($row = $allSubTaskResult->fetch_assoc()) {
    $subTasks[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($subTask['sub_task_name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            font-size: 36px;
            color: #004d99;
            margin-bottom: 20px;
        }

        .info {
            font-size: 18px;
            line-height: 1.6;
        }

        .info p {
            margin: 10px 0;
        }

        .info p strong {
            color: #333;
        }

        .buttons {
            margin-top: 30px;
            text-align: right;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }

        button.primary {
            background-color: #004d99;
        }

        button.primary:hover {
            background-color: #003366;
        }

        .action-buttons {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }

        .action-buttons button {
            padding: 10px 15px;
            font-size: 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .action-buttons .edit {
            background-color: #5bc0de;
            color: white;
        }

        .action-buttons .edit:hover {
            background-color: #31b0d5;
        }

        .action-buttons .delete {
            background-color: #d9534f;
            color: white;
        }

        .action-buttons .delete:hover {
            background-color: #c9302c;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 서브 테스크 제목 -->
        <div style="position: relative;">
            <h2><?php echo htmlspecialchars($subTask['sub_task_name']); ?></h2>
            <!-- 수정 및 삭제 버튼 -->
            <div class="action-buttons">
                <button class="delete" onclick="if(confirm('정말 삭제하시겠습니까?')) location.href='?sub_task_id=<?php echo htmlspecialchars($sub_task_id); ?>&task_id=<?php echo htmlspecialchars($task_id); ?>&action=delete'">삭제</button>
            </div>
        </div>

        <!-- 서브 테스크 정보 -->
        <div class="info">
            <p><strong>담당자:</strong> <?php echo htmlspecialchars($subTask['user_name'] ?? '없음'); ?></p>
            <p><strong>시작일:</strong> <?php echo $subTask['start']; ?></p>
            <p><strong>종료일:</strong> <?php echo $subTask['end']; ?></p>
            <p><strong>설명:</strong> <?php echo htmlspecialchars($subTask['description']); ?></p>
            <p><strong>선행 테스크:</strong> <?php echo htmlspecialchars($subTask['pre_task_name'] ?? '없음'); ?></p>
            <p><strong>최소 소요일:</strong> <?php echo $subTask['min_days']; ?>일</p>
        </div>

        <!-- 뒤로 가기 버튼 -->
        <div class="buttons">
            <button class="primary" onclick="location.href='m_task.php?task_id=<?php echo htmlspecialchars($task_id); ?>'">뒤로 가기</button>
        </div>
    </div>
</body>
</html>
