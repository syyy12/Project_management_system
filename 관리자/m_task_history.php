<?php
session_start();
include 'db.php';

$task_id = $_GET['task_id'] ?? null;
$project_id = $_GET['project_id'] ?? null;
$version = $_GET['version'] ?? null;

if (!$task_id || !$project_id || !$version) {
    die("잘못된 접근입니다.");
}

// 테스크 정보 조회
$taskQuery = "
    SELECT task_name, description, start, end
    FROM task_history
    WHERE task_id = ? AND project_id = ? AND version = ?
";
$taskStmt = $conn->prepare($taskQuery);
$taskStmt->bind_param("iii", $task_id, $project_id, $version);
$taskStmt->execute();
$taskResult = $taskStmt->get_result();
$task = $taskResult->fetch_assoc();

if (!$task) {
    die("테스크 정보를 찾을 수 없습니다.");
}

// 서브 테스크 목록 및 관계 조회
$subTaskQuery = "
    SELECT sth.sub_task_name, sth.min_days, sth.start, sth.end, sth.user_name, sth.pre_sub_task_name, sth.description
    FROM sub_task_history AS sth
    WHERE sth.task_id = ? AND sth.project_id = ? AND sth.version = ?
    ORDER BY sth.start ASC
";
$subTaskStmt = $conn->prepare($subTaskQuery);
$subTaskStmt->bind_param("iii", $task_id, $project_id, $version);
$subTaskStmt->execute();
$subTaskResult = $subTaskStmt->get_result();

// 데이터 그룹화: 선행 관계에 따라 서브 테스크 정렬
$tasksByGroup = [];
while ($row = $subTaskResult->fetch_assoc()) {
    $tasksByGroup[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($task['task_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f2f5; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        h2 { font-size: 36px; color: #004d99; margin-bottom: 20px; }
        .info p { font-size: 18px; line-height: 1.6; margin: 10px 0; }
        .task-diagram { display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 20px; }
        .task-box { text-align: center; background: #f9f9f9; padding: 15px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1); min-width: 200px; max-width: 250px; position: relative; margin-bottom: 20px; }
        .task-box h4 { margin: 10px 0; color: #333; }
        .task-box p { font-size: 14px; color: #555; margin: 5px 0; }
        .arrow { display: flex; align-items: center; justify-content: center; position: relative; width: 50px; height: 20px; }
        .arrow::after { content: "➡"; font-size: 24px; color: #333; }
        .buttons { margin-top: 20px; text-align: right; }
        button { padding: 10px 20px; font-size: 16px; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px; }
        button.primary { background-color: #004d99; }
        button.primary:hover { background-color: #003366; }
        button.secondary { background-color: #d9534f; }
        button.secondary:hover { background-color: #c9302c; }
    </style>
</head>
<body>
    <div class="container">
        <h2><?php echo htmlspecialchars($task['task_name']); ?></h2>
        <div class="info">
            <p><strong>설명:</strong> <?php echo htmlspecialchars($task['description']); ?></p>
            <p><strong>시작일:</strong> <?php echo $task['start']; ?></p>
            <p><strong>종료일:</strong> <?php echo $task['end']; ?></p>
        </div>

        <div class="task-diagram">
            <?php foreach ($tasksByGroup as $subTask): ?>
                <div class="task-box">
                    <h4><?php echo htmlspecialchars($subTask['sub_task_name']); ?></h4>
                    <p><strong>담당자:</strong> <?php echo htmlspecialchars($subTask['user_name']); ?></p>
                    <p><strong>선행 테스크:</strong> <?php echo htmlspecialchars($subTask['pre_sub_task_name'] ?? '없음'); ?></p>
                    <p><strong>최소 소요일:</strong> <?php echo $subTask['min_days']; ?>일</p>
                    <p><strong>시작일:</strong> <?php echo $subTask['start']; ?></p>
                    <p><strong>종료일:</strong> <?php echo $subTask['end']; ?></p>
                    <p><strong>설명:</strong> <?php echo htmlspecialchars($subTask['description']); ?></p>
                </div>

                <?php if (next($tasksByGroup)): ?>
                    <div class="arrow"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
