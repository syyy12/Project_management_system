<?php
session_start();
include 'db.php';

// 로그인 여부 확인
if (!isset($_SESSION['login_id'])) {
    header("Location: login.php");
    exit();
}

$login_id = $_SESSION['login_id'];
$user_name = $_SESSION['user_name'];

// 참여 중인 프로젝트 목록 조회
$projectQuery = "
    SELECT pr.id, pr.project_name
    FROM project AS pr
    JOIN project_member AS pm ON pr.id = pm.project_id
    WHERE pm.login_id = ?
";
$projectStmt = $conn->prepare($projectQuery);
$projectStmt->bind_param("s", $login_id);
$projectStmt->execute();
$projectResult = $projectStmt->get_result();

// 참여 중인 프로젝트의 게시글 목록 조회 (수정일 기준 내림차순 정렬)
$postQuery = "
    SELECT p.id, p.title, p.created_date, p.updated_date, pr.id AS project_id, pr.project_name
    FROM Post AS p
    JOIN project AS pr ON p.project_id = pr.id
    JOIN project_member AS pm ON pr.id = pm.project_id
    WHERE pm.login_id = ?
    ORDER BY COALESCE(p.updated_date, p.created_date) DESC
";
$postStmt = $conn->prepare($postQuery);
$postStmt->bind_param("s", $login_id);
$postStmt->execute();
$postResult = $postStmt->get_result();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>홈페이지</title>
    <link rel="stylesheet" href="styles.css"> <!-- 외부 CSS 연결 -->
    <style>
        /* 전체 레이아웃 */
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        .header {
            background-color: #004d99;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h2 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            flex: 1;
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .section h3 {
            color: #4CAF50;
            margin-bottom: 10px;
            font-size: 22px;
        }
        ul {
            list-style: none;
            padding: 0;
        }
        li {
            margin-bottom: 10px;
        }
        a {
            color: #004d99;
            text-decoration: none;
            font-weight: bold;
        }
        a:hover {
            text-decoration: underline;
        }
        .logout-button {
            display: block;
            margin: 20px auto;
            width: 100%;
            max-width: 200px;
            padding: 10px 20px;
            text-align: center;
            font-size: 16px;
            color: white;
            background-color: #d9534f;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .logout-button:hover {
            background-color: #c9302c;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>(주) 영남대학</h2>
    </div>
    <div class="content">
        <!-- 프로젝트 목록 -->
        <div class="section">
            <h3>📂 프로젝트 목록</h3>
            <ul>
                <?php
                if ($projectResult->num_rows > 0) {
                    while ($project = $projectResult->fetch_assoc()) {
                        $projectName = htmlspecialchars($project['project_name']);
                        $projectId = $project['id'];
                        echo "<li><a href='project.php?project_id=$projectId'>$projectName</a></li>";
                    }
                } else {
                    echo "<li>참여 중인 프로젝트가 없습니다.</li>";
                }
                ?>
            </ul>
        </div>

        <!-- 게시글 목록 -->
        <div class="section">
            <h3>📝 전체 게시판</h3>
            <ul>
                <?php
                if ($postResult->num_rows > 0) {
                    while ($post = $postResult->fetch_assoc()) {
                        $postTitle = htmlspecialchars($post['title']);
                        $postId = $post['id'];
                        $projectId = $post['project_id'];
                        $projectName = htmlspecialchars($post['project_name']);
                        $createdDate = $post['created_date'];
                        $updatedDate = $post['updated_date'];
                        $displayDate = $updatedDate ?? $createdDate;

                        // 게시글 출력: 제목, 프로젝트 이름, 수정일 또는 작성일
                        echo "<li><a href='view_post.php?post_id=$postId&project_id=$projectId'>$postTitle</a> - $projectName ($displayDate)</li>";
                    }
                } else {
                    echo "<li>게시글이 없습니다.</li>";
                }
                ?>
            </ul>
        </div>
    </div>

    <!-- 로그아웃 버튼 -->
    <button class="logout-button" onclick="location.href='logout.php'">로그아웃</button>
</body>
</html>
