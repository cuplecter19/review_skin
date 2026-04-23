<!-- view.php -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<?php

add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);

// ── 별점 렌더 함수 (중복 방지) ──
if (!function_exists('render_stars')) {
    function render_stars($score) {
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

// ── 블러 치환 ──
$content = $view['content'];
$content = str_replace('(블러시작)', '<span class="blurtext">', $content);
$content = str_replace('(블러끝)', '</span>', $content);

// ── 표지 URL ──
$bg_url = isset($view['wr_1']) ? $view['wr_1'] : '';
if (strpos($bg_url, 'http://') === 0 || strpos($bg_url, 'https://') === 0) {
    $poster_url = $bg_url;
    $poster_style = "--view-bg: url('" . addslashes($bg_url) . "');";
} elseif ($bg_url !== '') {
    $poster_url = G5_DATA_URL . '/file/' . $bo_table . '/' . $bg_url;
    $poster_style = "--view-bg: url('" . addslashes($poster_url) . "');";
} else {
    $poster_url   = '';
    $poster_style = '';
}

// ── 메타 정보 ──
$rating   = isset($view['wr_5']) ? floatval($view['wr_5']) : 0;
$author   = isset($view['wr_2']) ? trim($view['wr_2']) : '';
$synopsis = isset($view['wr_6']) ? trim($view['wr_6']) : '';
$campaign = isset($view['wr_9']) ? trim($view['wr_9']) : '';
$period_s = isset($view['wr_4']) ? trim($view['wr_4']) : '';
$period_e = isset($view['wr_8']) ? trim($view['wr_8']) : '';

$user_tags = parse_tags_strict(isset($view['wr_7']) ? $view['wr_7'] : '');
$auto_tags = array();
if ($rating >= 4.5) {
    $auto_tags[] = '평점 4.5 이상';
} elseif ($rating >= 4.0) {
    $auto_tags[] = '평점 4.0 이상';
}

// ── 보호글/멤버공개 체크 ──
$is_viewer = true;
$p_url     = '';
if (isset($view['wr_protect']) && $view['wr_protect'] !== '') {
    if (get_session("ss_secret_{$bo_table}_{$view['wr_num']}")
        || ($view['mb_id'] && $view['mb_id'] == $member['mb_id'])
        || $is_admin) {
        $is_viewer = true;
    } else {
        $is_viewer = false;
        $p_url = './password.php?w=p&amp;bo_table=' . $bo_table . '&amp;wr_id=' . $view['wr_id'] . $qstr;
    }
} elseif (isset($view['wr_secret']) && $view['wr_secret'] == '1') {
    if ($board['bo_read_level'] < $member['mb_level'] && $is_member) {
        $is_viewer = true;
    } else {
        $is_viewer = false;
        $p_url = './login.php';
    }
}
if (!$is_viewer && $p_url !== '') {
    if ($p_url === './login.php') {
        alert('멤버공개 글입니다. 로그인 후 이용해주세요.', $p_url);
    } else {
        goto_url($p_url);
    }
}

// ── 캠페인 관련 글 목록 ──
$campaign_list = array();
if ($campaign !== '') {
    $safe_camp = addslashes($campaign);
    $sql_camp  = "SELECT wr_id, wr_subject, wr_1, wr_5, wr_datetime
                  FROM {$write_table}
                  WHERE wr_is_comment=0
                    AND wr_9='{$safe_camp}'
                    AND wr_id != " . (int)$view['wr_id'] . "
                  ORDER BY wr_datetime DESC";
    $camp_result = sql_query($sql_camp);
    while ($crow = sql_fetch_array($camp_result)) {
        $campaign_list[] = $crow;
    }
}
?>
<div id="bo_v" class="view-fullbleed" style="<?php echo $poster_style ?>">

<!-- 플로팅 위젯 -->
<div class="view-float-widget">
    <a href="<?php echo $list_href ?>" title="목록으로"><i class="fa-solid fa-arrow-left"></i></a>
    <?php if ($update_href) { ?>
    <a href="<?php echo $update_href ?>" title="수정"><i class="fa-solid fa-pen"></i></a>
    <?php } ?>
    <?php if ($delete_href) { ?>
    <button type="button" title="삭제" onclick="if(del('<?php echo $delete_href ?>')){}"><i class="fa-solid fa-trash"></i></button>
    <?php } ?>
</div>

  <div class="content-wrap">

    <!-- 포스터 헤더 -->
    <div class="view-header">
      <div class="poster-card">
        <?php if ($poster_url) { ?>
        <img src="<?php echo htmlspecialchars($poster_url, ENT_QUOTES, 'UTF-8') ?>" alt="<?php echo htmlspecialchars($view['subject'], ENT_QUOTES, 'UTF-8') ?>" class="poster-img">
        <?php } else { ?>
        <div class="poster-img" style="background:#222;min-height:280px;"></div>
        <?php } ?>
        <div class="poster-bottom">
            <?php echo render_stars($rating) ?>
            <button class="play-btn" onclick="document.getElementById('view-content-anchor').scrollIntoView({behavior:'smooth'});" title="본문으로 이동">
                <i class="fa-solid fa-circle-play"></i>
            </button>
        </div>
      </div>

      <div class="view-meta">
        <?php if ($campaign !== '') { ?>
        <div class="view-campaign-label"><?php echo htmlspecialchars($campaign, ENT_QUOTES, 'UTF-8') ?></div>
        <?php } ?>

        <h1 class="title"><?php echo htmlspecialchars($view['subject'], ENT_QUOTES, 'UTF-8') ?></h1>

        <?php if ($period_s !== '' || $period_e !== '') { ?>
        <div class="view-period">
            <?php echo htmlspecialchars($period_s, ENT_QUOTES, 'UTF-8') ?>
            <?php if ($period_e !== '') { ?>&nbsp;~&nbsp;<?php echo htmlspecialchars($period_e, ENT_QUOTES, 'UTF-8') ?><?php } ?>
        </div>
        <?php } ?>

        <?php if ($author !== '') { ?>
        <div class="view-author"><?php echo htmlspecialchars($author, ENT_QUOTES, 'UTF-8') ?></div>
        <?php } ?>

        <?php if ($synopsis !== '') { ?>
        <div class="view-synopsis"><?php echo nl2br(htmlspecialchars($synopsis, ENT_QUOTES, 'UTF-8')) ?></div>
        <?php } ?>

        <div class="view-tags">
            <?php foreach ($user_tags as $tg) { ?>
            <span class="tag-item">#<?php echo htmlspecialchars($tg, ENT_QUOTES, 'UTF-8') ?></span>
            <?php } ?>
            <?php foreach ($auto_tags as $at) { ?>
            <span class="tag-item auto-tag">#<?php echo htmlspecialchars($at, ENT_QUOTES, 'UTF-8') ?></span>
            <?php } ?>
        </div>
      </div>
    </div>
    <!-- /포스터 헤더 -->

    <div class="line-view" aria-hidden="true"></div>
    <div id="view-content-anchor" class="view-content">
        <?php echo $content ?>
    </div>
    <div class="line-view" aria-hidden="true"></div>

    <?php if (!empty($campaign_list)) { ?>
    <div class="campaign-posts-wrap">
        <h4><i class="fa-solid fa-list"></i> 같은 캠페인: <?php echo htmlspecialchars($campaign, ENT_QUOTES, 'UTF-8') ?></h4>
        <div class="campaign-posts-list" id="campaignPostsList">
            <?php foreach ($campaign_list as $idx => $crow) {
                $cp_thumb = '';
                if (!empty($crow['wr_1'])) {
                    if (strpos($crow['wr_1'], 'http://') === 0 || strpos($crow['wr_1'], 'https://') === 0) {
                        $cp_thumb = $crow['wr_1'];
                    } else {
                        $cp_thumb = G5_DATA_URL . '/file/' . $bo_table . '/' . $crow['wr_1'];
                    }
                }
                $cp_date = date('Y.m.d', strtotime($crow['wr_datetime']));
                $cp_url  = G5_BBS_URL . '/board.php?bo_table=' . $bo_table . '&amp;wr_id=' . (int)$crow['wr_id'];
            ?>
            <a href="<?php echo $cp_url ?>" class="campaign-post-item" data-index="<?php echo $idx ?>">
                <div class="cp-thumb" <?php if ($cp_thumb) { ?>style="background-image:url('<?php echo htmlspecialchars($cp_thumb, ENT_QUOTES, 'UTF-8') ?>')"<?php } ?>></div>
                <div>
                    <div class="cp-title"><?php echo htmlspecialchars($crow['wr_subject'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="cp-date"><?php echo $cp_date ?></div>
                </div>
            </a>
            <?php } ?>
        </div>
    </div>
    <?php } ?>

    <div id="bo_vc" class="view-comment-wrap">
        <?php include_once(G5_BBS_PATH.'/view_comment.php'); ?>
    </div>

    <div id="bo_v_bot">
        <div class="bo_v_com">
            <?php if ($update_href) { ?><a href="<?php echo $update_href ?>" class="ui-btn">수정</a><?php } ?>
            <?php if ($delete_href) { ?><a href="<?php echo $delete_href ?>" class="ui-btn admin" onclick="del(this.href); return false;">삭제</a><?php } ?>
            <?php if ($move_href) { ?><a href="<?php echo $move_href ?>" class="ui-btn admin" onclick="board_move(this.href); return false;">이동</a><?php } ?>
            <?php if ($search_href) { ?><a href="<?php echo $search_href ?>" class="ui-btn">검색</a><?php } ?>
            <a href="<?php echo $list_href ?>" class="ui-btn">목록</a>
        </div>
    </div>
  </div>
</div>

<?php if (!empty($campaign_list)) { ?>
<script>
(function() {
    var list  = document.getElementById('campaignPostsList');
    if (!list) return;
    var items = list.querySelectorAll('.campaign-post-item');
    var total = items.length;
    var VISIBLE = 3;
    var ITEM_H  = items.length > 0 ? items[0].offsetHeight : 60;
    var offset  = 0; // 현재 맨 위에 보이는 인덱스

    // 최대 3개 높이로 컨테이너 고정
    list.style.overflow = 'hidden';
    list.style.height   = (VISIBLE * ITEM_H) + 'px';
    list.style.position = 'relative';

    function applyOffset() {
        for (var i = 0; i < items.length; i++) {
            items[i].style.transform   = 'translateY(' + (-offset * ITEM_H) + 'px)';
            items[i].style.transition  = 'transform .3s ease';
        }
    }

    list.addEventListener('mouseenter', function() {
        list.addEventListener('wheel', onWheel, { passive: false });
    });
    list.addEventListener('mouseleave', function() {
        list.removeEventListener('wheel', onWheel);
    });

    function onWheel(e) {
        e.preventDefault();
        if (e.deltaY > 0) {
            offset = Math.min(offset + 1, Math.max(0, total - VISIBLE));
        } else {
            offset = Math.max(offset - 1, 0);
        }
        applyOffset();
    }

    applyOffset();
})();
</script>
<?php } ?>

<script>
    $(document).on('click', '.blurtext', function () {
        $(this).toggleClass('off');
    });
</script>