<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>분석 페이지</title>
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
            display: flex;
            justify-content: space-between;
        }
        .analysis-section {
            flex: 1;
            padding: 20px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .analysis-section h2 {
            margin-bottom: 20px;
            font-size: 20px;
            color: green;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        table th {
            background-color: #f2f2f2;
        }
        .list-link {
            text-decoration: none;
            color: green;
            font-weight: bold;
        }
        .list-link:hover {
            text-decoration: underline;
        }
        .progress {
            color: green;
            font-weight: bold;
            margin-left: 10px;
        }
        .detail-link {
            text-decoration: none;
            color: #007bff;
            margin-left: 15px;
        }
        .detail-link:hover {
            text-decoration: underline;
        }
        .progress-container {
            width: 100%;
            height: 20px;
            background: #eee;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
            position: relative;
        }
        .progress-bar {
            height: 100%;
            background: red;
            width: <?php echo $overall_success_rate; ?>%; /* PHP에서 계산된 성공률 적용 */
        }
        .success-rate {
            margin-bottom: 20px;
        }
        .success-rate span {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        /* 뒤로가기 버튼 스타일 */
        .back-btn {
            position: fixed;
            bottom: 150px;
            right: 20px;
            background-color: #c0c0c0; /* 회색 */
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }
        .back-btn:hover {
            background-color: #666666; /* 약간 어두운 회색 */
        }
    </style>
</head>
<body>
    <header>
        <h1>(주) 영남대학교</h1>
        <button class="logout-btn" onclick="window.location.href='login.php'">로그아웃</button>
    </header>

    <main>
        <section class="analysis-section">
            <h2>분석 페이지</h2>

            <!-- 전체 프로젝트 성공률 -->
            <div class="success-rate">
                <?php
                // 데이터베이스 연결 설정
                $servername = "localhost";
                $username = "root";
                $password = "";
                $dbname = "mydatabase";

                // MySQL 연결 생성
                $conn = new mysqli($servername, $username, $password, $dbname);

                if ($conn->connect_error) {
                    die("연결 실패: " . $conn->connect_error);
                }

                // 프로젝트 데이터 가져오기
                $project_sql = "SELECT COUNT(*) AS total_projects, 
                                       SUM(CASE WHEN finish = 1 THEN 1 ELSE 0 END) AS completed_projects 
                                FROM project";
                $project_result = $conn->query($project_sql);
                $project_data = $project_result->fetch_assoc();

                $total_projects = $project_data['total_projects'];
                $completed_projects = $project_data['completed_projects'];

                // 전체 프로젝트 성공률 계산
                $overall_success_rate = $total_projects > 0 ? ($completed_projects / $total_projects) * 100 : 0;
                $overall_success_rate = round($overall_success_rate, 2); // 소수점 2자리까지 출력
                ?>

                <span>전체 프로젝트 성공률</span>
                <div class="progress-container">
                    <div class="progress-bar" style="width: <?php echo $overall_success_rate; ?>%;"></div> <!-- PHP로 동적 값 적용 -->
                </div>
                <span><?php echo $overall_success_rate; ?>%</span>
            </div>

            <!-- 프로젝트 진행도 테이블 -->
            <div class="project-status">
                <table>
                    <thead>
                        <tr>
                            <th>프로젝트 이름</th>
                            <th>진행도</th>
                            <th>상세 보기</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // 프로젝트 목록 가져오기
                        $project_sql = "SELECT id, project_name, finish FROM project";
                        $project_result = $conn->query($project_sql);

                        if ($project_result->num_rows > 0) {
                            while ($project_row = $project_result->fetch_assoc()) {
                                $project_id = $project_row['id'];
                                $project_name = htmlspecialchars($project_row['project_name']);

                                // task 테이블에서 진행률 계산
                                $task_sql = "SELECT COUNT(*) AS total_tasks, 
                                                    SUM(is_completed) AS completed_tasks 
                                             FROM task 
                                             WHERE project_id = $project_id";
                                $task_result = $conn->query($task_sql);
                                $task_data = $task_result->fetch_assoc();

                                $total_tasks = $task_data['total_tasks'];
                                $completed_tasks = $task_data['completed_tasks'];

                                // 진행률 계산
                                $progress = $total_tasks > 0 ? ($completed_tasks / $total_tasks) * 100 : 0;
                                $progress = round($progress, 2); // 소수점 2자리까지 반올림

                                // 테이블 출력
                                echo "<tr>";
                                echo "<td><a href='analysis1.php' class='list-link'>$project_name</a></td>";
                                echo "<td class='progress'>$progress%</td>";
                                echo "<td><a href='analysis2.php?project_id=$project_id' class='detail-link'>상세 진행도 보기</a></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3'>프로젝트 데이터가 없습니다.</td></tr>";
                        }

                        // 연결 종료
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- 뒤로가기 버튼 -->
    <a href="m_home.php" class="back-btn">뒤로가기</a>
</body>
</html>
