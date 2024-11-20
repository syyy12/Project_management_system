<?php
session_start();
include 'db.php'; // 데이터베이스 연결

// 회원가입 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_id = $_POST['login_id'];
    $password = $_POST['password'];
    $user_name = $_POST['user_name'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    // 필수 입력 값 확인
    if (empty($login_id) || empty($password) || empty($user_name)) {
        $error = "모든 필드를 입력해주세요.";
    } else {
        // 아이디 중복 체크
        $query = "SELECT * FROM User WHERE login_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $login_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "이미 존재하는 아이디입니다.";
        } else {
            // 사용자 정보 삽입
            $query = "INSERT INTO User (login_id, password, user_name, role) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssi", $login_id, $password, $user_name, $is_admin);

            if ($stmt->execute()) {
                $success = "회원가입에 성공했습니다! 로그인 페이지로 이동합니다.";
                header("refresh:2;url=login.php"); // 2초 후 로그인 페이지로 리다이렉트
                exit();
            } else {
                $error = "회원가입 중 오류가 발생했습니다. 다시 시도해주세요.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회원가입</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            font-size: 36px;
            color: #004d99;
            text-align: center;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            font-size: 18px;
            margin-bottom: 8px;
            color: #333;
        }

        input[type="text"], input[type="password"] {
            padding: 10px;
            font-size: 16px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
        }

        input[type="checkbox"] {
            margin-right: 10px;
        }

        button {
            padding: 12px 20px;
            font-size: 18px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
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

        .error {
            color: #d9534f;
            font-size: 16px;
            margin-top: 10px;
        }

        .success {
            color: #5cb85c;
            font-size: 16px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>회원가입</h2>
        <form method="post" action="register.php">
            <label for="login_id">아이디</label>
            <input type="text" id="login_id" name="login_id" placeholder="아이디" required>

            <label for="password">비밀번호</label>
            <input type="password" id="password" name="password" placeholder="비밀번호" required>

            <label for="user_name">이름</label>
            <input type="text" id="user_name" name="user_name" placeholder="이름" required>

            <label><input type="checkbox" name="is_admin"> 시스템 관리자</label>

            <button type="submit" class="primary">회원가입</button>
            <button type="button" class="secondary" onclick="location.href='login.php'">취소</button>
        </form>

        <?php
        if (isset($error)) {
            echo "<p class='error'>$error</p>";
        }
        if (isset($success)) {
            echo "<p class='success'>$success</p>";
        }
        ?>
    </div>
</body>
</html>
