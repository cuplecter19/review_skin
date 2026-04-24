<?php
if (!defined('_GNUBOARD_')) exit;
include_once(G5_LIB_PATH.'/thumbnail.lib.php');
$colspan = 5;
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);
add_stylesheet('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">', 0);

// ── 별점 렌더 함수 ──
if (!function_exists('render_stars_html')) {
    function render_stars_html($score) {
        $score = max(0, min(5, floatval($score)));
        $full  = floor($score);
        $half  = ($score - $full >= 0.5) ? 1 : 0;
        $empty = 5 - $full - $half;
        $html  = '<span class="rating-stars fa-stars" aria-label="' . number_format($score, 1) . '점">';
        for ($i = 0; $i < $full;  $i++) $html .= '<span class="star fa-star-wrap full"><i class="fa-regular fa-star star-outline"></i><i class="fa-solid fa-star star-fill"></i></span>';
        if ($half)                       $html .= '<span class="star fa-star-wrap half"><i class="fa-regular fa-star star-outline"></i><i class="fa-solid fa-star star-fill"></i></span>';
        for ($i = 0; $i < $empty; $i++) $html .= '<span class="star fa-star-wrap empty"><i class="fa-regular fa-star star-outline"></i><i class="fa-solid fa-star star-fill"></i></span>';
        $html .= '</span>';
        return $html;
    }
}

// ── 태그 파싱 ──
if (!function_exists('parse_tags_strict')) {
    function parse_tags_strict($raw) {
        $raw = trim((string)$raw);
        if ($raw === '') return array();
        $parts = explode(',', $raw);
        $tags  = array();
        foreach ($parts as $part) {
            $part = trim($part);
            if (mb_substr($part, 0, 1, 'UTF-8') === '#') $part = mb_substr($part, 1, null, 'UTF-8');
            $part = trim($part);
            if ($part !== '') $tags[] = $part;
        }
        return $tags;
    }
}

// ── 해시태그 전체 집계 (빈도순) ──
$tag_count  = array();
$sql_tags   = "SELECT wr_7 FROM {$write_table} WHERE wr_is_comment=0 AND wr_7 != ''";
$tag_result = sql_query($sql_tags);
while ($tr = sql_fetch_array($tag_result)) {
    foreach (parse_tags_strict($tr['wr_7']) as $t) {
        $tag_count[$t] = isset($tag_count[$t]) ? $tag_count[$t] + 1 : 1;
    }
}
arsort($tag_count);

// ── 선택된 해시태그 ──
$selected_tag = isset($_GET['stag']) ? trim((string)$_GET['stag']) : '';

// ── 기본 URL 조합 ──
$base_url = '?bo_table=' . urlencode($bo_table);
if ($sca !== '')                 $base_url .= '&amp;sca=' . urlencode($sca);
if (isset($sfl) && $sfl !== '') $base_url .= '&amp;sfl=' . urlencode($sfl);
if (isset($stx) && $stx !== '') $base_url .= '&amp;stx=' . urlencode(stripslashes($stx));

$tag_filtered    = false;
$tag_list        = array();
$tag_page        = 1;
$tag_total       = 0;
$tag_per_page    = $board['bo_page_rows'] > 0 ? (int)$board['bo_page_rows'] : 10;
$tag_total_pages = 1;
$tag_page_str    = '';

