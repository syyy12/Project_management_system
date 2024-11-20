<?php
session_start();
include 'db.php'; // 데이터베이스 연결

// 로그인 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_id = $_POST['login_id'];
    $password = $_POST['password'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0; // 시스템 관리자 체크 여부

    // SQL 쿼리: 로그인 아이디, 비밀번호, 역할(role) 확인
    $query = "SELECT * FROM User WHERE login_id = ? AND password = ? AND role = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $login_id, $password, $is_admin);
    $stmt->execute();
    $result = $stmt->get_result();

    // 결과 확인
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['login_id'] = $user['login_id'];
        $_SESSION['user_name'] = $user['user_name'];
        $_SESSION['role'] = $user['role'];

        // 로그인 성공 시 home.php로 이동
        header("Location: home.php");
        exit();
    } else {
        $error = "아이디, 비밀번호 또는 권한이 잘못되었습니다.";
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
        /* 스타일 적용 */
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
        .error {
            color: red;
            text-align: center;
            margin-top: 10px;
        }
        .form-actions {
            display: flex;
            justify-content: space-between;
        }
        .form-actions button {
            width: 48%;
        }
    </style>
</head>
<body>
    <form method="post" action="login.php">
        <h2>(주) 영남대학</h2>
        <label>
            <input type="checkbox" name="is_admin"> 시스템 관리자
        </label>
        <input type="text" name="login_id" placeholder="아이디" required>
        <input type="password" name="password" placeholder="비밀번호" required>
        <div class="form-actions">
            <button type="submit">로그인</button>
            <button type="button" onclick="location.href='register.php'">회원가입</button>
        </div>
        <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
    </form>
</body>
</html>
