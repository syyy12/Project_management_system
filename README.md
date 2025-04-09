# 🎯 프로젝트 관리 시스템 (PHP 기반)

본 시스템은 PHP 기반 프로젝트 관리 시스템입니다.  
로그인/회원가입부터 프로젝트 및 테스크(Task) 관리, 알림 기능, 이력 열람까지  
**프로젝트 관리에 필요한 핵심 기능**들을 구현한 웹 애플리케이션입니다.

---

## 📌 주요 기능
- ✅ 사용자 / 관리자 계정 분리
- ✅ 프로젝트 생성 및 구성원 배정
- ✅ 테스크(Task) 추가 및 상태 변경
- ✅ **수정 이력 확인 기능**
- ✅ **알림 퍼센트 기반 자동 알림 기능**
- ✅ 게시판(공지/업데이트) 기능

---
## 🗃️ 데이터베이스 구조 

![ERD 구조도](./img/img0.png)

- **user**: 사용자 정보 테이블 (관리자/일반 사용자 구분)
- **project**: 프로젝트 기본 정보
- **project_member**: 프로젝트별 참여 사용자 및 역할
- **task / sub_task**: 테스크 및 서브 테스크 정보
- **task_history / sub_task_history / project_history**: 수정 이력 관리
- **post**: 프로젝트별 게시판 글 (공지 포함)
- 
## 📷 페이지별 소개

### 🔐 1. 로그인 페이지 (`login.php`)
- 일반 사용자와 관리자 구분 로그인

![login](./img/img1.png)

---

### 🧾 2. 회원가입 페이지 (`register.php`)
- 사용자 정보 입력, 관리자 여부 선택 가능

![register](./img/img2.png)

---

### ❌ 3. 관리자 로그인 체크 누락 시 오류
- 관리자 계정인데 체크 안하면 경고 메시지 출력

![admin_login_error](./img/img3.png)

---

### 🧑‍💼 4. 관리자 홈 페이지 (`m_home.php`)
- 프로젝트 생성 및 게시판 관리 가능

![admin_home](./img/img4.png)

---

### 👤 5. 사용자 홈 페이지 (`home.php`)
- 본인이 속한 프로젝트 및 전체 게시글 확인 가능

![user_home](./img/img5.png)

---

### 🆕 6. 프로젝트 생성 (`m_create.php`)
- 관리자만 사용 가능, 날짜 및 멤버 배정

![create_project](./img/img6.png)

---

### 📄 7. 프로젝트 상세 보기 (`m_project.php`)
- 프로젝트 설명, 테스크 목록, 진행률, 수정 기능 등 통합 관리

![project_detail](./img/img7.png)

---

### 🔁 8. 테스크 추가 (`m_task_add.php`)
- Task별 세부 정보 설정 (설명, 날짜, 알림 비율 등)

![add_task](./img/img8.png)

---

### ✏️ 9. 프로젝트 및 테스크 수정 (`m_project_edit.php`)
- 기존 내용 변경 + 완료/삭제 여부 설정

![edit_project_task](./img/img9.png)

---

### ✅ 10. 수정 완료 화면
- 성공적으로 저장되었음을 안내

![edit_complete](./img/img10.png)

---

### 🕓 11. 수정 이력 열람 기능
- 이전 수정본과 비교 가능, 히스토리 관리

![history_version_1](./img/img11.png)
![history_version_2](./img/img12.png)

---

### 📊 12. 전체 프로젝트 분석 페이지
- 전체 성공률 및 각 프로젝트별 진행도 확인 가능

![analysis_overall](./img/img13.png)

---

### 👥 13. 프로젝트 멤버별 진행도
- 개인별 테스크 진행률, 담당 여부 확인

![analysis_member](./img/img14.png)

---

### 🔁 14. 사용자 홈에서 프로젝트 확인
- 사용자별 참여한 프로젝트 목록 확인

![user_home_project](./img/img15.png)

---

### 📊 15. 사용자용 프로젝트 상세 보기 (`project.php`)
- 테스크 상태에 따른 진행률 그래프 시각화

![user_project_detail](./img/img16.png)

---

### 📢 16. 게시판 기능 (`m_post.php`)
- 프로젝트별 공지 및 커뮤니케이션

![board_main](./img/img17.png)

---

### 📝 17. 게시판 글 작성 (`create_post.php`)
- 공지 등록, 일반 게시글 작성 기능

![board_write](./img/img18.png)

---

### 🔔 18. 전체 게시판에서 공지 확인
- 모든 프로젝트에서의 주요 공지 확인 가능

![board_notice](./img/img19.png)

---

## ⏰ 알림 퍼센트 기능
- 테스크 시작일과 종료일 기준으로 퍼센트 계산
- 사용자가 설정한 퍼센트가 되는 시점에 **자동 알림** (메인페이지 게시글 생성)

> 📌 예시: 종료일 기준 70%가 지나면 자동 알림

---

## ⚙️ 실행 방법

1. XAMPP로 Apache + MySQL 실행
2. 프로젝트 폴더를 `htdocs`에 복사
3. `http://localhost/login.php` 접속

---

## 🧱 개발 환경

- Language: PHP
- DB: MySQL
- 프론트엔드: HTML, CSS, JS 
- 서버 실행: XAMPP


## 🙋‍♂️ 제작자 정보
- 제작자: 김동하,양형준,양시헌,한제준

---

