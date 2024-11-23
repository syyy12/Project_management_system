<?php
# 수정 토요일 
session_start();
include 'db.php';

// 로그인 여부 확인
if (!isset($_SESSION['login_id'])) {
    header("Location: login.php");
    exit();
}

$project_id = $_GET['project_id'] ?? null;
$version = $_GET['version'] ?? null; // 특정 수정본 조회
if (!$project_id) {
    die("잘못된 접근입니다.");
}

// 프로젝트 기본 정보 조회 (현재 프로젝트 또는 특정 히스토리)
if ($version) {
    $projectQuery = "
        SELECT p.project_name, ph.description, ph.start, ph.end, ph.manager_name, ph.modified_date
        FROM project_history ph
        JOIN project p ON p.id = ph.project_id
        WHERE ph.project_id = ? AND ph.version = ?
    ";
    $projectStmt = $conn->prepare($projectQuery);
    $projectStmt->bind_param("ii", $project_id, $version);
} else {
    $projectQuery = "
        SELECT project_name, description, start, end, finish
        FROM project
        WHERE id = ?
    ";
    $projectStmt = $conn->prepare($projectQuery);
    $projectStmt->bind_param("i", $project_id);
}
$projectStmt->execute();
$projectResult = $projectStmt->get_result();
$project = $projectResult->fetch_assoc();

if (!$project) {
    die("프로젝트 정보를 찾을 수 없습니다.");
}

// 프로젝트 멤버 및 관리자 조회
$membersQuery = "
    SELECT pm.login_id, u.user_name, pm.project_role
    FROM project_member pm
    JOIN User u ON pm.login_id = u.login_id
    WHERE pm.project_id = ?
";
$membersStmt = $conn->prepare($membersQuery);
$membersStmt->bind_param("i", $project_id);
$membersStmt->execute();
$membersResult = $membersStmt->get_result();

$manager = null;
$members = [];
while ($row = $membersResult->fetch_assoc()) {
    if ((int)$row['project_role'] === 1) { // 관리자
        $manager = $row['user_name'];
    } else {
        $members[] = $row['user_name'];
    }
}

// 테스크 목록 조회 (수정본 또는 현재 상태)
if ($version) {
    // 과거 수정본에 해당하는 테스크 조회
    $taskQuery = "
        SELECT th.task_id, th.task_name, th.description
        FROM task_history th
        WHERE th.project_id = ? AND th.version = ?
    ";
    $taskStmt = $conn->prepare($taskQuery);
    $taskStmt->bind_param("ii", $project_id, $version);
} else {
    // 현재 수정본의 테스크 조회
    $taskQuery = "
        SELECT t.id AS task_id, t.task_name, t.is_completed
        FROM task t
        WHERE t.project_id = ?
    ";
    $taskStmt = $conn->prepare($taskQuery);
    $taskStmt->bind_param("i", $project_id);
}
$taskStmt->execute();
$taskResult = $taskStmt->get_result();

$totalTasks = 0;
$completedTasks = 0;
$tasks = [];

while ($task = $taskResult->fetch_assoc()) {
    $totalTasks++;
    if (!$version && (int)$task['is_completed'] === 3) { // 완료 상태는 현재 수정본에서만 계산
        $completedTasks++;
    }
    $tasks[] = $task;
}
$progress = ($totalTasks > 0 && !$version) ? round(($completedTasks / $totalTasks) * 100, 1) : 0;

// 프로젝트 히스토리 조회
$historyQuery = "
    SELECT version, modified_date
    FROM project_history
    WHERE project_id = ?
    ORDER BY version DESC
";
$historyStmt = $conn->prepare($historyQuery);
$historyStmt->bind_param("i", $project_id);
$historyStmt->execute();
$historyResult = $historyStmt->get_result();

$histories = [];
while ($history = $historyResult->fetch_assoc()) {
    $histories[] = $history;
}

