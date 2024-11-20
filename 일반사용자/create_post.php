<?php
session_start();
include 'db.php';

$project_id = $_GET['project_id'] ?? null;
if (!$project_id) {
    die("잘못된 접근입니다.");
}

// 사용자가 게시글을 작성해 등록하는 경우
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $login_id = $_SESSION['login_id']; // 현재 로그인한 사용자 ID

    if (empty($title) || empty($content)) {
        $error = "제목과 내용을 모두 입력해주세요.";
    } else {
        $insertQuery = "
            INSERT INTO Post (project_id, login_id, title, content, created_date)
            VALUES (?, ?, ?, ?, NOW())
        ";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("isss", $project_id, $login_id, $title, $content);

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
</head>
<body>
    <h2>프로젝트 <?php echo htmlspecialchars($project_id); ?> 게시판에 글쓰기</h2>

    <form method="post" action="create_post.php?project_id=<?php echo $project_id; ?>">
        <div>
            <label for="title">제목:</label>
            <input type="text" id="title" name="title" placeholder="제목을 입력하세요" required>
        </div>
        <div>
            <label for="content">내용:</label>
            <textarea id="content" name="content" rows="10" placeholder="내용을 입력하세요" required></textarea>
        </div>
        <div style="margin-top: 20px;">
            <button type="submit">등록</button>
            <button type="button" onclick="location.href='post.php?project_id=<?php echo $project_id; ?>'">취소</button>
        </div>
    </form>

    <?php
    if (isset($error)) {
        echo "<p style='color: red;'>$error</p>";
    }
    ?>
</body>
</html>
