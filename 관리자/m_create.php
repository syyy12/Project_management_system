<?php
session_start();
include 'db.php';

// 로그인 여부 확인 및 관리자 권한 확인
if (!isset($_SESSION['login_id']) || $_SESSION['role'] != 1) {
    header("Location: login.php");
    exit();
}

// 시스템 관리자를 제외한 사용자 조회
$userQuery = "SELECT login_id, user_name FROM User WHERE role != 1"; // role = 1 은 시스템 관리자
$userResult = $conn->query($userQuery);
$users = [];
while ($user = $userResult->fetch_assoc()) {
    $users[] = $user; // 사용자 리스트 저장
}

// 프로젝트 생성 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $projectName = trim($_POST['project_name']);
    $projectAdmin = trim($_POST['project_admin']);
    $projectDescription = trim($_POST['project_description']);
    $projectStart = trim($_POST['project_start']);
    $projectEnd = trim($_POST['project_end']);
    $projectMembers = $_POST['project_members'] ?? []; // 배열 형태

    // 관리자 검증: 관리자가 멤버로 선택되었는지 확인
    if (!in_array($projectAdmin, $projectMembers)) {
        $error = "관리자는 반드시 프로젝트 멤버로 선택되어야 합니다.";
    } else {
        // 프로젝트 데이터 삽입
        $projectInsertQuery = "
            INSERT INTO project (project_name, description, start, end, finish)
            VALUES (?, ?, ?, ?, 0)
        ";
        $stmt = $conn->prepare($projectInsertQuery);
        $stmt->bind_param("ssss", $projectName, $projectDescription, $projectStart, $projectEnd);
        $stmt->execute();
        $projectId = $conn->insert_id; // 생성된 프로젝트 ID

        // 프로젝트 관리자 삽입 (project_role = 1)
        $adminInsertQuery = "
            INSERT INTO project_member (project_id, login_id, project_role)
            VALUES (?, ?, 1)
        ";
        $adminStmt = $conn->prepare($adminInsertQuery);
        $adminStmt->bind_param("is", $projectId, $projectAdmin);
        $adminStmt->execute();

        // 프로젝트 멤버 데이터 삽입 (관리자 제외, project_role = 0)
        foreach ($projectMembers as $memberId) {
            if ($memberId !== $projectAdmin) { // 관리자는 멤버에서 제외
                $memberInsertQuery = "
                    INSERT INTO project_member (project_id, login_id, project_role)
                    VALUES (?, ?, 0)
                ";
                $memberStmt = $conn->prepare($memberInsertQuery);
                $memberStmt->bind_param("is", $projectId, $memberId);
                $memberStmt->execute();
            }
        }

        // 성공 메시지 및 리다이렉션
        header("Location: m_project.php?project_id=" . $projectId);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>프로젝트 생성</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .form-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 400px;
        }
        h2 {
            text-align: center;
            color: #004d99;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }
        input, textarea, select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        textarea {
            height: 100px;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            font-size: 14px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>프로젝트 생성</h2>
        <form method="POST" action="m_create.php">
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <label for="project_name">프로젝트 이름</label>
            <input type="text" id="project_name" name="project_name" placeholder="프로젝트 이름을 입력하세요" required>

            <label for="project_admin">프로젝트 관리자</label>
            <select id="project_admin" name="project_admin" required>
                <option value="">관리자를 선택하세요</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo htmlspecialchars($user['login_id']); ?>">
                        <?php echo htmlspecialchars($user['user_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="project_description">프로젝트 설명</label>
            <textarea id="project_description" name="project_description" placeholder="프로젝트 설명을 입력하세요"></textarea>

            <label for="project_start">프로젝트 시작일</label>
            <input type="date" id="project_start" name="project_start" required>

            <label for="project_end">프로젝트 종료일</label>
            <input type="date" id="project_end" name="project_end" required>

            <label for="project_members">프로젝트 진행 멤버</label>
            <select id="project_members" name="project_members[]" multiple required>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo htmlspecialchars($user['login_id']); ?>">
                        <?php echo htmlspecialchars($user['user_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">프로젝트 생성</button>
        </form>
    </div>
</body>
</html>
