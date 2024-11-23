# 2024 11 24 : 03시 수정 진행여부 표시 추가
<?php
session_start();
include 'db.php';

// 로그인 여부 확인
if (!isset($_SESSION['login_id'])) {
    header("Location: login.php");
    exit();
}

$login_id = $_SESSION['login_id']; // 현재 로그인된 유저 ID
$sub_task_id = $_GET['sub_task_id'] ?? null;
$task_id = $_GET['task_id'] ?? null;
if (!$sub_task_id || !$task_id) {
    die("잘못된 접근입니다.");
}

// 메시지 변수 초기화
$message = "";

// 승인 대기 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['is_completed'])) {
    $is_completed = $_POST['is_completed'];

    // `is_completed` 업데이트
    $updateQuery = "UPDATE sub_task SET is_completed = ? WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ii", $is_completed, $sub_task_id);

    if ($stmt->execute()) {
        $message = "승인대기 상태로 변경되었습니다.";
    } else {
        $message = "승인 상태 변경에 실패했습니다.";
    }

    $stmt->close();
}

// 서브 테스크 정보 조회
$subTaskQuery = "
    SELECT st.sub_task_name, u.user_name, st.start, st.end, st.min_days, st.description, st.login_id, pst.sub_task_name AS pre_task_name
    FROM sub_task AS st
    LEFT JOIN user_view AS u ON st.login_id = u.login_id
    LEFT JOIN sub_task AS pst ON st.pre_sub_task_id = pst.id
    WHERE st.id = ?
";
$subTaskStmt = $conn->prepare($subTaskQuery);
$subTaskStmt->bind_param("i", $sub_task_id);
$subTaskStmt->execute();
$subTaskResult = $subTaskStmt->get_result();
$subTask = $subTaskResult->fetch_assoc();

if (!$subTask) {
    die("서브 테스크를 찾을 수 없습니다.");
}

$isOwner = ($login_id === $subTask['login_id']);
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
            max-width: 1000px;
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

        button.secondary {
            background-color: #d9534f;
        }

        button.secondary:hover {
            background-color: #c9302c;
        }

        .message {
            font-size: 18px;
            margin-top: 20px;
            color: green;
        }

        .message.error {
            color: red;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- 서브 테스크 제목 -->
    <h2><?php echo htmlspecialchars($subTask['sub_task_name']); ?></h2>

    <!-- 메시지 표시 -->
    <?php if ($message): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- 서브 테스크 정보 -->
    <div class="info">
        <p><strong>담당자:</strong> <?php echo htmlspecialchars($subTask['user_name'] ?? '없음'); ?></p>
        <p><strong>시작일:</strong> <?php echo $subTask['start']; ?></p>
        <p><strong>종료일:</strong> <?php echo $subTask['end']; ?></p>
        <p><strong>설명:</strong> <?php echo htmlspecialchars($subTask['description']); ?></p>
        <p><strong>선행 테스크:</strong> <?php echo htmlspecialchars($subTask['pre_task_name'] ?? '없음'); ?></p>
        <p><strong>최소 소요일:</strong> <?php echo $subTask['min_days']; ?>일</p>
    </div>

    <!-- 버튼 -->
    <div class="buttons">
        <?php if ($isOwner): ?>
            <!-- 승인 대기 버튼 -->
            <form method="POST" style="display: inline;">
                <input type="hidden" name="is_completed" value="2">
                <button type="submit" class="primary">승인 대기</button>
            </form>
        <?php else: ?>
            <!-- 경고 문구 -->
            <p style="color: red; font-weight: bold; margin-bottom: 10px;">
                이 작업의 담당자가 아니므로 승인할 수 없습니다.
            </p>
        <?php endif; ?>

        <!-- 뒤로 가기 버튼 -->
        <button class="primary" onclick="location.href='task.php?task_id=<?php echo htmlspecialchars($task_id); ?>'">뒤로 가기</button>
    </div>
</div>
</body>
</html>

