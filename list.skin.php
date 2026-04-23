<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');
$colspan = 5;
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);
add_stylesheet('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">', 0);

// ── 별점 렌더 함수 (중복 방지) ──
if (!function_exists('render_stars_html')) {
    function render_stars_html($score) {
        $score = max(0, min(5, floatval($score)));
        $full  = floor($score);
        $half  = ($score - $full >= 0.5) ? 1 : 0;
        $empty = 5 - $full - $half;
        $html  = '<span class="rating-stars fa-stars" aria-label="' . number_format($score, 1) . '점">';
        for ($i = 0; $i < $full; $i++) {
            $html .= '<span class="star fa-star-wrap full"><i class="fa-regular fa-star star-outline"></i><i class="fa-solid fa-star star-fill"></i></span>';
        }
        if ($half) {
            $html .= '<span class="star fa-star-wrap half"><i class="fa-regular fa-star star-outline"></i><i class="fa-solid fa-star star-fill"></i></span>';
        }
        for ($i = 0; $i < $empty; $i++) {
            $html .= '<span class="star fa-star-wrap empty"><i class="fa-regular fa-star star-outline"></i><i class="fa-solid fa-star star-fill"></i></span>';
        }
        $html .= '</span>';
        return $html;
    }
}

// ── 태그 파싱 (쉼표 구분, 단어 내 띄어쓰기 허용) ──
if (!function_exists('parse_tags_strict')) {
    function parse_tags_strict($raw) {
        $raw = trim((string)$raw);
        if ($raw === '') return array();
        $parts = explode(',', $raw);
        $tags  = array();
        foreach ($parts as $part) {
            $part = trim($part);
            if (mb_substr($part, 0, 1, 'UTF-8') === '#') {
                $part = mb_substr($part, 1, null, 'UTF-8');
            }
            $part = trim($part);
            if ($part !== '') $tags[] = $part;
        }
        return $tags;
    }
}

// ── 해시태그 전체 집계 (빈도순) ──
$tag_count = array();
$sql_tags  = "SELECT wr_7 FROM {$write_table} WHERE wr_is_comment=0 AND wr_7 != ''";
$tag_result = sql_query($sql_tags);
while ($tr = sql_fetch_array($tag_result)) {
    $tags_raw = parse_tags_strict($tr['wr_7']);
    foreach ($tags_raw as $t) {
        $tag_count[$t] = isset($tag_count[$t]) ? $tag_count[$t] + 1 : 1;
    }
}
arsort($tag_count);

// ── 선택된 해시태그 필터 ──
$selected_tag = isset($_GET['stag']) ? trim((string)$_GET['stag']) : '';

// ── 서버사이드 필터링 ──
if ($selected_tag !== '') {
    $filtered = array();
    foreach ($list as $row) {
        $row_tags = parse_tags_strict(isset($row['wr_7']) ? $row['wr_7'] : '');
        if (in_array($selected_tag, $row_tags)) {
            $filtered[] = $row;
        }
    }
    $list = $filtered;
}

$category_option = get_category_option($bo_table, $sca);

// ── 기본 URL 조합 (stag 없이) ──
$base_url = '?bo_table=' . urlencode($bo_table);
if ($sca !== '') $base_url .= '&amp;sca=' . urlencode($sca);
if (isset($sfl) && $sfl !== '') $base_url .= '&amp;sfl=' . urlencode($sfl);
if (isset($stx) && $stx !== '') $base_url .= '&amp;stx=' . urlencode(stripslashes($stx));
?>
<div <?php if ($board['bo_table_width'] > 0) { ?>style="max-width:<?php echo $board['bo_table_width'] ?><?php echo $board['bo_table_width'] > 100 ? "px" : "%" ?>;margin:0 auto;"<?php } ?>>
<hr class="padding">
<?php if ($board['bo_content_head']) { ?>
    <div class="board-notice">
        <?php echo stripslashes($board['bo_content_head']); ?>
    </div><hr class="padding" />
<?php } ?>

<div class="board-skin-basic">
<nav id="bo_cate">
  <ul id="bo_cate_ul">
    <?php $on_all = ($sca === '' && (string)(isset($sfl) ? $sfl : '') === ''); ?>
    <li>
      <a href="?bo_table=<?php echo $bo_table ?>" class="ui-btn<?php echo $on_all ? ' point' : '' ?>">전체</a>
    </li>
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

