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

// 프로젝트 기본 정보 조회
$projectQuery = "
    SELECT project_name, description, start, end, finish
    FROM project
    WHERE id = ?
";
$projectStmt = $conn->prepare($projectQuery);
$projectStmt->bind_param("i", $project_id);
$projectStmt->execute();
$projectResult = $projectStmt->get_result();
$project = $projectResult->fetch_assoc();

// 테스크 목록 조회
$taskQuery = "
    SELECT task_name, is_completed
    FROM task
    WHERE project_id = ?
";
$taskStmt = $conn->prepare($taskQuery);
$taskStmt->bind_param("i", $project_id);
$taskStmt->execute();
$taskResult = $taskStmt->get_result();

// 진행 상태 계산
$totalTasks = 0;
$completedTasks = 0;
$tasks = [];

while ($task = $taskResult->fetch_assoc()) {
    $totalTasks++;
    if ((int)$task['is_completed'] === 3) { // 완료 상태
        $completedTasks++;
    }
    $tasks[] = $task;
}
$progress = ($totalTasks > 0) ? round(($completedTasks / $totalTasks) * 100, 1) : 0;

// 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project'])) {
    $deleteQueries = [
        "DELETE FROM sub_task WHERE task_id IN (SELECT id FROM task WHERE project_id = ?)",
        "DELETE FROM task WHERE project_id = ?",
        "DELETE FROM project_member WHERE project_id = ?",
        "DELETE FROM project_history WHERE project_id = ?",
        "DELETE FROM project WHERE id = ?"
    ];
    foreach ($deleteQueries as $query) {
        $deleteStmt = $conn->prepare($query);
        $deleteStmt->bind_param("i", $project_id);
        $deleteStmt->execute();
    }
    header("Location: home.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['project_name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 28px;
            color: #004d99;
            margin-bottom: 20px;
        }

        .section {
            margin-bottom: 30px;
        }

        .section h2 {
            font-size: 20px;
            color: #4CAF50;
            margin-bottom: 10px;
        }

        .info p, .tasks ul {
            font-size: 16px;
            line-height: 1.6;
            margin: 5px 0;
        }

        .tasks ul {
            list-style: none;
            padding: 0;
        }

        .tasks ul li {
            margin-bottom: 10px;
        }

        .tasks ul li span {
            font-weight: bold;
            color: #4CAF50;
        }

        .tasks ul li span.in-progress {
            color: #FFC107;
        }

        .progress-bar-container {
            background-color: #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-bar {
            height: 25px;
            background-color: #4CAF50;
            width: <?php echo $progress; ?>%;
            text-align: center;
            line-height: 25px;
            color: white;
            font-size: 14px;
        }

        .buttons {
            display: flex;
            gap: 10px;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
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

        button.edit {
            background-color: #5cb85c;
        }

        button.edit:hover {
            background-color: #4cae4c;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($project['project_name']); ?></h1>

        <div class="section">
            <h2>프로젝트 정보</h2>
            <p><strong>설명:</strong> <?php echo htmlspecialchars($project['description']); ?></p>
            <p><strong>시작일:</strong> <?php echo htmlspecialchars($project['start']); ?></p>
            <p><strong>종료일:</strong> <?php echo htmlspecialchars($project['end']); ?></p>
            <p><strong>완료 여부:</strong> <?php echo $project['finish'] ? '완료' : '진행 중'; ?></p>
        </div>

        <div class="section tasks">
            <h2>테스크 목록</h2>
            <ul>
                <?php foreach ($tasks as $task): ?>
                    <li>
                        <?php echo htmlspecialchars($task['task_name']); ?> - 
                        <span class="<?php echo $task['is_completed'] === 3 ? 'completed' : 'in-progress'; ?>">
                            <?php echo ($task['is_completed'] === 3) ? '완료' : '진행 중'; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="section">
            <h2>진행 현황</h2>
            <div class="progress-bar-container">
                <div class="progress-bar"><?php echo $progress; ?>%</div>
            </div>
        </div>

        <div class="buttons">
            <form method="POST">
                <button type="submit" name="delete_project" class="secondary">삭제</button>
            </form>
            <button onclick="location.href='edit_project.php?project_id=<?php echo $project_id; ?>'" class="edit">수정</button>
            <button onclick="location.href='m_home.php'" class="primary">뒤로 가기</button>
        </div>
    </div>
</body>
</html>
