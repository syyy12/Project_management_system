# 2024 11 21 14시24분 수정 김동하
<?php
session_start();
include 'db.php';

// 로그인 여부 확인
if (!isset($_SESSION['login_id'])) {
    // 로그인되지 않은 경우 로그인 페이지로 리다이렉션
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

// 프로젝트 관리자 조회
// 비밀번호 보안을 위해 View 사용
$managerQuery = "
    SELECT u.user_name
    FROM project_member AS pm
    JOIN user_view  AS u ON pm.login_id = u.login_id
    WHERE pm.project_id = ? AND pm.project_role = 1
";


$managerStmt = $conn->prepare($managerQuery);
$managerStmt->bind_param("i", $project_id);
$managerStmt->execute();
$managerResult = $managerStmt->get_result();
$manager = $managerResult->fetch_assoc()['user_name'] ?? 'N/A';

// 프로젝트 멤버 조회
// 비밀번호 보안을 위해 View 사용
$memberQuery = "
    SELECT u.user_name
    FROM project_member AS pm
    JOIN user_view  AS u ON pm.login_id = u.login_id
    WHERE pm.project_id = ?
";


$memberStmt = $conn->prepare($memberQuery);
$memberStmt->bind_param("i", $project_id);
$memberStmt->execute();
$memberResult = $memberStmt->get_result();

$members = [];
while ($member = $memberResult->fetch_assoc()) {
    $members[] = $member['user_name'];
}
$memberList = implode(', ', $members);

// 테스크 목록 조회
$taskQuery = "
    SELECT id, task_name, description, start, end, is_completed
    FROM task
    WHERE project_id = ?
";

$taskStmt = $conn->prepare($taskQuery);
$taskStmt->bind_param("i", $project_id);
$taskStmt->execute();
$taskResult = $taskStmt->get_result();

// 진행률 계산
$totalTasksQuery = "
    SELECT COUNT(*) AS total_tasks,
           SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) AS completed_tasks
    FROM task
    WHERE project_id = ?
";
$totalTasksStmt = $conn->prepare($totalTasksQuery);
$totalTasksStmt->bind_param("i", $project_id);
$totalTasksStmt->execute();
$totalTasksStmt->bind_result($total_tasks, $completed_tasks);
$totalTasksStmt->fetch();
$totalTasksStmt->close();

$progress = ($total_tasks > 0) ? round(($completed_tasks / $total_tasks) * 100, 1) : 0;
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
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }

        h2 {
            font-size: 32px;
            color: #004d99;
            margin-bottom: 20px;
        }

        .section {
            margin-bottom: 30px;
        }

        .section h3 {
            font-size: 24px;
            color: #4CAF50;
            margin-bottom: 15px;
        }

        p, ul {
            font-size: 18px;
            margin: 10px 0;
        }

        ul {
            padding-left: 20px;
        }

        ul li {
            margin-bottom: 10px;
        }

        ul li a {
            color: #004d99;
            text-decoration: none;
            font-weight: bold;
        }

        ul li a:hover {
            text-decoration: underline;
        }

        .progress-bar-container {
            background-color: #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            margin: 15px 0;
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

        button {
            padding: 10px 20px;
            font-size: 18px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
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
        <h2><?php echo htmlspecialchars($project['project_name']); ?></h2>

        <div class="section">
            <h3>프로젝트 정보</h3>
            <p><strong>프로젝트 매니저:</strong> <?php echo htmlspecialchars($manager); ?></p>
            <p><strong>멤버:</strong> <?php echo htmlspecialchars($memberList); ?></p>
            <p><strong>설명:</strong> <?php echo htmlspecialchars($project['description']); ?></p>
            <p><strong>완료 여부:</strong> <?php echo $project['finish'] ? '완료' : '진행 중'; ?></p>
        </div>

        <div class="section">
            <h3>테스크 목록</h3>
            <ul>
                <?php
                if ($taskResult->num_rows > 0) {
                    while ($task = $taskResult->fetch_assoc()) {
                        $task_name = htmlspecialchars($task['task_name']);
                        $task_id = $task['id'];
                        $is_completed = $task['is_completed'] ? '완료' : '진행 중';
                        echo "<li><a href='task.php?task_id=$task_id'>$task_name</a> - $is_completed</li>";
                    }
                } else {
                    echo "<li>테스크가 없습니다.</li>";
                }
                ?>
            </ul>
        </div>

        <div class="section">
            <h3>진행 현황</h3>
            <p><strong>시작일:</strong> <?php echo $project['start']; ?></p>
            <p><strong>종료일:</strong> <?php echo $project['end']; ?></p>
            <div class="progress-bar-container">
                <div class="progress-bar"><?php echo $progress; ?>%</div>
            </div>
        </div>

        <div class="section">
            <h3>수정본 열람</h3>
            <label for="versionSelect">수정본 보기:</label>
            <select id="versionSelect" onchange="location.href='history.php?project_id=<?php echo $project_id; ?>&version=' + this.value;">
                <option value="">현재 프로젝트</option>
                <?php
                $versionQuery = "
                    SELECT version
                    FROM project_history
                    WHERE project_id = ?
                    ORDER BY version ASC
                ";
                $versionStmt = $conn->prepare($versionQuery);
                $versionStmt->bind_param("i", $project_id);
                $versionStmt->execute();
                $versionResult = $versionStmt->get_result();

                while ($row = $versionResult->fetch_assoc()) {
                    echo "<option value='{$row['version']}'>{$row['version']}차 수정본</option>";
                }
                ?>
            </select>
        </div>

        <div class="section">
            <button class="primary" onclick="location.href='post.php?project_id=<?php echo $project_id; ?>'">게시판 글 목록</button>
            <button class="secondary" onclick="location.href='home.php'">뒤로 가기</button>
        </div>
    </div>
</body>
</html>
