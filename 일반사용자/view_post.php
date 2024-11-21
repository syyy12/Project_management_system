# 2024 11 21 15시 수정
<?php
session_start();
include 'db.php';

$post_id = $_GET['post_id'] ?? null;
$project_id = $_GET['project_id'] ?? null;
if (!$post_id || !$project_id) {
    die("잘못된 접근입니다.");
}

// 게시글 정보 조회
$postQuery = "
    SELECT p.title, p.content, p.created_date, p.updated_date, u.user_name
    FROM Post AS p
    JOIN user_view AS u ON p.login_id = u.login_id
    WHERE p.id = ?
";
$postStmt = $conn->prepare($postQuery);
$postStmt->bind_param("i", $post_id);
$postStmt->execute(); 
$postResult = $postStmt->get_result();
$post = $postResult->fetch_assoc();

if (!$post) {
    die("게시글을 찾을 수 없습니다.");
}

// 프로젝트 이름 조회
$projectName = '';
$projectQuery = "SELECT project_name FROM project WHERE id = ?";
$projectStmt = $conn->prepare($projectQuery);
$projectStmt->bind_param("i", $project_id);
$projectStmt->execute();
$projectResult = $projectStmt->get_result();
$project = $projectResult->fetch_assoc();

if ($project) {
    $projectName = htmlspecialchars($project['project_name']);
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            font-size: 36px;
            color: #004d99;
            margin-bottom: 20px;
        }

        .post-info {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .post-info p {
            margin: 5px 0;
        }

        .content-box {
            border: 1px solid #ddd;
            padding: 20px;
            background-color: #f9f9f9;
            font-size: 18px;
            line-height: 1.6;
            margin-top: 20px;
        }

        .buttons {
            margin-top: 30px;
            text-align: right;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
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

        button.disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 게시글 제목 -->
        <h2><?php echo htmlspecialchars($post['title']); ?></h2>

        <!-- 게시글 정보 -->
        <div class="post-info">
            <p><strong>작성자:</strong> <?php echo htmlspecialchars($post['user_name']); ?></p>
            <p><strong>작성일:</strong> <?php echo $post['created_date']; ?></p>
            <p><strong>최종 수정일:</strong> <?php echo $post['updated_date'] ?? '수정 없음'; ?></p>
            <p><strong>프로젝트 이름:</strong> <?php echo $projectName ?: '알 수 없음'; ?></p>
        </div>

        <!-- 게시글 내용 -->
        <div class="content-box">
            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
        </div>

        <!-- 버튼들 -->
        <div class="buttons">
            <button class="primary" onclick="location.href='post.php?project_id=<?php echo $project_id; ?>'">게시글 목록</button>
            <button class="disabled" disabled>첨부파일</button>
            <button class="secondary" onclick="location.href='home.php'">홈으로</button>
        </div>
    </div>
</body>
</html>
