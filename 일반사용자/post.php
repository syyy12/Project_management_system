# 2024 11 21 15시 수정
<?php
session_start();
include 'db.php';

// 로그인 여부 확인
if (!isset($_SESSION['login_id'])) {
    header("Location: login.php");
    exit();
}

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
$projectStmt->close();

// 게시글 목록 조회
$postQuery = "
    SELECT p.id, p.title, p.created_date, p.updated_date, p.Post_id, p.is_noticed, u.user_name
    FROM Post AS p
    JOIN User AS u ON p.login_id = u.login_id
    WHERE p.project_id = ?
    ORDER BY p.created_date DESC
";
$postStmt = $conn->prepare($postQuery);
$postStmt->bind_param("i", $project_id);
$postStmt->execute();
$postResult = $postStmt->get_result();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> <?php echo $projectName; ?> 게시판</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #333;
        }
        ul {
            list-style: none;
            padding: 0;
        }
        ul li {
            padding: 10px;
            margin-bottom: 10px;
            background: #f0f2f5;
            border-radius: 5px;
        }
        ul li a {
            text-decoration: none;
            font-weight: bold;
            color: #004d99;
        }
        ul li a:hover {
            text-decoration: underline;
        }
        .buttons {
            text-align: right;
            margin-top: 20px;
        }
        button {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            background: #004d99;
            color: white;
            cursor: pointer;
        }
        button.secondary {
            background: #d9534f;
        }
        button:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 게시판 제목 -->
        <h2> <?php echo $projectName; ?> 게시판</h2>

        <!-- 게시글 목록 -->
        <h3>📝 게시글 목록</h3>
        <ul>
            <?php
            if ($postResult->num_rows > 0) {
                while ($post = $postResult->fetch_assoc()) {
                    $postTitle = htmlspecialchars($post['title']);
                    $postId = $post['id'];
                    $postParentId = $post['Post_id'];
                    $isNoticed = $post['is_noticed'];
                    $userName = htmlspecialchars($post['user_name']);
                    $createdDate = $post['created_date'];
                    $updatedDate = $post['updated_date'] ?? '최종 수정 없음';

                    // 공지 여부 확인
                    if ($isNoticed) {
                        $postTitle = "[공지] $postTitle";
                    }

                    // 답글 여부 확인
                    if ($postParentId) {
                        $parentQuery = "SELECT title FROM post WHERE id = ?";
                        $parentStmt = $conn->prepare($parentQuery);
                        $parentStmt->bind_param("i", $postParentId);
                        $parentStmt->execute();
                        $parentResult = $parentStmt->get_result();

                        if ($parentResult->num_rows > 0) {
                            $parentRow = $parentResult->fetch_assoc();
                            $parentTitle = htmlspecialchars($parentRow['title']);
                            $postTitle = "[답글: $parentTitle] $postTitle";
                        }
                        $parentStmt->close();
                    }

                    echo "<li>
                        <a href='view_post.php?post_id=$postId&project_id=$project_id'>$postTitle</a>
                        <div>작성자: $userName | 작성일: $createdDate | 수정일: $updatedDate</div>
                    </li>";
                }
            } else {
                echo "<li>게시글이 없습니다.</li>";
            }
            ?>
        </ul>

        <!-- 버튼 -->
        <div class="buttons">
            <button onclick="location.href='create_post.php?project_id=<?php echo $project_id; ?>'">글 쓰기</button>
            <button class="secondary" onclick="location.href='project.php?project_id=<?php echo $project_id; ?>'">프로젝트 정보</button>
            <button class="secondary" onclick="location.href='home.php'">홈으로</button>
        </div>
    </div>
</body>
</html>
