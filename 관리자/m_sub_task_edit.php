<?php
session_start();
include 'db.php';

$sub_task_id = $_GET['sub_task_id'] ?? null;
$task_id = $_GET['task_id'] ?? null;

if (!$sub_task_id || !$task_id) {
    die("잘못된 접근입니다.");
}

// 서브 테스크 정보 조회
$subTaskQuery = "
    SELECT sub_task_name, login_id, start, end, min_days, description, pre_sub_task_id 
    FROM sub_task 
    WHERE id = ?
";
$stmt = $conn->prepare($subTaskQuery);
$stmt->bind_param("i", $sub_task_id);
$stmt->execute();
$result = $stmt->get_result();
$subTask = $result->fetch_assoc();

if (!$subTask) {
    die("해당 서브 테스크를 찾을 수 없습니다.");
}

// user 테이블에서 유저 목록 조회
$userQuery = "SELECT login_id, user_name FROM user";
$userResult = $conn->query($userQuery);
$users = $userResult->fetch_all(MYSQLI_ASSOC);

// 같은 프로젝트의 서브 테스크 조회
$preSubTasks = [];
try {
    // 현재 task_id의 project_id를 가져오기
    $projectQuery = "
        SELECT project_id 
        FROM task 
        WHERE id = ?
    ";
    $projectStmt = $conn->prepare($projectQuery);
    $projectStmt->bind_param("i", $task_id);
    $projectStmt->execute();
    $projectResult = $projectStmt->get_result();
    $project_id = $projectResult->fetch_assoc()['project_id'];

    if (!$project_id) {
        die("프로젝트 정보를 찾을 수 없습니다.");
    }

    // 같은 프로젝트의 테스크의 모든 서브 테스크 가져오기
    $subTaskListQuery = "
        SELECT st.id, st.sub_task_name
        FROM sub_task AS st
        JOIN task AS t ON st.task_id = t.id
        WHERE t.project_id = ? AND st.id != ?
    ";
    $subTaskListStmt = $conn->prepare($subTaskListQuery);
    $subTaskListStmt->bind_param("ii", $project_id, $sub_task_id);
    $subTaskListStmt->execute();
    $subTaskListResult = $subTaskListStmt->get_result();

    while ($row = $subTaskListResult->fetch_assoc()) {
        $preSubTasks[] = $row;
    }
} catch (Exception $e) {
    die("선행 테스크를 가져오는 중 문제가 발생했습니다: " . $e->getMessage());
}

// 경고 메시지 배열
$errors = [];

