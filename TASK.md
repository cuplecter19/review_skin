# 작업지시서 — review_skin OTT 리뷰 게시판 개편

## 환경
- PHP 5.6 호환 (short_open_tag 사용 가능, `<?=` 사용 가능)
- 그누보드 5 + 아보카도 퍼스널
- Font Awesome 6 (CDN), Pretendard (CDN)

## 필드 매핑 (그누보드 wr_* 커스텀 필드)
| 필드 | 용도 |
|------|------|
| wr_1 | 타이틀 이미지 URL or 파일명 |
| wr_2 | 작가명 |
| wr_3 | 진행 상황 (감상 중 / 감상 완료 / 중도 하차) |
| wr_4 | 감상 시작일 |
| wr_5 | 별점 (0~5, 0.5 단위) |
| wr_6 | 줄거리 |
| wr_7 | 해시태그 (쉼표 구분) |
| wr_8 | 감상 종료일 |
| wr_9 | 캠페인명 |

## 작업 항목
1. list.skin.php — 해시태그 필터, 목록 레이아웃, 별점·캠페인·자동 해시태그
2. view.skin.php — 포스터 헤더, 플로팅 위젯, 캠페인 관련 글목록, 구조 개편
3. view_comment.skin.php — 이모티콘 버튼 추가
4. write.skin.php — 캠페인 UI, 작가명 필드, 해시태그 파싱 규칙 수정, 이모티콘 버튼
5. write_update.skin.php — sanitize_hashtags() 적용
6. style.css — 전체 디자인 업데이트
