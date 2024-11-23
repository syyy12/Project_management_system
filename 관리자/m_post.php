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

// 현재 사용자 ID
$current_user_id = $_SESSION['login_id'];

// 검색 기능 처리
$search_category = $_GET['search_category'] ?? null;
$search_keyword = $_GET['search_keyword'] ?? null;
$is_search_valid = true;

// 기본 게시글 목록 조회 쿼리
$postQuery = "
    SELECT p.id, p.title, p.created_date, p.updated_date, u.user_name, p.login_id AS author_id
    FROM Post AS p
    JOIN User AS u ON p.login_id = u.login_id
    WHERE p.project_id = ?
";

// 검색 조건 추가
if ($search_category && $search_category !== "검색 카테고리") {
    if (empty($search_keyword)) {
        // 검색 키워드가 없을 때 아무 결과도 출력되지 않도록 설정
        $is_search_valid = false;
    } else {
        switch ($search_category) {
            case '작성자':
                $postQuery .= " AND u.user_name LIKE ?";
                $search_keyword = '%' . $search_keyword . '%';
                break;
            case '제목':
                $postQuery .= " AND p.title LIKE ?";
                $search_keyword = '%' . $search_keyword . '%';
                break;
            case '내용':
                $postQuery .= " AND p.content LIKE ?";
                $search_keyword = '%' . $search_keyword . '%';
                break;
        }
    }
}

// 정렬 조건 추가
$postQuery .= " ORDER BY p.created_date DESC";

if ($is_search_valid) {
    $postStmt = $conn->prepare($postQuery);
    if ($search_category && $search_category !== "검색 카테고리") {
        $postStmt->bind_param("is", $project_id, $search_keyword);
    } else {
        $postStmt->bind_param("i", $project_id);
    }
    $postStmt->execute();
    $postResult = $postStmt->get_result();
} else {
    $postResult = null;
}

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

// 시스템 관리자 확인 쿼리
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

        .search-bar {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-bar select, .search-bar input, .search-bar button {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .search-bar button {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }

        .search-bar button:hover {
            background-color: #45a049;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        button.delete {
            background-color: #d9534f;
        }

        button.delete:hover {
            background-color: #c9302c;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 게시판 제목 -->
        <h2>프로젝트 <?php echo htmlspecialchars($project_id); ?> 게시판</h2>

        <!-- 검색 기능 -->
        <form method="GET" class="search-bar">
            <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
            <select name="search_category">
                <option value="검색 카테고리" <?php if ($search_category === "검색 카테고리") echo 'selected'; ?>>검색 카테고리</option>
                <option value="작성자" <?php if ($search_category === '작성자') echo 'selected'; ?>>작성자</option>
                <option value="제목" <?php if ($search_category === '제목') echo 'selected'; ?>>제목</option>
                <option value="내용" <?php if ($search_category === '내용') echo 'selected'; ?>>내용</option>
            </select>
            <input type="text" name="search_keyword" placeholder="검색어를 입력하세요" value="<?php echo htmlspecialchars($search_keyword ?? ''); ?>">
            <button type="submit">검색</button>
        </form>

        <!-- 게시글 목록 -->
        <div class="post-list">
            <ul>
                <?php
                if ($is_search_valid && $postResult && $postResult->num_rows > 0) {
                    while ($post = $postResult->fetch_assoc()) {
                        $postTitle = htmlspecialchars($post['title']);
                        $postId = $post['id'];
                        $userName = htmlspecialchars($post['user_name']);
                        $createdDate = $post['created_date'];
                        $updatedDate = $post['updated_date'] ?? '최종 수정 없음';
                        $is_author = $post['author_id'] === $current_user_id;

                        // 사용자 권한에 따라 이동 URL 결정
                        $target_url = ($is_author || $is_manager)
                            ? "m_view_post.php"
                            : "view_post.php";

                        echo "<li>
                            <div>
                                <a href='$target_url?post_id=$postId&project_id=$project_id'>$postTitle</a>
                                <div class='post-meta'>
                                    작성자: $userName | 작성일: $createdDate | 수정일: $updatedDate
                                </div>
                            </div>";

                        // 삭제 버튼 추가 (관리자 및 시스템 관리자 표시)
                        if ($is_manager || $is_system_admin) {
                            echo "<form action='post_delete.php' method='GET' style='margin: 0;' >
                                <input type='hidden' name='post_id' value='$postId'>
                                <input type='hidden' name='project_id' value='$project_id'>
                                <button type='submit' class='delete'>삭제</button>
                            </form>";
                        }

                        echo "</li>";
                    }
                } else {
                    echo "<li>검색 결과가 없습니다.</li>";
                }
                ?>
            </ul>
        </div>

        <!-- 버튼들 -->
        <div class="buttons">
            <button class="primary" onclick="location.href='create_post.php?project_id=<?php echo $project_id; ?>'">글 쓰기</button>
        </div>
    </div>
</body>
</html>
