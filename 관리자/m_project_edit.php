<?php
session_start();
include 'db.php';

// 로그인 여부 확인 및 관리자 권한 확인
if (!isset($_SESSION['login_id']) || $_SESSION['role'] != 1) {
    header("Location: login.php");
    exit();
}

$project_id = $_GET['project_id'] ?? null;
if (!$project_id) {
    die("잘못된 접근입니다.");
}

// 프로젝트 정보 조회
$projectQuery = "
    SELECT project_name, description, start, end
    FROM project
    WHERE id = ?
";
$projectStmt = $conn->prepare($projectQuery);
$projectStmt->bind_param("i", $project_id);
$projectStmt->execute();
$projectResult = $projectStmt->get_result();
$project = $projectResult->fetch_assoc();

if (!$project) {
    die("프로젝트 정보를 찾을 수 없습니다.");
}

// 현재 프로젝트 멤버 및 관리자 조회
$memberQuery = "
    SELECT pm.login_id, u.user_name, pm.project_role
    FROM project_member pm
    JOIN User u ON pm.login_id = u.login_id
    WHERE pm.project_id = ?
";
$memberStmt = $conn->prepare($memberQuery);
$memberStmt->bind_param("i", $project_id);
$memberStmt->execute();
$memberResult = $memberStmt->get_result();

$currentMembers = [];
$admin_id = null;

while ($member = $memberResult->fetch_assoc()) {
    if ($member['project_role'] == 1) { // 관리자
        $admin_id = $member['login_id'];
    }
    $currentMembers[$member['login_id']] = $member['user_name'];
}

// 모든 사용자 조회 (시스템 관리자를 제외)
$userQuery = "SELECT login_id, user_name FROM User WHERE role != 1";
$userResult = $conn->query($userQuery);

// 프로젝트 수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectName = trim($_POST['project_name']);
    $projectDescription = trim($_POST['project_description']);
    $projectStart = trim($_POST['project_start']);
    $projectEnd = trim($_POST['project_end']);
    $projectAdmin = trim($_POST['project_admin']);
    $projectMembers = $_POST['project_members'] ?? [];

    // 프로젝트 기본 정보 수정
    $updateProjectQuery = "
        UPDATE project
        SET project_name = ?, description = ?, start = ?, end = ?
        WHERE id = ?
    ";
    $updateProjectStmt = $conn->prepare($updateProjectQuery);
    $updateProjectStmt->bind_param("ssssi", $projectName, $projectDescription, $projectStart, $projectEnd, $project_id);
    $updateProjectStmt->execute();

    // 기존 멤버 처리
    $existingMemberIds = array_keys($currentMembers);
    $newMembers = array_diff($projectMembers, $existingMemberIds); // 추가된 멤버
    $removedMembers = array_diff($existingMemberIds, $projectMembers); // 삭제된 멤버

    // 삭제된 멤버 제거
    if (!empty($removedMembers)) {
        $removeQuery = "DELETE FROM project_member WHERE project_id = ? AND login_id = ?";
        $removeStmt = $conn->prepare($removeQuery);
        foreach ($removedMembers as $removedMember) {
            $removeStmt->bind_param("is", $project_id, $removedMember);
            $removeStmt->execute();
        }
    }

    // 추가된 멤버 삽입
    if (!empty($newMembers)) {
        $addQuery = "INSERT INTO project_member (project_id, login_id, project_role) VALUES (?, ?, ?)";
        $addStmt = $conn->prepare($addQuery);
        foreach ($newMembers as $newMember) {
            $role = ($newMember === $projectAdmin) ? 1 : 0; // 관리자는 role=1
            $addStmt->bind_param("isi", $project_id, $newMember, $role);
            $addStmt->execute();
        }
    }

    // 관리자 업데이트
    $updateAdminQuery = "UPDATE project_member SET project_role = 1 WHERE project_id = ? AND login_id = ?";
    $updateAdminStmt = $conn->prepare($updateAdminQuery);
    $updateAdminStmt->bind_param("is", $project_id, $projectAdmin);
    $updateAdminStmt->execute();

    // 성공 메시지 및 리다이렉션
    header("Location: m_project.php?project_id=$project_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>프로젝트 수정</title>
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
    </style>
</head>
<body>
    <div class="form-container">
        <h2>프로젝트 수정</h2>
        <form method="POST">
            <label for="project_name">프로젝트 이름</label>
            <input type="text" id="project_name" name="project_name" value="<?php echo htmlspecialchars($project['project_name']); ?>" required>

            <label for="project_description">프로젝트 설명</label>
            <textarea id="project_description" name="project_description" required><?php echo htmlspecialchars($project['description']); ?></textarea>

            <label for="project_start">프로젝트 시작일</label>
            <input type="date" id="project_start" name="project_start" value="<?php echo htmlspecialchars($project['start']); ?>" required>

            <label for="project_end">프로젝트 종료일</label>
            <input type="date" id="project_end" name="project_end" value="<?php echo htmlspecialchars($project['end']); ?>" required>

            <label for="project_admin">프로젝트 관리자</label>
            <select id="project_admin" name="project_admin" required>
                <?php while ($user = $userResult->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($user['login_id']); ?>" <?php echo ($user['login_id'] === $admin_id) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['user_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="project_members">프로젝트 진행 멤버</label>
            <select id="project_members" name="project_members[]" multiple required>
                <?php
                // 모든 사용자 다시 가져오기
                $userResult->data_seek(0);
                while ($user = $userResult->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($user['login_id']); ?>" <?php echo isset($currentMembers[$user['login_id']]) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['user_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <button type="submit">수정 완료</button>
        </form>
    </div>
</body>
</html>
