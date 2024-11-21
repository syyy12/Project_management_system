<?php
# 2024 11 21 : 1530 수정 디자인 밎 공지 유뮤 , 로그인 상시 확인 추가
session_start();
include 'db.php';

// 로그인 여부 확인
if (!isset($_SESSION['login_id'])) {
    header("Location: login.php");
    exit();
}
// 프로젝트 ID를 URL에서 가져오기
$project_id = $_GET['project_id'] ?? null;
if (!$project_id) {
    die("잘못된 접근입니다.");
}

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

// 사용자가 게시글을 작성해 등록하는 경우
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $is_noticed = isset($_POST['is_notice']) ? 1 : 0; // 공지 여부
    $login_id = $_SESSION['login_id']; // 현재 로그인한 사용자 ID

    if (empty($title) || empty($content)) {
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

        <form method="post" action="create_post.php?project_id=<?php echo $project_id; ?>">
            <div>
                <label for="title">제목:</label>
                <input type="text" id="title" name="title" placeholder="제목을 입력하세요" required>
            </div>
            <div>
                <label for="content">내용:</label>
                <textarea id="content" name="content" rows="10" placeholder="내용을 입력하세요" required></textarea>
            </div>
            <div>
                <label for="is_notice">
                    <input type="checkbox" id="is_notice" name="is_notice">
                    공지로 등록
                </label>
            </div>
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
