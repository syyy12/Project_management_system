# 🎯 프로젝트 관리 시스템 (PHP 기반)

본 시스템은 (주)영남대학을 기반으로 제작된 **PHP 기반 프로젝트 관리 시스템**입니다.  
로그인/회원가입부터 프로젝트 및 테스크(Task) 관리까지, 프로젝트 진행에 필요한 전반적인 기능을 제공합니다.

---

## 📌 주요 기능
- 사용자 및 관리자 로그인/회원가입
- 관리자 전용 프로젝트 관리 기능
- 테스크(Task) 생성 및 수정
- 프로젝트 진행률 확인
- 게시판 기능 (추후 확장 가능)

---

## 📷 페이지별 소개

### 🔐 1. 로그인 페이지 (`login.php`)
- 일반 사용자 및 시스템 관리자가 로그인할 수 있는 페이지입니다.
- 관리자는 체크박스를 통해 관리자 권한으로 로그인합니다.

![login](./img/img1.png)

---

### 🧾 2. 회원가입 페이지 (`register.php`)
- 아이디, 비밀번호, 이름을 입력하여 회원가입을 진행합니다.
- 체크박스를 통해 관리자 권한 부여 여부를 설정할 수 있습니다.

![register](./img/img2.png)

---

### ❌ 3. 관리자 권한 오류 메시지
- 관리자 권한 없이 관리자 계정으로 로그인 시 오류 메시지가 출력됩니다.

![admin_login_error](./img/img3.png)

---

### 🧑‍💼 4. 관리자 홈 페이지 (`m_home.php`)
- 전체 프로젝트 목록 확인 및 생성 가능
- 게시판 접근 가능

![admin_home](./img/img4.png)

---

### 👤 5. 일반 사용자 홈 페이지 (`home.php`)
- 본인이 참여 중인 프로젝트 및 전체 게시글 확인 가능

![user_home](./img/img5.png)

---

### 🆕 6. 프로젝트 생성 페이지 (`m_create.php`)
- 프로젝트 이름, 설명, 일정, 참여 인원 등을 입력하여 프로젝트를 생성합니다.

![create_project](./img/img6.png)

---

### 📄 7. 프로젝트 상세 페이지 (`m_project.php`)
- 프로젝트 정보, 테스크 목록, 진행률, 게시판 접근 등 전반적인 내용을 확인할 수 있습니다.

![project_detail](./img/img7.png)

---

### ➕ 8. 테스크 추가 페이지 (`m_task_add.php`)
- 테스크 이름, 설명, 일정, 알림 기준 비율 등을 입력하여 테스크를 추가합니다.

![add_task](./img/img8.png)

---

### ✏️ 9. 프로젝트 및 테스크 수정 페이지 (`m_project_edit.php`)
- 기존 프로젝트 및 테스크의 내용을 수정하거나 삭제할 수 있습니다.

![edit_project_task](./img/img9.png)

---

### ✅ 10. 수정 완료 알림 페이지 (`process_project_edit.php`)
- 수정이 완료되면 성공 메시지를 출력하고, 목록 페이지로 돌아가는 버튼이 제공됩니다.

![edit_complete](./img/img10.png)

---

## 📂 프로젝트 실행 방법
1. `XAMPP` 또는 `MAMP` 등 로컬 서버 실행
2. 해당 프로젝트 폴더를 `htdocs` 또는 웹 루트 디렉토리에 배치
3. `localhost/login.php`에서 시작
4. MySQL DB 연결은 별도 설정 파일 참고

---

## 👨‍💻 개발 환경
- PHP 8.x
- MySQL
- HTML/CSS
- JavaScript (Vanilla)

---

## ✨ 향후 계획
- 게시판 CRUD 기능 추가
- 프로젝트 참여자 간 채팅 기능
- 알림/리마인드 이메일 연동
