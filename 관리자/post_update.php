<?php
session_start();
include 'db.php';

// 로그인 여부 확인
if (!isset($_SESSION['login_id'])) {
    header("Location: login.php");
    exit();
}

// 게시글 ID 및 프로젝트 ID 확인
$post_id = $_GET['post_id'] ?? null;
$project_id = $_GET['project_id'] ?? null;
if (!$post_id || !$project_id) {
    die("잘못된 접근입니다.");
}

// 현재 사용자 ID
$current_user_id = $_SESSION['login_id'];

// 현재 사용자 시스템 관리자 여부 확인
$userRoleQuery = "
    SELECT role
    FROM User
    WHERE login_id = ?
";
$userRoleStmt = $conn->prepare($userRoleQuery);
$userRoleStmt->bind_param("s", $current_user_id);
$userRoleStmt->execute();
$userRoleResult = $userRoleStmt->get_result();
$userRoleData = $userRoleResult->fetch_assoc();
$is_system_admin = $userRoleData && $userRoleData['role'] == 1;

// 현재 사용자 프로젝트 관리자 여부 확인
$projectRoleQuery = "
    SELECT project_role
    FROM project_member
    WHERE login_id = ? AND project_id = ?
";
$projectRoleStmt = $conn->prepare($projectRoleQuery);
$projectRoleStmt->bind_param("si", $current_user_id, $project_id);
$projectRoleStmt->execute();
$projectRoleResult = $projectRoleStmt->get_result();
$projectRoleData = $projectRoleResult->fetch_assoc();
$is_project_admin = $projectRoleData && $projectRoleData['project_role'] == 1;

// 게시글 정보 조회
$postQuery = "
    SELECT title, content
    FROM Post
    WHERE id = ?
";
$postStmt = $conn->prepare($postQuery);
$postStmt->bind_param("i", $post_id);
$postStmt->execute();
$postResult = $postStmt->get_result();
$post = $postResult->fetch_assoc();

if (!$post) {
    die("게시글을 찾을 수 없습니다.");
}

// 게시글 수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_title = $_POST['title'] ?? '';
    $new_content = $_POST['content'] ?? '';

    if (empty($new_title) || empty($new_content)) {
        $error = "제목과 내용을 모두 입력해야 합니다.";
    } else {
        $updateQuery = "
            UPDATE Post
            SET title = ?, content = ?, updated_date = NOW()
            WHERE id = ?
        ";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ssi", $new_title, $new_content, $post_id);
        $updateStmt->execute();

        // 수정 완료 후 역할에 따라 리다이렉트
        if ($is_system_admin || $is_project_admin) {
            header("Location: m_post.php?project_id=$project_id");
        } else {
            header("Location: post.php?project_id=$project_id");
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>게시글 수정</title>
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
            font-size: 24px;
            color: #004d99;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-size: 18px;
            font-weight: bold;
        }

        input[type="text"], textarea {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
        }

        textarea {
            resize: vertical;
            height: 150px;
        }

        .buttons {
            display: flex;
            justify-content: space-between;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
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

        .error {
            color: red;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>게시글 수정</h2>

        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="title">제목</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>

            <label for="content">내용</label>
            <textarea id="content" name="content" required><?php echo htmlspecialchars($post['content']); ?></textarea>

            <div class="buttons">
                <button type="submit" class="primary">수정</button>
                <button type="button" class="secondary" onclick="location.href='m_view_post.php?post_id=<?php echo $post_id; ?>&project_id=<?php echo $project_id; ?>'">취소</button>
            </div>
        </form>
    </div>
</body>
</html>
    WHERE login_id = ? AND project_id = ?
";
$projectRoleStmt = $conn->prepare($projectRoleQuery);
$projectRoleStmt->bind_param("si", $current_user_id, $project_id);
$projectRoleStmt->execute();
$projectRoleResult = $projectRoleStmt->get_result();
$is_project_admin = $projectRoleResult->fetch_assoc()['project_role'] == 1;

// 게시글 정보 조회
$postQuery = "
    SELECT title, content
    FROM Post
    WHERE id = ?
";
$postStmt = $conn->prepare($postQuery);
$postStmt->bind_param("i", $post_id);
$postStmt->execute();
$postResult = $postStmt->get_result();
$post = $postResult->fetch_assoc();

if (!$post) {
    die("게시글을 찾을 수 없습니다.");
}

// 게시글 수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_title = $_POST['title'] ?? '';
    $new_content = $_POST['content'] ?? '';

    if (empty($new_title) || empty($new_content)) {
        $error = "제목과 내용을 모두 입력해야 합니다.";
    } else {
        $updateQuery = "
            UPDATE Post
            SET title = ?, content = ?, updated_date = NOW()
            WHERE id = ?
        ";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ssi", $new_title, $new_content, $post_id);
        $updateStmt->execute();

        // 수정 완료 후 역할에 따라 리다이렉트
        if ($is_system_admin || $is_project_admin) {
            header("Location: m_post.php?project_id=$project_id");
        } else {
            header("Location: post.php?project_id=$project_id");
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>게시글 수정</title>
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
            font-size: 24px;
            color: #004d99;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-size: 18px;
            font-weight: bold;
        }

        input[type="text"], textarea {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
        }

        textarea {
            resize: vertical;
            height: 150px;
        }

        .buttons {
            display: flex;
            justify-content: space-between;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
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

        .error {
            color: red;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>게시글 수정</h2>

        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="title">제목</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>

            <label for="content">내용</label>
            <textarea id="content" name="content" required><?php echo htmlspecialchars($post['content']); ?></textarea>

            <div class="buttons">
                <button type="submit" class="primary">수정</button>
                <button type="button" class="secondary" onclick="location.href='m_view_post.php?post_id=<?php echo $post_id; ?>&project_id=<?php echo $project_id; ?>'">취소</button>
            </div>
        </form>
    </div>
</body>
</html>
