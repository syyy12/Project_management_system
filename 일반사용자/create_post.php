<?php
session_start();
include 'db.php';

// 로그인 여부 확인
if (!isset($_SESSION['login_id'])) {
    header("Location: login.php");
    exit();
}

// 프로젝트 ID와 parent_post_id를 URL에서 가져오기
$project_id = $_GET['project_id'] ?? null;
$parent_post_id = $_GET['parent_post_id'] ?? null; // 부모 게시글 ID
if (!$project_id) {
    die("잘못된 접근입니다.");
}

// 현재 사용자 ID
$current_user_id = $_SESSION['login_id'];

// 프로젝트 이름 조회
$projectName = '';
$projectQuery = "SELECT project_name FROM project WHERE id = ?";
$projectStmt = $conn->prepare($projectQuery);
$projectStmt->bind_param("i", $project_id);
$projectStmt->execute();
$projectResult = $projectStmt->get_result();

if ($projectResult->num_rows > 0) {
    $projectRow = $projectResult->fetch_assoc();
    $projectName = htmlspecialchars($projectRow['project_name']);
} else {
    die("유효하지 않은 프로젝트 ID입니다.");
}

// 프로젝트 관리자 확인
$managerQuery = "
    SELECT COUNT(*) AS is_manager
    FROM project_member
    WHERE login_id = ? AND project_id = ? AND project_role = 1
";
$managerStmt = $conn->prepare($managerQuery);
$managerStmt->bind_param("si", $current_user_id, $project_id);
$managerStmt->execute();
$managerResult = $managerStmt->get_result();
$is_project_manager = $managerResult->fetch_assoc()['is_manager'] ?? 0;

// 시스템 관리자 확인
$systemAdminQuery = "
    SELECT role
    FROM User
    WHERE login_id = ?
";
$systemAdminStmt = $conn->prepare($systemAdminQuery);
$systemAdminStmt->bind_param("s", $current_user_id);
$systemAdminStmt->execute();
$systemAdminResult = $systemAdminStmt->get_result();
$is_system_admin = $systemAdminResult->fetch_assoc()['role'] == 1;

// 사용자가 게시글을 작성해 등록하는 경우
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $is_noticed = isset($_POST['is_notice']) ? 1 : 0; // 공지 여부
    $login_id = $_SESSION['login_id']; // 현재 로그인한 사용자 ID
    $post_id_to_store = $parent_post_id ? intval($parent_post_id) : null; // Post_id 값 설정

    if (empty($title) || empty($content)) {
        $error = "제목과 내용을 모두 입력해주세요.";
    } else {
        $insertQuery = "
            INSERT INTO Post (project_id, login_id, title, content, is_noticed, created_date, Post_id)
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("isssii", $project_id, $login_id, $title, $content, $is_noticed, $post_id_to_store);

        if ($stmt->execute()) {
            // 권한에 따라 이동 경로 결정
            if ($is_project_manager || $is_system_admin) {
                header("Location: m_post.php?project_id=$project_id");
            } else {
                header("Location: post.php?project_id=$project_id");
            }
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
    <title><?php echo $projectName; ?> 게시판 글쓰기</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .form-container {
            max-width: 400px;
            margin: 40px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-container h2 {
            text-align: center;
            color: #4CAF50;
            margin-bottom: 20px;
        }

        .form-container label {
            font-size: 16px;
            color: #333;
            margin-bottom: 5px;
            display: block;
        }

        .form-container input[type="text"],
        .form-container textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .form-container textarea {
            resize: none;
        }

        .form-container .buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .form-container .buttons button {
            width: 48%;
        }

        .error-message {
            color: red;
            font-size: 14px;
            margin-top: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2><?php echo $projectName; ?> 게시판 글쓰기</h2>

        <form method="post" action="create_post.php?project_id=<?php echo $project_id; ?>&parent_post_id=<?php echo $parent_post_id; ?>">
            <div>
                <label for="title">제목:</label>
                <input type="text" id="title" name="title" placeholder="제목을 입력하세요" required>
            </div>
            <div>
                <label for="content">내용:</label>
                <textarea id="content" name="content" rows="10" placeholder="내용을 입력하세요" required></textarea>
            </div>
            <?php if ($is_project_manager || $is_system_admin): ?>
                <div>
                    <label for="is_notice">
                        <input type="checkbox" id="is_notice" name="is_notice">
                        공지로 등록
                    </label>
                </div>
            <?php endif; ?>
            <div class="buttons">
                <button type="submit" class="primary">등록</button>
                <button type="button" class="secondary" onclick="location.href='post.php?project_id=<?php echo $project_id; ?>'">취소</button>
            </div>
        </form>

        <?php if (isset($error)) { ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php } ?>
    </div>
</body>
</html>
