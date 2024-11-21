<?php
session_start();
include 'db.php';


// 모든 프로젝트 목록 조회
$projectQuery = "
    SELECT id, project_name
    FROM project
";
$projectResult = $conn->query($projectQuery);

// 모든 게시글 조회 (최신순 정렬)
$postQuery = "
    SELECT p.id, p.title, p.created_date, p.updated_date, pr.project_name
    FROM Post AS p
    JOIN project AS pr ON p.project_id = pr.id
    ORDER BY COALESCE(p.updated_date, p.created_date) DESC
";
$postResult = $conn->query($postQuery);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 홈</title>
    <style>
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
        .create-button {
            display: inline-block;
            margin-bottom: 10px;
            padding: 10px 20px;
            font-size: 16px;
            color: white;
            background-color: #4CAF50;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
        }
        .create-button:hover {
            background-color: #45a049;
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
        .analysis-button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            font-size: 16px;
            color: white;
            background-color: #004d99;
            text-decoration: none;
            border-radius: 4px;
            text-align: center;
        }
        .analysis-button:hover {
            background-color: #003366;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>관리자 페이지</h2>
    </div>
    <div class="content">
        <!-- 프로젝트 목록 -->
        <div class="section">
            <h3>📂 전체 프로젝트 목록</h3>
            <a href="m_create.php" class="create-button">+ 프로젝트 생성</a>
            <ul>
                <?php
                if ($projectResult->num_rows > 0) {
                    while ($project = $projectResult->fetch_assoc()) {
                        $projectName = htmlspecialchars($project['project_name']);
                        $projectId = $project['id'];
                        echo "<li><a href='project.php?project_id=$projectId'>$projectName</a></li>";
                    }
                } else {
                    echo "<li>생성된 프로젝트가 없습니다.</li>";
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
                        $projectName = htmlspecialchars($post['project_name']);
                        $createdDate = $post['created_date'];
                        $updatedDate = $post['updated_date'];
                        $displayDate = $updatedDate ?? $createdDate;

                        echo "<li><a href='view_post.php?post_id={$post['id']}'>{$postTitle}</a> - $projectName ($displayDate)</li>";
                    }
                } else {
                    echo "<li>게시글이 없습니다.</li>";
                }
                ?>
            </ul>
        </div>
    </div>

    <!-- 분석 페이지 버튼 -->
    <a href="analysis.php" class="analysis-button">분석 페이지로 이동</a>

    <!-- 로그아웃 버튼 -->
    <button class="logout-button" onclick="location.href='logout.php'">로그아웃</button>
</body>
</html>
