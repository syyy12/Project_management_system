<?php
# 2024 11 21 : 15시 수정 보안성 View 추가, 로그인 상시 확인

session_start();
include 'db.php';

// 로그인 여부 확인
if (!isset($_SESSION['login_id'])) {
    header("Location: login.php");
    exit();
}

$task_id = $_GET['task_id'] ?? null;
if (!$task_id) {
    die("잘못된 접근입니다.");
}

// 테스크 정보 조회
$taskQuery = "
    SELECT task_name, description, start, end, is_completed
    FROM task
    WHERE id = ?
";
$taskStmt = $conn->prepare($taskQuery);
$taskStmt->bind_param("i", $task_id);
$taskStmt->execute();
$taskResult = $taskStmt->get_result();
$task = $taskResult->fetch_assoc();

// 서브 테스크 목록 조회
$subTaskQuery = "
    SELECT DISTINCT id, sub_task_name, is_completed
    FROM sub_task
    WHERE task_id = ?
";
$subTaskStmt = $conn->prepare($subTaskQuery);
$subTaskStmt->bind_param("i", $task_id);
$subTaskStmt->execute();
$subTaskResult = $subTaskStmt->get_result();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($task['task_name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 90%;
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
            margin-bottom: 30px;
        }

        .info p {
            font-size: 18px;
            line-height: 1.6;
            margin: 10px 0;
        }

        .sub-tasks {
            margin-top: 30px;
        }

        .sub-tasks h3 {
            font-size: 24px;
            color: #4CAF50;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        ul {
            list-style: none;
            padding: 0;
        }

        ul li {
            font-size: 18px;
            margin: 10px 0;
        }

        ul li a {
            text-decoration: none;
            font-weight: bold;
            color: #004d99;
        }

        ul li a:hover {
            text-decoration: underline;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            color: white;
        }

        .completed {
            background-color: #4CAF50;
        }

        .in-progress {
            background-color: #FFC107;
        }

        .buttons {
            margin-top: 20px;
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
    </style>
</head>
<body>
    <div class="container">
        <!-- 테스크 정보 -->
        <h2><?php echo htmlspecialchars($task['task_name']); ?></h2>
        <div class="info">
            <p><strong>설명:</strong> <?php echo htmlspecialchars($task['description']); ?></p>
            <p><strong>시작일:</strong> <?php echo $task['start']; ?></p>
            <p><strong>종료일:</strong> <?php echo $task['end']; ?></p>
            <p><strong>완료 여부:</strong> 
                <span class="status <?php echo $task['is_completed'] ? 'completed' : 'in-progress'; ?>">
                    <?php echo $task['is_completed'] ? '완료' : '진행 중'; ?>
                </span>
            </p>
        </div>

        <!-- 서브 테스크 목록 -->
        <div class="sub-tasks">
            <h3>서브 테스크 목록</h3>
            <ul>
                <?php
                if ($subTaskResult->num_rows > 0) {
                    while ($subTask = $subTaskResult->fetch_assoc()) {
                        $subTaskName = htmlspecialchars($subTask['sub_task_name']);
                        $subTaskId = $subTask['id'];
                        $isCompleted = $subTask['is_completed'] ? 'completed' : 'in-progress';
                        echo "<li>
                            <a href='sub_task.php?sub_task_id=$subTaskId&task_id=$task_id'>$subTaskName</a> 
                            <span class='status $isCompleted'>
                                " . ($subTask['is_completed'] ? '완료' : '진행 중') . "
                            </span>
                        </li>";
                    }
                } else {
                    echo "<li>서브 테스크가 없습니다.</li>";
                }
                ?>
            </ul>
        </div>

        <!-- 버튼 -->
        <div class="buttons">
            <button class="primary" onclick="location.href='home.php'">뒤로 가기</button>
        </div>
    </div>
</body>
</html>