if ($selected_tag !== '') {
    $tag_filtered = true;
    $tag_page     = isset($_GET['tag_page']) ? max(1, (int)$_GET['tag_page']) : 1;

    $safe_tag  = sql_escape_string($selected_tag);
    $sca_where = ($sca !== '') ? " AND ca_name='" . sql_escape_string($sca) . "'" : '';
    $sql_all   = "SELECT * FROM {$write_table}
                  WHERE wr_is_comment=0
                  {$sca_where}
                  AND wr_7 != ''
                  ORDER BY wr_datetime DESC";
    $res_all   = sql_query($sql_all);

    $all_matched = array();
    while ($row_all = sql_fetch_array($res_all)) {
        if (in_array($selected_tag, parse_tags_strict($row_all['wr_7']))) {
            $all_matched[] = $row_all;
        }
    }

    $tag_total       = count($all_matched);
    $tag_total_pages = max(1, ceil($tag_total / $tag_per_page));
    $tag_page        = min($tag_page, $tag_total_pages);
    $offset          = ($tag_page - 1) * $tag_per_page;
    $tag_list        = array_slice($all_matched, $offset, $tag_per_page);

    foreach ($tag_list as $k => $row) {
        $tag_list[$k]['href'] = G5_BBS_URL . '/board.php?bo_table=' . $bo_table
            . '&amp;wr_id=' . (int)$row['wr_id'];
    }

    $tag_url_base = $base_url . '&amp;stag=' . urlencode($selected_tag);
    $pg_html      = '';
    if ($tag_total_pages > 1) {
        $block       = 10;
        $block_start = floor(($tag_page - 1) / $block) * $block + 1;
        $block_end   = min($block_start + $block - 1, $tag_total_pages);

        $pg_html .= '<div class="pg_wrap"><nav class="pg"><ul>';
        if ($block_start > 1)
            $pg_html .= '<li class="pg_page pg_prev_more"><a href="' . $tag_url_base . '&amp;tag_page=' . ($block_start - 1) . '">&laquo;</a></li>';
        if ($tag_page > 1)
            $pg_html .= '<li class="pg_page pg_prev"><a href="' . $tag_url_base . '&amp;tag_page=' . ($tag_page - 1) . '">&lsaquo;</a></li>';
        for ($pn = $block_start; $pn <= $block_end; $pn++) {
            if ($pn == $tag_page)
                $pg_html .= '<li class="pg_page pg_current"><strong>' . $pn . '</strong></li>';
            else
                $pg_html .= '<li class="pg_page"><a href="' . $tag_url_base . '&amp;tag_page=' . $pn . '">' . $pn . '</a></li>';
        }
        if ($tag_page < $tag_total_pages)
            $pg_html .= '<li class="pg_page pg_next"><a href="' . $tag_url_base . '&amp;tag_page=' . ($tag_page + 1) . '">&rsaquo;</a></li>';
        if ($block_end < $tag_total_pages)
            $pg_html .= '<li class="pg_page pg_next_more"><a href="' . $tag_url_base . '&amp;tag_page=' . ($block_end + 1) . '">&raquo;</a></li>';
        $pg_html .= '</ul></nav></div>';
    }
    $tag_page_str = $pg_html;
    $display_list = $tag_list;
} else {
    $display_list = $list;
}

$category_option = get_category_option($bo_table, $sca);
?>

<style>
.review-list-box {
    background: #2a2a2a;
    border-radius: 12px;
    padding: 20px 20px 8px;
    margin-bottom: 16px;
    box-sizing: border-box;
}
.review-list-box #hashtag-filter {
    padding-bottom: 14px;
    border-bottom: 1px solid rgba(255,255,255,.08);
    margin-bottom: 10px;
}
.review-num {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}
.num-label {
    font-size: 16px; font-weight: 600;
    color: #fff; opacity: .85;
    transition: opacity .2s;
}
.del-check {
    display: none;
    width: 20px; height: 20px;
    cursor: pointer; accent-color: #e05555;
}
.del-mode .num-label { display: none; }
.del-mode .del-check  { display: block; }
.del-mode .review-item:hover { background: #3a2a2a; }
.del-mode .review-item.is-checked {
    background: #3d2323;
    outline: 1px solid rgba(220,80,80,.45);
}
#btn_del_cancel {
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.2);
    color: #ccc;
}
#btn_del_cancel:hover { background: rgba(255,255,255,.18); color: #fff; }
</style>

<div <?php if ($board['bo_table_width'] > 0) { ?>style="max-width:<?php echo $board['bo_table_width'] ?><?php echo $board['bo_table_width'] > 100 ? "px" : "%" ?>;margin:0 auto;"<?php } ?>>
<hr class="padding">
<?php if ($board['bo_content_head']) { ?>
    <div class="board-notice"><?php echo stripslashes($board['bo_content_head']); ?></div>
    <hr class="padding" />
<?php } ?>

