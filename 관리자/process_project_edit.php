<?php
# 히스토리에 저장하고 원래 테이블 저장하고 테이블 수정까지 하는 2024 11 23
session_start();
include 'db.php';

// POST 데이터 수신
$project_id = $_POST['project_id'];

// 1. 프로젝트 업데이트
$project_name = mysqli_real_escape_string($conn, $_POST['project_name']);
$description = mysqli_real_escape_string($conn, $_POST['description']);
$start = isset($_POST['start']) ? mysqli_real_escape_string($conn, $_POST['start']) : null;
$end = isset($_POST['end']) ? mysqli_real_escape_string($conn, $_POST['end']) : null;

$updateProjectQuery = "
    UPDATE project 
    SET project_name = '$project_name', 
        description = '$description', 
        start = '$start', 
        end = '$end' 
    WHERE id = $project_id
";

$projectResult = mysqli_query($conn, $updateProjectQuery);

if (!$projectResult) {
echo $updateProjectQuery;

    die("프로젝트 업데이트 실패: " . mysqli_error($conn));
}

// 2. 테스크 업데이트
if (!empty($_POST['tasks']) && is_array($_POST['tasks'])) {
    foreach ($_POST['tasks'] as $task_id => $task) {
        if (isset($task['is_deleted']) && $task['is_deleted'] == 1) {
            // 테스크 삭제
            $deleteTaskQuery = "DELETE FROM task WHERE id = $task_id";
            if (!mysqli_query($conn, $deleteTaskQuery)) {
                die("테스크 삭제 실패: " . mysqli_error($conn));
            }
        } else {
            // 테스크 업데이트
            $task_name = mysqli_real_escape_string($conn, $task['task_name']);
            $description = mysqli_real_escape_string($conn, $task['description']);
            $start = mysqli_real_escape_string($conn, $task['start']);
            $end = mysqli_real_escape_string($conn, $task['end']);

            $updateTaskQuery = "
                UPDATE task 
                SET task_name = '$task_name', 
                    description = '$description', 
                    start = '$start', 
                    end = '$end' 
                WHERE id = $task_id
            ";
            if (!mysqli_query($conn, $updateTaskQuery)) {
                die("테스크 업데이트 실패: " . mysqli_error($conn));
            }
        }
    }
} else {
    // 테스크가 없을 경우 처리
    echo "테스크가 없습니다.<br>";
}

// 3. 서브 테스크 업데이트
if (!empty($_POST['sub_tasks']) && is_array($_POST['sub_tasks'])) {
    foreach ($_POST['sub_tasks'] as $sub_task_id => $sub_task) {
        if (isset($sub_task['is_deleted']) && $sub_task['is_deleted'] == 1) {
            // 서브 테스크 삭제
            $deleteSubTaskQuery = "DELETE FROM sub_task WHERE id = $sub_task_id";
            if (!mysqli_query($conn, $deleteSubTaskQuery)) {
                die("서브 테스크 삭제 실패: " . mysqli_error($conn));
            }
        } else {
            // 서브 테스크 업데이트
            $sub_task_name = mysqli_real_escape_string($conn, $sub_task['sub_task_name']);
            $description = mysqli_real_escape_string($conn, $sub_task['description']);
            $start = mysqli_real_escape_string($conn, $sub_task['start']);
            $end = mysqli_real_escape_string($conn, $sub_task['end']);

            $updateSubTaskQuery = "
                UPDATE sub_task 
                SET sub_task_name = '$sub_task_name', 
                    description = '$description', 
                    start = '$start', 
                    end = '$end' 
                WHERE id = $sub_task_id
            ";
            if (!mysqli_query($conn, $updateSubTaskQuery)) {
                die("서브 테스크 업데이트 실패: " . mysqli_error($conn));
            }
        }
    }
} else {
    // 서브 테스크가 없을 경우 처리
    echo "서브 테스크가 없습니다.<br>";
}


// 연결 종료
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>수정 완료</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
        }
        .container {
            max-width: 600px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        h1 {
            color: #4CAF50;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            text-decoration: none;
            color: white;
            background-color: #4CAF50;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>수정이 완료되었습니다!</h1>
    <p>수정된 내용이 성공적으로 저장되었습니다.</p>
    <a href="m_project.php?project_id=<?php echo $project_id; ?>">프로젝트 목록으로 돌아가기</a>
</div>
</body>
</html>
