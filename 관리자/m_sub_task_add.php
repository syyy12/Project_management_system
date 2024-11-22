<?php
// 데이터베이스 연결 설정
$host = 'localhost'; // 데이터베이스 호스트
$dbname = 'mydatabase'; // 데이터베이스 이름
$username = 'root'; // 사용자 이름
$password = ""; // 비밀번호

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// 전달된 task_id 확인
$task_id = $_GET['task_id'] ?? null;

if (!$task_id) {
    die("테스크 ID가 전달되지 않았습니다. 올바른 경로로 접근해주세요.");
}

// 현재 테스크의 project_id 가져오기
try {
    // user 테이블에서 참여 멤버 가져오기
    $userQuery = $pdo->query("SELECT login_id, user_name FROM user");
    $users = $userQuery->fetchAll(PDO::FETCH_ASSOC);

    // 현재 task_id를 기반으로 project_id 가져오기
    $projectQuery = $pdo->prepare("SELECT project_id FROM task WHERE id = :task_id");
    $projectQuery->execute([':task_id' => $task_id]);
    $project_id = $projectQuery->fetchColumn();

    if (!$project_id) {
        die("유효하지 않은 테스크 ID입니다.");
    }

    // 동일한 project_id를 가진 테스크들의 서브 테스크 가져오기
    $subTaskQuery = $pdo->prepare("
        SELECT s.id, s.sub_task_name 
        FROM sub_task s 
        JOIN task t ON s.task_id = t.id 
        WHERE t.project_id = :project_id
    ");
    $subTaskQuery->execute([':project_id' => $project_id]);
    $subTasks = $subTaskQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("데이터를 불러오는 중 오류가 발생했습니다: " . $e->getMessage());
}

// 경고 메시지를 저장할 변수
$error_message = "";

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sub_task_name = $_POST['sub_task_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $start_date = $_POST['start'] ?? '';
    $end_date = $_POST['end'] ?? '';
    $min_days = $_POST['min_days'] ?? 0;
    $login_id = $_POST['login_id'] ?? '';
    $pre_sub_task_id = $_POST['pre_sub_task_id'] ?? null;

    // 입력값 유효성 검사
    if (empty($sub_task_name) || empty($start_date) || empty($end_date) || empty($login_id)) {
        $error_message = "모든 필수 입력값을 작성해주세요.";
    } else {
        try {
            // 참여 멤버가 해당 프로젝트에 속했는지 확인
            $checkMembershipQuery = $pdo->prepare("
                SELECT COUNT(*) FROM project_member 
                WHERE login_id = :login_id AND project_id = :project_id
            ");
            $checkMembershipQuery->execute([
                ':login_id' => $login_id,
                ':project_id' => $project_id
            ]);
            $isMember = $checkMembershipQuery->fetchColumn();

            // 참여 멤버가 프로젝트에 속하지 않은 경우 추가
            if (!$isMember) {
                $addMemberQuery = $pdo->prepare("
                    INSERT INTO project_member (login_id, project_id, project_role)
                    VALUES (:login_id, :project_id, 0) -- project_role 기본값은 0 (일반 멤버)
                ");
                $addMemberQuery->execute([
                    ':login_id' => $login_id,
                    ':project_id' => $project_id
                ]);
            }

            // 서브 테스크 추가
            $stmt = $pdo->prepare("
                INSERT INTO sub_task (task_id, sub_task_name, description, start, end, min_days, login_id, pre_sub_task_id, is_completed)
                VALUES (:task_id, :sub_task_name, :description, :start, :end, :min_days, :login_id, :pre_sub_task_id, 0)
            ");
            $stmt->execute([
                ':task_id' => $task_id,
                ':sub_task_name' => $sub_task_name,
                ':description' => $description,
                ':start' => $start_date,
                ':end' => $end_date,
                ':min_days' => $min_days,
                ':login_id' => $login_id,
                ':pre_sub_task_id' => $pre_sub_task_id ?: null
            ]);

            // 성공적으로 추가되었으면 m_task.php로 리다이렉션 (task_id 전달)
            header("Location: m_task.php?task_id=" . urlencode($task_id));
            exit; // 반드시 exit를 추가하여 스크립트 실행 중단
        } catch (PDOException $e) {
            $error_message = "데이터 추가 중 오류가 발생했습니다: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>서브 테스크 추가</title>
</head>
<body>
    <h1>서브 테스크 추가</h1>

    <?php if (!empty($error_message)): ?>
        <p style="color: red; font-weight: bold;"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="sub_task_name">서브 테스크 이름:</label><br>
        <input type="text" id="sub_task_name" name="sub_task_name" required><br><br>

        <label for="description">서브 테스크 설명:</label><br>
        <textarea id="description" name="description" rows="4" cols="50"></textarea><br><br>

        <label for="start">시작일:</label><br>
        <input type="date" id="start" name="start" required><br><br>

        <label for="end">종료일:</label><br>
        <input type="date" id="end" name="end" required><br><br>

        <label for="min_days">최소 소요일:</label><br>
        <input type="number" id="min_days" name="min_days" min="0" required><br><br>

        <label for="login_id">참여 멤버:</label><br>
        <select id="login_id" name="login_id" required>
            <option value="">선택하세요</option>
            <?php foreach ($users as $user): ?>
                <option value="<?= htmlspecialchars($user['login_id']) ?>">
                    <?= htmlspecialchars($user['user_name']) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label for="pre_sub_task_id">선행 테스크 (선택):</label><br>
        <select id="pre_sub_task_id" name="pre_sub_task_id">
            <option value="">선택하지 않음</option>
            <?php foreach ($subTasks as $subTask): ?>
                <option value="<?= htmlspecialchars($subTask['id']) ?>">
                    <?= htmlspecialchars($subTask['sub_task_name']) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <button type="submit">추가하기</button>
    </form>
</body>
</html>