<div class="board-skin-basic">
<nav id="bo_cate">
  <ul id="bo_cate_ul">
    <?php $on_all = ($sca === '' && (string)(isset($sfl) ? $sfl : '') === ''); ?>
    <li><a href="?bo_table=<?php echo $bo_table ?>" class="ui-btn<?php echo $on_all ? ' point' : '' ?>">전체</a></li>
    <?php if ($is_category):
      foreach (array_filter(array_map('trim', explode('|', (string)(isset($board['bo_category_list']) ? $board['bo_category_list'] : '')))) as $cat):
        $is_on = ($cat === $sca); ?>
        <li>
          <a href="?bo_table=<?php echo $bo_table ?>&amp;sca=<?php echo urlencode($cat) ?>"
             class="ui-btn<?php echo $is_on ? ' point' : '' ?>">
            <?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
          </a>
        </li>
    <?php endforeach; endif; ?>
  </ul>
</nav>

    <form name="fboardlist" id="fboardlist" action="./board_list_update.php" onsubmit="return fboardlist_submit(this);" method="post">
    <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
    <input type="hidden" name="sfl"      value="<?php echo $sfl ?>">
    <input type="hidden" name="stx"      value="<?php echo $stx ?>">
    <input type="hidden" name="spt"      value="<?php echo $spt ?>">
    <input type="hidden" name="sca"      value="<?php echo $sca ?>">
    <input type="hidden" name="sst"      value="<?php echo $sst ?>">
    <input type="hidden" name="sod"      value="<?php echo $sod ?>">
    <input type="hidden" name="page"     value="<?php echo $page ?>">

    <!-- 해시태그 필터 + 글목록 통합 박스 -->
    <div class="review-list-box">

        <?php if (!empty($tag_count)) { ?>
        <div id="hashtag-filter">
            <?php foreach ($tag_count as $tag_name => $cnt) {
                $is_active  = ($selected_tag === $tag_name);
                $tag_url    = $base_url . '&amp;stag=' . urlencode($tag_name);
                $toggle_url = $is_active ? $base_url : $tag_url;
            ?>
            <button type="button" class="<?php echo $is_active ? 'active' : '' ?>"
                    onclick="location.href='<?php echo $toggle_url ?>'">
                #<?php echo htmlspecialchars($tag_name, ENT_QUOTES, 'UTF-8') ?>
                <span class="tag-cnt"><?php echo $cnt ?></span>
            </button>
            <?php } ?>
        </div>
        <?php } ?>

        <ul class="review-list" id="review-list">
        <?php if (count($display_list)) { ?>
        <?php for ($i = 0; $i < count($display_list); $i++) {
            $row = $display_list[$i];

            if ($tag_filtered) {
                $row_num  = ($tag_page - 1) * $tag_per_page + $i + 1;
                $row_href = './board.php?bo_table=' . $bo_table . '&amp;wr_id=' . (int)$row['wr_id'];
            } else {
                $row_num  = $i + 1 + ($page - 1) * $board['bo_page_rows'];
                $row_href = $row['href'];
            }

            $thumb_url = '';
            if (!empty($row['wr_1'])) {
                $thumb_url = $row['wr_1'];
            } else {
                $thumb = get_list_thumbnail($bo_table, $row['wr_id'], 200, 130, false, true);
                $thumb_url = (!empty($thumb) && is_array($thumb) && isset($thumb['src'])) ? $thumb['src'] : '';
            }

            $rating    = isset($row['wr_5']) ? floatval($row['wr_5']) : 0;
            $stars     = render_stars_html($rating);
            $author    = isset($row['wr_2']) ? trim($row['wr_2']) : '';
            $synopsis  = isset($row['wr_6']) ? trim($row['wr_6']) : '';
            $campaign  = isset($row['wr_9']) ? trim($row['wr_9']) : '';
            $user_tags = parse_tags_strict(isset($row['wr_7']) ? $row['wr_7'] : '');

            $auto_tags = array();
            if      ($rating >= 4.5) $auto_tags[] = '평점 4.5 이상';
            elseif  ($rating >= 4.0) $auto_tags[] = '평점 4.0 이상';

            $write_date = isset($row['wr_datetime']) ? date('Y.m.d', strtotime($row['wr_datetime'])) : '';
        ?>
          <li class="review-item" data-wr-id="<?php echo (int)$row['wr_id'] ?>">

            <div class="review-num">
                <span class="num-label"><?php echo $row_num ?></span>
                <input type="checkbox" name="chk_wr_id[]"
                       value="<?php echo (int)$row['wr_id'] ?>"
                       class="del-check"
                       id="chk_<?php echo (int)$row['wr_id'] ?>">
            </div>

            <div class="thumb-wrap">
                <?php if ($thumb_url) { ?>
                <img src="<?php echo htmlspecialchars($thumb_url, ENT_QUOTES, 'UTF-8') ?>"
                     alt="<?php echo htmlspecialchars($row['wr_subject'], ENT_QUOTES, 'UTF-8') ?>">
                <?php } else { ?>
                <div class="review-thumb" style="background:#222;"></div>
                <?php } ?>
                <div class="thumb-stars"><?php echo $stars ?></div>
            </div>

            <div class="review-info">
                <div class="review-title">
                    <a href="<?php echo $row_href ?>"><?php echo htmlspecialchars($row['wr_subject'], ENT_QUOTES, 'UTF-8') ?></a>
                </div>
                <?php if ($author !== '') { ?>
                <div class="review-author"><?php echo htmlspecialchars($author, ENT_QUOTES, 'UTF-8') ?></div>
                <?php } ?>
                <?php if ($synopsis !== '') { ?>
                <div class="review-synopsis"><?php echo htmlspecialchars($synopsis, ENT_QUOTES, 'UTF-8') ?></div>
                <?php } ?>
                <div class="tag-row" id="tag-row-<?php echo $i ?>">
                    <?php if ($campaign !== '') { ?>
                    <span class="campaign-label"><?php echo htmlspecialchars($campaign, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php } ?>
                    <?php foreach ($user_tags as $tg) { ?>
                    <span class="tag-item">#<?php echo htmlspecialchars($tg, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php } ?>
                    <?php foreach ($auto_tags as $at) { ?>
                    <span class="tag-item auto-tag">#<?php echo htmlspecialchars($at, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php } ?>
                </div>
            </div>

            <div class="review-date"><?php echo $write_date ?></div>

            <a class="card-link" href="<?php echo $row_href ?>"
               aria-label="<?php echo htmlspecialchars($row['wr_subject'], ENT_QUOTES, 'UTF-8') ?>"></a>
          </li>
        <?php } ?>
        <?php } else { ?>
          <li class="review-item review-item--empty">
            <?php echo $selected_tag !== '' ? '선택한 해시태그의 게시물이 없습니다.' : '게시물이 없습니다.' ?>
          </li>
        <?php } ?>
        </ul>

    </div><!-- /review-list-box -->

    <!-- 버튼 영역 -->
    <?php if ($write_href || $admin_href || $is_admin) { ?>
    <div class="bo_fx txt-right">
        <?php if ($is_admin) { ?>
        <button type="button" id="btn_del_enter"  class="ui-btn admin" onclick="enterDeleteMode()">삭제</button>
        <!--
            ★ 핵심 수정:
               board_list_update.php 는 $_POST['btn_submit'] === '선택삭제' 를 확인함.
               name="btn_submit", value="선택삭제" 가 반드시 있어야 POST로 전송됨.
        -->
        <button type="submit"
                id="btn_sel_del"
                name="btn_submit"
                value="선택삭제"
                class="ui-btn admin"
                style="display:none;">선택 삭제</button>
        <button type="button" id="btn_del_cancel" class="ui-btn" style="display:none;" onclick="exitDeleteMode()">취소</button>
        <?php } ?>
        <?php if ($write_href) { ?><a href="<?php echo $write_href ?>" class="ui-btn point">글쓰기</a><?php } ?>
        <?php if ($admin_href) { ?><a href="<?php echo $admin_href ?>" class="ui-btn admin" target="_blank">관리자</a><?php } ?>
    </div>
    <?php } ?>

    </form>

    <?php if ($tag_filtered) { echo $tag_page_str; } else { echo $write_pages; } ?>

    <fieldset id="bo_sch" class="txt-center">
        <legend>게시물 검색</legend>
        <form name="fsearch" method="get">
        <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
        <input type="hidden" name="sca"      value="<?php echo $sca ?>">
        <input type="hidden" name="sop"      value="and">
        <select name="sfl" id="sfl">
            <option value="wr_subject"<?php echo get_selected($sfl, 'wr_subject', true); ?>>제목</option>
            <option value="wr_content"<?php echo get_selected($sfl, 'wr_content'); ?>>내용</option>
            <option value="wr_subject||wr_content"<?php echo get_selected($sfl, 'wr_subject||wr_content'); ?>>제목+내용</option>
            <option value="wr_7"<?php echo get_selected($sfl, 'wr_7'); ?>>해시태그</option>
        </select>
        <input type="text" name="stx" value="<?php echo stripslashes($stx) ?>" required id="stx" class="frm_input required" size="15" maxlength="20">
        <button type="submit" class="ui-btn point ico search default">검색</button>
        </form>
    </fieldset>
</div>
</div>

<script>
function enterDeleteMode() {
    document.getElementById('review-list').classList.add('del-mode');
    document.getElementById('btn_del_enter').style.display  = 'none';
    document.getElementById('btn_sel_del').style.display    = '';
    document.getElementById('btn_del_cancel').style.display = '';
    var cards = document.querySelectorAll('#review-list .card-link');
    for (var c = 0; c < cards.length; c++) {
        cards[c].setAttribute('data-href', cards[c].getAttribute('href'));
        cards[c].removeAttribute('href');
        cards[c].style.cursor = 'pointer';
        cards[c].addEventListener('click', onCardClickInDelMode);
    }
}
function exitDeleteMode() {
    var list = document.getElementById('review-list');
    list.classList.remove('del-mode');
    document.getElementById('btn_del_enter').style.display  = '';
    document.getElementById('btn_sel_del').style.display    = 'none';
    document.getElementById('btn_del_cancel').style.display = 'none';
    var checks = list.querySelectorAll('.del-check');
    for (var i = 0; i < checks.length; i++) checks[i].checked = false;
    var items = list.querySelectorAll('.review-item');
    for (var j = 0; j < items.length; j++) items[j].classList.remove('is-checked');
    var cards = document.querySelectorAll('#review-list .card-link');
    for (var k = 0; k < cards.length; k++) {
        cards[k].setAttribute('href', cards[k].getAttribute('data-href'));
        cards[k].removeAttribute('data-href');
        cards[k].removeEventListener('click', onCardClickInDelMode);
    }
}
function onCardClickInDelMode(e) {
    e.preventDefault();
    var li    = this.closest('.review-item');
    var check = li ? li.querySelector('.del-check') : null;
    if (!check) return;
    check.checked = !check.checked;
    li.classList.toggle('is-checked', check.checked);
}
document.addEventListener('DOMContentLoaded', function () {
    var checks = document.querySelectorAll('.del-check');
    for (var i = 0; i < checks.length; i++) {
        checks[i].addEventListener('change', function () {
            var li = this.closest('.review-item');
            if (li) li.classList.toggle('is-checked', this.checked);
        });
    }
});

/* 폼 제출 유효성 검사 */
function fboardlist_submit(f) {
    /* 선택삭제 버튼이 눌린 경우에만 체크 수 검증 */
    var pressed = document.activeElement ? document.activeElement.value : '';
    if (pressed === '선택삭제') {
        var chk_count = 0;
        for (var i = 0; i < f.length; i++) {
            if (f.elements[i].name === 'chk_wr_id[]' && f.elements[i].checked) chk_count++;
        }
        if (chk_count === 0) { alert("삭제할 게시물을 하나 이상 선택하세요."); return false; }
        if (!confirm("선택한 게시물을 정말 삭제하시겠습니까?\n\n한번 삭제한 자료는 복구할 수 없습니다.")) return false;
    }
    if (document.pressed == "선택복사") { select_copy("copy"); return false; }
    if (document.pressed == "선택이동") { select_copy("move"); return false; }
    return true;
}
function select_copy(sw) {
    window.open("", "move", "left=50,top=50,width=500,height=550,scrollbars=1");
    var f = document.fboardlist;
    f.sw.value = sw; f.target = "move"; f.action = "./move.php"; f.submit();
}

/* 해시태그 행 넘침 처리 */
document.addEventListener('DOMContentLoaded', function () {
    requestAnimationFrame(function () {
        var rows = document.querySelectorAll('.tag-row');
        for (var r = 0; r < rows.length; r++) {
            (function (row) {
                var tags = row.querySelectorAll('.tag-item, .campaign-label');
                if (!tags.length) return;
                row.style.overflow = 'visible';
                var rowRect = row.getBoundingClientRect();
                for (var i = 0; i < tags.length; i++) {
                    if (tags[i].getBoundingClientRect().right > rowRect.right + 2) {
                        tags[i].style.display = 'none';
                    }
                }
                row.style.overflow = 'hidden';
            })(rows[r]);
        }
    });
});
</script>
<!-- } 게시판 목록 끝 -->
