<?php
# 최종 2024 11 21 18시 수정
session_start();
include 'db.php'; // 데이터베이스 연결

// 로그인 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_id = trim($_POST['login_id']); // 입력된 아이디
    $password = trim($_POST['password']); // 입력된 비밀번호
    $is_admin_checked = isset($_POST['is_admin']) ? 1 : 0; // 시스템 관리자 체크 여부

    // SQL 쿼리: 아이디와 비밀번호 확인
    $query = "SELECT login_id, user_name, role FROM User WHERE login_id = ? AND password = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $login_id, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    // 결과 확인
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // 세션에 사용자 정보 저장
        $_SESSION['login_id'] = $user['login_id'];
        $_SESSION['user_name'] = $user['user_name'];
        $_SESSION['role'] = $user['role'];

        // 관리자 체크박스가 선택된 경우
        if ($is_admin_checked) {
            if ($user['role'] == 1) {
                // 시스템 관리자 + 체크박스 선택
                header("Location: m_home.php"); // 관리자 홈 화면으로 이동
                exit();
            } else {
                // 일반 사용자가 관리자 체크박스를 선택한 경우 에러
                $error = "관리자 권한이 없습니다.";
            }
        } else {
            // 관리자 체크박스가 선택되지 않은 경우
            if ($user['role'] == 0) {
                // 일반 사용자
                header("Location: home.php"); // 일반 사용자 홈 화면으로 이동
                exit();
            } else {
                // 관리자 계정이 관리자 체크박스를 선택하지 않은 경우 에러
                $error = "관리자 계정으로 로그인하려면 관리자 옵션을 선택하세요.";
            }
        }
    } else {
        $error = "아이디 또는 비밀번호가 잘못되었습니다.";
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로그인</title>
    <link rel="stylesheet" href="styles.css"> <!-- 외부 CSS 파일 연결 -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        form {
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 300px;
        }
        h2 {
            text-align: center;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        label {
            display: block;
            margin-bottom: 10px;
        }
        button {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 4px;
            background-color: #4CAF50;
            color: white;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background-color: #45a049;
        }
        .register-button {
            background-color: #007BFF; /* 파란색 */
            margin-top: 10px;
        }
        .register-button:hover {
            background-color: #0056b3;
        }
        .error {
            color: red;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <form method="post" action="login.php">
        <h2>(주) 영남대학</h2>
        <input type="text" name="login_id" placeholder="아이디" required>
        <input type="password" name="password" placeholder="비밀번호" required>
        <div>
            <label>
                <input type="checkbox" name="is_admin"> 시스템 관리자
            </label>
        </div>
        <button type="submit">로그인</button>
        <!-- 회원가입 버튼 -->
        <button type="button" class="register-button" onclick="location.href='register.php'">회원가입</button>
        <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
    </form>
</body>
</html>
