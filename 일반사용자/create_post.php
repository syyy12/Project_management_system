<?php
session_start();
include 'db.php';

$project_id = $_GET['project_id'] ?? null;
if (!$project_id) {
    die("잘못된 접근입니다.");
}

$login_id = $_SESSION['login_id'] ?? null;
if (!$login_id) {
    die("로그인이 필요합니다.");
}

// 시스템 관리자 여부 확인
$roleQuery = "SELECT role FROM User WHERE login_id = ?";
$roleStmt = $conn->prepare($roleQuery);
$roleStmt->bind_param("s", $login_id);
$roleStmt->execute();
$roleResult = $roleStmt->get_result();
$roleRow = $roleResult->fetch_assoc();
$is_system_admin = ($roleRow && $roleRow['role'] == 1);

// 프로젝트 관리자 여부 확인
$projectRoleQuery = "
    SELECT project_role
    FROM project_member
    WHERE login_id = ? AND project_id = ?
";
$projectRoleStmt = $conn->prepare($projectRoleQuery);
$projectRoleStmt->bind_param("si", $login_id, $project_id);
$projectRoleStmt->execute();
$projectRoleResult = $projectRoleStmt->get_result();
$projectRoleRow = $projectRoleResult->fetch_assoc();
$is_project_admin = ($projectRoleRow && $projectRoleRow['project_role'] == 1);

// 사용자가 게시글을 작성해 등록하는 경우
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $is_noticed = isset($_POST['is_noticed']) ? 1 : 0;

    if (!$is_system_admin && !$is_project_admin && $is_noticed) {
        $error = "공지사항 작성 권한이 없습니다.";
    } elseif (empty($title) || empty($content)) {
        $error = "제목과 내용을 모두 입력해주세요.";
    } else {
        $insertQuery = "
            INSERT INTO Post (project_id, login_id, title, content, is_noticed, created_date)
            VALUES (?, ?, ?, ?, ?, NOW())
        ";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("isssi", $project_id, $login_id, $title, $content, $is_noticed);

        if ($stmt->execute()) {
            header("Location: post.php?project_id=$project_id"); // 게시판 목록으로 이동
            exit();
        } else {
            $error = "게시글 등록 중 문제가 발생했습니다. 다시 시도해주세요.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>게시글 작성</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #004d99;
            text-align: center;
        }
        form {
            margin-top: 20px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        textarea {
            height: 150px;
        }
        .checkbox {
            margin-bottom: 15px;
        }
        .checkbox label {
            font-size: 16px;
        }
        button {
            padding: 10px 20px;
            background-color: #004d99;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #003366;
        }
        .error {
            color: red;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>게시글 작성</h2>
        <form method="post" action="create_post.php?project_id=<?php echo $project_id; ?>">
            <?php if (isset($error)): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>

            <label for="title">제목:</label>
            <input type="text" id="title" name="title" placeholder="제목을 입력하세요" required>

            <label for="content">내용:</label>
            <textarea id="content" name="content" placeholder="내용을 입력하세요" required></textarea>

            <div class="checkbox">
                <?php if ($is_system_admin || $is_project_admin): ?>
                    <label>
                        <input type="checkbox" name="is_noticed"> 공지로 설정
                    </label>
                <?php else: ?>
                    <label style="color: gray;">공지로 설정 (권한 없음)</label>
                <?php endif; ?>
            </div>

            <button type="submit">등록</button>
            <button type="button" onclick="location.href='post.php?project_id=<?php echo $project_id; ?>'">취소</button>
        </form>
    </div>
</body>
</html>