// 폼 제출 시 데이터 업데이트
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sub_task_name = $_POST['sub_task_name'];
    $login_id = $_POST['login_id'];
    $start = $_POST['start'];
    $end = $_POST['end'];
    $min_days = $_POST['min_days'];
    $description = $_POST['description'];
    $pre_sub_task_id = $_POST['pre_sub_task_id'] ?: null;

    // 데이터 검증
    if (empty($sub_task_name)) {
        $errors[] = "서브 테스크 이름을 입력해주세요.";
    }
    if (empty($login_id)) {
        $errors[] = "담당자를 선택해주세요.";
    }
    if (empty($start)) {
        $errors[] = "시작일을 입력해주세요.";
    }
    if (empty($end)) {
        $errors[] = "종료일을 입력해주세요.";
    }
    if (empty($min_days) || $min_days < 1) {
        $errors[] = "최소 소요일은 1일 이상이어야 합니다.";
    }
    if (empty($description)) {
        $errors[] = "설명을 입력해주세요.";
    }

    // 선행 테스크 검증
    if (!empty($pre_sub_task_id)) {
        // 기존 값인지 확인
        if ($pre_sub_task_id != $subTask['pre_sub_task_id']) {
            $preTaskConflictQuery = "
                SELECT COUNT(*) AS conflict_count 
                FROM sub_task 
                WHERE pre_sub_task_id = ? AND id != ?
            ";
            $conflictStmt = $conn->prepare($preTaskConflictQuery);
            $conflictStmt->bind_param("ii", $pre_sub_task_id, $sub_task_id);
            $conflictStmt->execute();
            $conflictResult = $conflictStmt->get_result();
            $conflictCount = $conflictResult->fetch_assoc()['conflict_count'];

            if ($conflictCount > 0) {
                $errors[] = "선택한 선행 테스크는 이미 다른 서브 테스크에서 사용 중입니다.";
            }
        }
    }

    // 에러가 없으면 업데이트 진행
    if (empty($errors)) {
        $updateQuery = "
            UPDATE sub_task 
            SET sub_task_name = ?, login_id = ?, start = ?, end = ?, min_days = ?, description = ?, pre_sub_task_id = ?
            WHERE id = ?
        ";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ssssissi", $sub_task_name, $login_id, $start, $end, $min_days, $description, $pre_sub_task_id, $sub_task_id);

        if ($updateStmt->execute()) {
            // 업데이트 및 멤버 추가가 완료되었으면 리다이렉트
            header("Location: m_sub_task.php?task_id=$task_id&sub_task_id=$sub_task_id");
            exit();
        } else {
            $errors[] = "서브 테스크 수정 중 문제가 발생했습니다: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>서브 테스크 수정</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            font-size: 28px;
            color: #004d99;
            margin-bottom: 20px;
        }

        form {
            font-size: 18px;
            line-height: 1.6;
        }

        form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        form input, form select, form textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        form button {
            padding: 10px 20px;
            font-size: 16px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        form button.primary {
            background-color: #004d99;
        }

        form button.primary:hover {
            background-color: #003366;
        }

        .back {
            margin-top: 20px;
            text-align: right;
        }

        .error {
            color: red;
            font-weight: bold;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>서브 테스크 수정</h2>
        <!-- 에러 메시지 표시 -->
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <label for="sub_task_name">서브 테스크 이름</label>
            <input type="text" id="sub_task_name" name="sub_task_name" value="<?php echo htmlspecialchars($subTask['sub_task_name']); ?>" required>

            <label for="login_id">담당자</label>
            <select id="login_id" name="login_id" required>
                <option value="">담당자를 선택하세요</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo htmlspecialchars($user['login_id']); ?>" 
                        <?php echo $subTask['login_id'] === $user['login_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['user_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="start">시작일</label>
            <input type="date" id="start" name="start" value="<?php echo htmlspecialchars($subTask['start']); ?>" required>

            <label for="end">종료일</label>
            <input type="date" id="end" name="end" value="<?php echo htmlspecialchars($subTask['end']); ?>" required>

            <label for="min_days">최소 소요일</label>
            <input type="number" id="min_days" name="min_days" value="<?php echo htmlspecialchars($subTask['min_days']); ?>" required>

            <label for="description">설명</label>
            <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($subTask['description']); ?></textarea>

            <!-- 선행 서브 테스크 선택 드롭다운 -->
            <label for="pre_sub_task_id">선행 서브 테스크 (선택):</label>
            <select id="pre_sub_task_id" name="pre_sub_task_id">
                <option value="">선택하지 않음</option>
                <?php foreach ($preSubTasks as $preSubTask): ?>
                    <option value="<?php echo htmlspecialchars($preSubTask['id']); ?>" 
                        <?php echo ($subTask['pre_sub_task_id'] == $preSubTask['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($preSubTask['sub_task_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- 수정 완료 버튼 -->
            <button type="submit" class="primary">수정 완료</button>
        </form>

        <!-- 뒤로가기 버튼 -->
        <div class="back">
            <button 
                onclick="location.href='m_sub_task.php?task_id=<?php echo htmlspecialchars($task_id); ?>&sub_task_id=<?php echo htmlspecialchars($sub_task_id); ?>'">
                뒤로 가기
            </button>
        </div>
    </div>
</body>
</html>
