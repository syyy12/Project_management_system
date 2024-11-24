# 11/24 데이터베이스 연결 수정
<?php
// 데이터베이스 연결 설정
session_start();
include 'db.php';

// 연결 확인
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 프로젝트 ID 가져오기 (기본값: 1)
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 1;

// 프로젝트 이름 가져오기
$project_name_query = "SELECT project_name FROM project WHERE id = $project_id";
$project_name_result = $conn->query($project_name_query);

$project_name = "프로젝트 진행도 (멤버별)";
if ($project_name_result->num_rows > 0) {
    $project_name_row = $project_name_result->fetch_assoc();
    $project_name = $project_name_row['project_name'] . " 진행도 (멤버별)";
}

// 멤버별 진행도 가져오기
$sql = "
    SELECT 
        u.user_name AS member_name,
        st.login_id,
        COUNT(CASE WHEN st.is_completed = 3 THEN 1 END) AS completed_tasks, -- 완료된 테스크만 포함
        COUNT(*) AS total_tasks
    FROM sub_task st
    JOIN user u ON st.login_id = u.login_id
    JOIN task t ON st.task_id = t.id
    WHERE t.project_id = $project_id
    GROUP BY st.login_id, u.user_name
";
$result = $conn->query($sql);

$members = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project_name); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        header h1 {
            color: green;
            font-size: 24px;
        }
        .logout-btn {
            background-color: #006400;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        .logout-btn:hover {
            background-color: #004d00;
        }
        main {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h2 {
            font-size: 20px;
            color: green;
            margin-bottom: 20px;
        }
        .progress-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .progress-table th, .progress-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .progress-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .progress-table td:nth-child(2) {
            color: green;
            font-weight: bold;
        }
        .progress-table td:last-child {
            text-align: center;
        }
        /* 뒤로가기 버튼 */
        .back-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            font-size: 14px;
        }
        .back-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <header>
        <h1>(주) 영남대학교</h1>
        <button class="logout-btn" onclick="window.location.href='login.php'">로그아웃</button>
    </header>
    
    <main>
        <section class="progress-section">
            <!-- 동적으로 가져온 프로젝트 이름 출력 -->
            <h2><?php echo htmlspecialchars($project_name); ?></h2>
            <table class="progress-table">
                <thead>
                    <tr>
                        <th>멤버</th>
                        <th>진행도</th>
                        <th>완료 테스크 / 담당 테스크</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($members) > 0): ?>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($member['member_name']); ?></td>
                                <td>
                                    <?php 
                                        $progress = ($member['total_tasks'] > 0) 
                                            ? round(($member['completed_tasks'] / $member['total_tasks']) * 100, 2) 
                                            : 0;
                                        echo $progress . "%";
                                    ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($member['completed_tasks']) . " / " . htmlspecialchars($member['total_tasks']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center;">프로젝트에 할당된 멤버가 없습니다.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <!-- 뒤로가기 버튼 -->
    <button class="back-btn" onclick="window.location.href='analysis.php'">뒤로가기</button>
</body>
</html>