// 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project'])) {
    $password = $_POST['password'] ?? '';
    
    $userQuery = "SELECT password FROM User WHERE login_id = ?";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->bind_param("s", $_SESSION['login_id']);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();

    if ($user['password'] !== $password) {
        $error = "비밀번호가 일치하지 않습니다.";
    } else {
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
        header("Location: m_home.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($version ? "수정본 - {$project['description']}" : $project['project_name']); ?> - 관리자</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1, h2 {
            color: #343a40;
            margin-bottom: 20px;
        }

        .section {
            margin-bottom: 30px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 20px;
        }

        .section:last-child {
            border-bottom: none;
        }

        p {
            font-size: 16px;
            color: #495057;
            line-height: 1.6;
        }

        .tasks ul {
            list-style: none;
            padding: 0;
        }

        .tasks ul li {
            margin-bottom: 10px;
        }

        .tasks ul li a {
            color: #007bff;
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
        }

        .tasks ul li a:hover {
            text-decoration: underline;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }

        button.primary {
            background-color: #007bff;
            color: white;
        }

        button.primary:hover {
            background-color: #0056b3;
        }

        button.secondary {
            background-color: #dc3545;
            color: white;
        }

        button.secondary:hover {
            background-color: #c82333;
        }

        select {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        input[type="password"] {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .progress-bar-container {
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            height: 30px;
        }

        .progress-bar {
            background: #007bff;
            color: white;
            text-align: center;
            height: 100%;
            line-height: 30px;
            font-weight: bold;
        }

        .buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .error {
            color: #dc3545;
            font-weight: bold;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($project['project_name']); ?></h1>

        <div class="section">
            <h2>프로젝트 정보</h2>
            <p><strong>매니저:</strong> <?php echo htmlspecialchars($manager ?? 'N/A'); ?></p>
            <p><strong>멤버:</strong> <?php echo htmlspecialchars(implode(', ', $members)); ?></p>
            <p><strong>설명:</strong> <?php echo htmlspecialchars($project['description']); ?></p>
        </div>

        <div class="section">
            <h2>수정 히스토리</h2>
            <select onchange="location.href='m_project.php?project_id=<?php echo $project_id; ?>&version='+this.value">
                <option value="">현재 프로젝트</option>
                <?php foreach ($histories as $history): ?>
                    <option value="<?php echo $history['version']; ?>" <?php echo ($version == $history['version']) ? 'selected' : ''; ?>>
                        <?php echo $history['version']; ?>차 수정본 - <?php echo $history['modified_date']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="section tasks">
            <h2>테스크 목록</h2>
            <ul>
                <?php foreach ($tasks as $task): ?>
                    <li>
                        <a href="m_task<?php echo $version ? '_history' : ''; ?>.php?project_id=<?php echo $project_id; ?>&version=<?php echo $version; ?>&task_id=<?php echo $task['task_id']; ?>">
                            <?php echo htmlspecialchars($task['task_name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if (!$version): ?>
                <button onclick="location.href='m_task_add.php?project_id=<?php echo $project_id; ?>'" class="primary">
                    +테스크 추가
                </button>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>진행 현황</h2>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: <?php echo $progress; ?>%;">
                    <?php echo $progress; ?>%
                </div>
            </div>
        </div>

        <div class="buttons">
            <form method="POST">
                <?php if (isset($error)): ?>
                    <p class="error"><?php echo $error; ?></p>
                <?php endif; ?>
                <label for="password">비밀번호 확인</label>
                <input type="password" name="password" id="password" required>
                <button type="submit" name="delete_project" class="secondary">삭제</button>
            </form>
            <button class="primary" onclick="location.href='m_post.php?project_id=<?php echo $project_id; ?>'">게시판 글 목록</button>
            <button class="primary" onclick="location.href='m_project_edit.php?project_id=<?php echo $project_id; ?>'">수정</button>
            <button class="primary" onclick="location.href='m_home.php'">뒤로 가기</button>
        </div>
    </div>
</body>
</html>
