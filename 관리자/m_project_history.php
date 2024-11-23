<?php
session_start();
include 'db.php';

// 로그인 여부 확인
if (!isset($_SESSION['login_id'])) {
    header("Location: login.php");
    exit();
}

$project_id = $_GET['project_id'] ?? null;
$version = $_GET['version'] ?? null;

if (!$project_id || !$version) {
    die("잘못된 접근입니다.");
}

// 프로젝트 히스토리 정보 조회
$projectHistoryQuery = "
    SELECT p.project_name, ph.description, ph.start, ph.end, ph.manager_name, ph.modified_date
    FROM project_history ph
    JOIN project p ON p.id = ph.project_id
    WHERE ph.project_id = ? AND ph.version = ?
";
$projectHistoryStmt = $conn->prepare($projectHistoryQuery);
$projectHistoryStmt->bind_param("ii", $project_id, $version);
$projectHistoryStmt->execute();
$projectHistoryResult = $projectHistoryStmt->get_result();
$projectHistory = $projectHistoryResult->fetch_assoc();

if (!$projectHistory) {
    die("해당 수정본 정보를 찾을 수 없습니다.");
}

// 테스크 히스토리 조회
$taskHistoryQuery = "
    SELECT th.task_id, th.task_name, th.description, th.start, th.end
    FROM task_history th
    WHERE th.project_id = ? AND th.version = ?
";
$taskHistoryStmt = $conn->prepare($taskHistoryQuery);
$taskHistoryStmt->bind_param("ii", $project_id, $version);
$taskHistoryStmt->execute();
$taskHistoryResult = $taskHistoryStmt->get_result();
$tasks = [];
while ($task = $taskHistoryResult->fetch_assoc()) {
    $tasks[] = $task;
}

// 서브테스크 히스토리 조회
$subTaskHistoryQuery = "
    SELECT sth.sub_task_name, sth.user_name, sth.description, sth.start, sth.end, sth.min_days
    FROM sub_task_history sth
    WHERE sth.project_id = ? AND sth.version = ?
";
$subTaskHistoryStmt = $conn->prepare($subTaskHistoryQuery);
$subTaskHistoryStmt->bind_param("ii", $project_id, $version);
$subTaskHistoryStmt->execute();
$subTaskHistoryResult = $subTaskHistoryStmt->get_result();
$subTasks = [];
while ($subTask = $subTaskHistoryResult->fetch_assoc()) {
    $subTasks[] = $subTask;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>수정본 - <?php echo htmlspecialchars($projectHistory['project_name']); ?></title>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($projectHistory['project_name']); ?> - 수정본 보기</h1>

        <div class="section">
            <h2>프로젝트 정보</h2>
            <p><strong>매니저:</strong> <?php echo htmlspecialchars($projectHistory['manager_name']); ?></p>
            <p><strong>설명:</strong> <?php echo htmlspecialchars($projectHistory['description']); ?></p>
            <p><strong>시작일:</strong> <?php echo htmlspecialchars($projectHistory['start']); ?></p>
            <p><strong>종료일:</strong> <?php echo htmlspecialchars($projectHistory['end']); ?></p>
            <p><strong>수정일:</strong> <?php echo htmlspecialchars($projectHistory['modified_date']); ?></p>
        </div>

        <div class="section">
            <h2>테스크 목록</h2>
            <ul>
                <?php foreach ($tasks as $task): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($task['task_name']); ?></strong> - 
                        <?php echo htmlspecialchars($task['description']); ?> 
                        (<?php echo htmlspecialchars($task['start']); ?> ~ <?php echo htmlspecialchars($task['end']); ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="section">
            <h2>서브 테스크 목록</h2>
            <ul>
                <?php foreach ($subTasks as $subTask): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($subTask['sub_task_name']); ?></strong> - 
                        <?php echo htmlspecialchars($subTask['description']); ?> 
                        (<?php echo htmlspecialchars($subTask['start']); ?> ~ <?php echo htmlspecialchars($subTask['end']); ?>)
                        [담당자: <?php echo htmlspecialchars($subTask['user_name']); ?>]
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <button onclick="location.href='m_project.php?project_id=<?php echo $project_id; ?>'">돌아가기</button>
    </div>
</body>
</html>