<?php if (!empty($tag_count)) { ?>
<div id="hashtag-filter">
    <?php foreach ($tag_count as $tag_name => $cnt) {
        $is_active = ($selected_tag === $tag_name);
        $tag_url   = $base_url . '&amp;stag=' . urlencode($tag_name);
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

    <form name="fboardlist" id="fboardlist" action="./board_list_update.php" onsubmit="return fboardlist_submit(this);" method="post">
    <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
    <input type="hidden" name="stx" value="<?php echo $stx ?>">
    <input type="hidden" name="spt" value="<?php echo $spt ?>">
    <input type="hidden" name="sca" value="<?php echo $sca ?>">
    <input type="hidden" name="sst" value="<?php echo $sst ?>">
    <input type="hidden" name="sod" value="<?php echo $sod ?>">
    <input type="hidden" name="page" value="<?php echo $page ?>">
    <input type="hidden" name="sw" value="">

<ul class="review-list">
<?php if (count($list)) { ?>
<?php for ($i = 0; $i < count($list); $i++) {
    $row = $list[$i];

    // 썸네일 URL
    $thumb_url = '';
    if (!empty($row['wr_1'])) {
        $thumb_url = $row['wr_1'];
    } else {
        $thumb = get_list_thumbnail($bo_table, $row['wr_id'], 200, 130, false, true);
        $thumb_url = (!empty($thumb) && is_array($thumb) && isset($thumb['src'])) ? $thumb['src'] : '';
    }

    $rating  = isset($row['wr_5']) ? floatval($row['wr_5']) : 0;
    $stars   = render_stars_html($rating);
    $author  = isset($row['wr_2']) ? trim($row['wr_2']) : '';
    $synopsis = isset($row['wr_6']) ? trim($row['wr_6']) : '';
    $campaign = isset($row['wr_9']) ? trim($row['wr_9']) : '';
    $user_tags = parse_tags_strict(isset($row['wr_7']) ? $row['wr_7'] : '');

    // 자동 해시태그
    $auto_tags = array();
    if ($rating >= 4.5) {
        $auto_tags[] = '평점 4.5 이상';
    } elseif ($rating >= 4.0) {
        $auto_tags[] = '평점 4.0 이상';
    }

    $status = isset($row['wr_3']) ? $row['wr_3'] : '';
    $write_date = isset($row['wr_datetime']) ? date('Y.m.d', strtotime($row['wr_datetime'])) : '';
?>
  <li class="review-item">
    <div class="thumb-wrap">
        <?php if ($thumb_url) { ?>
        <img src="<?php echo htmlspecialchars($thumb_url, ENT_QUOTES, 'UTF-8') ?>" alt="<?php echo htmlspecialchars($row['wr_subject'], ENT_QUOTES, 'UTF-8') ?>">
        <?php } else { ?>
        <div class="review-thumb" style="background:#222;"></div>
        <?php } ?>
        <div class="thumb-stars"><?php echo $stars ?></div>
    </div>

    <div class="review-info">
        <div class="review-title">
            <a href="<?php echo $row['href'] ?>"><?php echo htmlspecialchars($row['wr_subject'], ENT_QUOTES, 'UTF-8') ?></a>
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

    <a class="card-link" href="<?php echo $row['href'] ?>" aria-label="<?php echo htmlspecialchars($row['wr_subject'], ENT_QUOTES, 'UTF-8') ?>"></a>
  </li>
<?php } ?>
<?php } else { ?>
  <li class="review-item review-item--empty">
    <?php echo $selected_tag !== '' ? '선택한 해시태그의 게시물이 없습니다.' : '게시물이 없습니다.' ?>
  </li>
<?php } ?>
</ul>

    <?php if ($is_checkbox || $write_href || $admin_href) { ?>
    <div class="bo_fx txt-right">
        <?php if ($is_checkbox) { ?>
        <input type="submit" name="sw_del" value="선택삭제" onclick="document.pressed=this.value" class="ui-btn admin">
        <?php } ?>
        <?php if ($write_href) { ?><a href="<?php echo $write_href ?>" class="ui-btn point">글쓰기</a><?php } ?>
        <?php if ($admin_href) { ?><a href="<?php echo $admin_href ?>" class="ui-btn admin" target="_blank">관리자</a><?php } ?>
    </div>
    <?php } ?>

    </form>
    <?php echo $write_pages; ?>

    <fieldset id="bo_sch" class="txt-center">
        <legend>게시물 검색</legend>
        <form name="fsearch" method="get">
        <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
        <input type="hidden" name="sca" value="<?php echo $sca ?>">
        <input type="hidden" name="sop" value="and">
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

<?php if ($is_checkbox) { ?>
<script>
function all_checked(sw) {
    var f = document.fboardlist;
    for (var i = 0; i < f.length; i++) {
        if (f.elements[i].name == "chk_wr_id[]")
            f.elements[i].checked = sw;
    }
}

function fboardlist_submit(f) {
    var chk_count = 0;
    for (var i = 0; i < f.length; i++) {
        if (f.elements[i].name == "chk_wr_id[]" && f.elements[i].checked)
            chk_count++;
    }
    if (!chk_count) {
        alert(document.pressed + "할 게시물을 하나 이상 선택하세요.");
        return false;
    }
    if (document.pressed == "선택복사") { select_copy("copy"); return; }
    if (document.pressed == "선택이동") { select_copy("move"); return; }
    if (document.pressed == "선택삭제") {
        if (!confirm("선택한 게시물을 정말 삭제하시겠습니까?\n\n한번 삭제한 자료는 복구할 수 없습니다\n\n답변글이 있는 게시글을 선택하신 경우\n답변글도 선택하셔야 게시글이 삭제됩니다."))
            return false;
        f.removeAttribute("target");
        f.action = "./board_list_update.php";
    }
    return true;
}

function select_copy(sw) {
    var f = document.fboardlist;
    var sub_win = window.open("", "move", "left=50, top=50, width=500, height=550, scrollbars=1");
    f.sw.value = sw;
    f.target = "move";
    f.action = "./move.php";
    f.submit();
}
</script>
<?php } ?>
<script>
// 해시태그 행 넘침 처리: 넘치는 태그 숨김
(function() {
    var rows = document.querySelectorAll('.tag-row');
    for (var r = 0; r < rows.length; r++) {
        (function(row) {
            var tags = row.querySelectorAll('.tag-item, .campaign-label');
            if (!tags.length) return;
            var rowTop = row.getBoundingClientRect().top;
            for (var i = tags.length - 1; i >= 0; i--) {
                var t = tags[i];
                if (t.getBoundingClientRect().top > rowTop + 2) {
                    t.classList.add('hidden');
                } else {
                    break;
                }
            }
        })(rows[r]);
    }
})();
</script>
<!-- } 게시판 목록 끝 -->