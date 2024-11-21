<?php
# 2024 11 21 16시 수정 : 관리자 유무에 따라 view.php , m_view.php 경로 다르게(아직 검증 x)
#                      : 제목 관련 부가기능 추가함
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

// 현재 사용자 ID
$current_user_id = $_SESSION['login_id'];

// 게시글 목록 조회 쿼리
$postQuery = "
    SELECT p.id, p.title, p.created_date, p.updated_date, u.user_name, p.login_id AS author_id
    FROM Post AS p
    JOIN User AS u ON p.login_id = u.login_id
    WHERE p.project_id = ?
    ORDER BY p.created_date DESC
";
$postStmt = $conn->prepare($postQuery);
$postStmt->bind_param("i", $project_id);
$postStmt->execute();
$postResult = $postStmt->get_result();

// 프로젝트 관리자 확인 쿼리
$managerQuery = "
    SELECT COUNT(*) AS is_manager
    FROM project_member
    WHERE project_id = ? AND login_id = ? AND project_role = 1
";
$managerStmt = $conn->prepare($managerQuery);
$managerStmt->bind_param("is", $project_id, $current_user_id);
$managerStmt->execute();
$managerResult = $managerStmt->get_result();
$is_manager = $managerResult->fetch_assoc()['is_manager'] ?? 0;
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>프로젝트 <?php echo htmlspecialchars($project_id); ?> 게시판</title>
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

        .post-list {
            margin-top: 20px;
        }

        .post-list ul {
            list-style: none;
            padding: 0;
        }

        .post-list ul li {
            padding: 15px 10px;
            margin-bottom: 10px;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .post-list ul li a {
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
            color: #004d99;
        }

        .post-list ul li a:hover {
            text-decoration: underline;
        }

        .post-meta {
            margin-top: 5px;
            font-size: 14px;
            color: #666;
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
    </style>
</head>
<body>
    <div class="container">
        <!-- 게시판 제목 -->
        <h2>프로젝트 <?php echo htmlspecialchars($project_id); ?> 게시판</h2>

        <!-- 게시글 목록 -->
        <div class="post-list">
            <ul>
                <?php
                if ($postResult->num_rows > 0) {
                    while ($post = $postResult->fetch_assoc()) {
                        $postTitle = htmlspecialchars($post['title']);
                        $postId = $post['id'];
                        $userName = htmlspecialchars($post['user_name']);
                        $createdDate = $post['created_date'];
                        $updatedDate = $post['updated_date'] ?? '최종 수정 없음';
                        $is_author = $post['author_id'] === $current_user_id;

                        // 관리자 또는 게시글 작성자 여부 확인
                        if ($is_author || $is_manager) {
                            $target_url = "m_view_post.php";
                        } else {
                            $target_url = "view_post.php";
                        }

                        echo "<li>
                            <a href='$target_url?post_id=$postId&project_id=$project_id'>$postTitle</a>
                            <div class='post-meta'>
                                작성자: $userName | 작성일: $createdDate | 수정일: $updatedDate
                            </div>
                        </li>";
                    }
                } else {
                    echo "<li>게시글이 없습니다.</li>";
                }
                ?>
            </ul>
        </div>

        <!-- 버튼들 -->
        <div class="buttons">
            <button class="primary" onclick="location.href='create_post.php?project_id=<?php echo $project_id; ?>'">글 쓰기</button>
            <button class="secondary" onclick="location.href='project.php?project_id=<?php echo $project_id; ?>'">목록</button>
        </div>
    </div>
</body>
</html>
