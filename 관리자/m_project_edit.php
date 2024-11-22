<?php
# 수정해서 히스토리에 저장하는 찐 최종본 2024 11 23 김동하
session_start();
include 'db.php';

// 프로젝트 ID 확인
$project_id = $_GET['project_id'] ?? null;
if (!$project_id) {
    die("잘못된 접근입니다. 프로젝트 ID가 필요합니다.");
}

// 프로젝트 정보 가져오기
$projectQuery = "
    SELECT id, project_name, description, start, end, finish
    FROM project
    WHERE id = ?
";
$projectStmt = $conn->prepare($projectQuery);
$projectStmt->bind_param("i", $project_id);
$projectStmt->execute();
$project = $projectStmt->get_result()->fetch_assoc();

if (!$project) {
    die("프로젝트를 찾을 수 없습니다.");
}

// Task 데이터 가져오기
$taskQuery = "
    SELECT id, task_name, description, start, end, is_completed, Notification_Percentage
    FROM task
    WHERE project_id = ?
";
$taskStmt = $conn->prepare($taskQuery);
$taskStmt->bind_param("i", $project_id);
$taskStmt->execute();
$tasks = $taskStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Sub-task 데이터 가져오기
$subTaskQuery = "
    SELECT id, task_id, sub_task_name, start, end, min_days, description, is_completed
    FROM sub_task
    WHERE task_id IN (SELECT id FROM task WHERE project_id = ?)
";
$subTaskStmt = $conn->prepare($subTaskQuery);
$subTaskStmt->bind_param("i", $project_id);
$subTaskStmt->execute();
$subTasks = $subTaskStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<HTML>
<HEAD>
<META http-equiv="content-type" content="text/html; charset=utf-8">
</HEAD>
<BODY>
    <h1>프로젝트 수정</h1>
    <FORM METHOD="post" ACTION="process_project_edit.php">
<!-- 숨겨진 프로젝트 ID 전달 -->
    <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">

        <h3>프로젝트 정보</h3>
        <label>프로젝트 이름:</label>
        <INPUT TYPE="text" NAME="project_name" VALUE="<?php echo htmlspecialchars($project['project_name']); ?>"><br>
        
        <label>설명:</label>
        <INPUT TYPE="text" NAME="description" VALUE="<?php echo htmlspecialchars($project['description']); ?>"><br>
        
       <label>시작 날짜:</label>
<input type="date" name="start" value="<?php echo $project['start']; ?>"><br>

<label>종료 날짜:</label>
<input type="date" name="end" value="<?php echo $project['end']; ?>"><br>

        
<h3>테스크 수정</h3>
<?php foreach ($tasks as $task): ?>
    <label>Task 이름:</label>
    <INPUT TYPE="text" NAME="tasks[<?php echo $task['id']; ?>][task_name]" VALUE="<?php echo htmlspecialchars($task['task_name']); ?>"><br>
    
    <label>설명:</label>
    <INPUT TYPE="text" NAME="tasks[<?php echo $task['id']; ?>][description]" VALUE="<?php echo htmlspecialchars($task['description']); ?>"><br>

    <label>시작 날짜:</label>
    <INPUT TYPE="date" NAME="tasks[<?php echo $task['id']; ?>][start]" VALUE="<?php echo htmlspecialchars($task['start']); ?>"><br>

    <label>종료 날짜:</label>
    <INPUT TYPE="date" NAME="tasks[<?php echo $task['id']; ?>][end]" VALUE="<?php echo htmlspecialchars($task['end']); ?>"><br>

    <!-- 삭제 체크박스 -->
    <label>
        <input type="checkbox" name="tasks[<?php echo $task['id']; ?>][is_deleted]" value="1">
        삭제
    </label>
    <br><br>
<?php endforeach; ?>

<h3>서브 테스크 수정</h3>
<?php foreach ($subTasks as $subTask): ?>
    <label>Sub-task 이름:</label>
    <INPUT TYPE="text" NAME="sub_tasks[<?php echo $subTask['id']; ?>][sub_task_name]" VALUE="<?php echo htmlspecialchars($subTask['sub_task_name']); ?>"><br>
    
    <label>설명:</label>
    <INPUT TYPE="text" NAME="sub_tasks[<?php echo $subTask['id']; ?>][description]" VALUE="<?php echo htmlspecialchars($subTask['description']); ?>"><br>

    <label>시작 날짜:</label>
    <INPUT TYPE="date" NAME="sub_tasks[<?php echo $subTask['id']; ?>][start]" VALUE="<?php echo htmlspecialchars($subTask['start']); ?>"><br>

    <label>종료 날짜:</label>
    <INPUT TYPE="date" NAME="sub_tasks[<?php echo $subTask['id']; ?>][end]" VALUE="<?php echo htmlspecialchars($subTask['end']); ?>"><br>

    <!-- 삭제 체크박스 -->
    <label>
        <input type="checkbox" name="sub_tasks[<?php echo $subTask['id']; ?>][is_deleted]" value="1">
        삭제
    </label>
    <br><br>
<?php endforeach; ?>


        
        <INPUT TYPE="submit" VALUE="저장">
    </FORM>
</BODY>
</HTML>
