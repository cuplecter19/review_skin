<!-- view.php -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">

<?php

add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);


if (defined('G5_EDITOR_PATH') && isset($config['cf_editor']) && $config['cf_editor']) {
    $be_font_loader = G5_EDITOR_PATH.'/'.$config['cf_editor'].'/fonts.loader.php';
    if (is_file($be_font_loader)) {
        include_once($be_font_loader);
        if (function_exists('load_editor_fonts')) {
            // head 영역에 출력되도록 add_stylesheet 사용
            add_stylesheet(load_editor_fonts(), 1);
        }
    }
}

// ─── 블러 치환 ───
$content = $view['content'];
$content = str_replace('(블러시작)', '<span class="blurtext">', $content);
$content = str_replace('(블러끝)', '</span>', $content);

// ─── 표지 URL ───
$bg_url = isset($view['wr_1']) ? $view['wr_1'] : '';
if (strpos($bg_url, 'http://') === 0 || strpos($bg_url, 'https://') === 0) {
    $poster_style = "--view-bg: url('{$bg_url}');";
} elseif ($bg_url !== '') {
    $poster_style = "--view-bg: url('/data/file/review/{$bg_url}');";
} else {
    $poster_style = "";
}

// ─── 별점 함수 (중복 정의 방지) ───
if (!function_exists('render_stars')) {
    function render_stars($score) {
        $score = max(0, min(5, floatval($score)));
        $full  = floor($score);
        $half  = ($score - $full >= 0.5) ? 1 : 0;
        $empty = 5 - $full - $half;

        $html = "<span class='rating-stars fa-stars' aria-label='".number_format($score,1)."점'>";

        for ($i = 0; $i < $full; $i++) {
            $html .= "<span class='star fa-star-wrap full'><i class='fa-regular fa-star star-outline'></i><i class='fa-solid fa-star star-fill'></i></span>";
        }
        if ($half) {
            $html .= "<span class='star fa-star-wrap half'><i class='fa-regular fa-star star-outline'></i><i class='fa-solid fa-star star-fill'></i></span>";
        }
        for ($i = 0; $i < $empty; $i++) {
            $html .= "<span class='star fa-star-wrap empty'><i class='fa-regular fa-star star-outline'></i><i class='fa-solid fa-star star-fill'></i></span>";
        }

        $html .= "</span>";
        return $html;
    }
}

// ─── 태그 파싱 함수 ───
if (!function_exists('parse_tags')) {
    function parse_tags($raw) {
        $raw = trim((string)$raw);
        if ($raw === '') return array();
        $arr = preg_split('/[\s,#]+/u', $raw);
        $tags = array();
        foreach ($arr as $t) {
            $t = trim($t);
            if ($t !== '') $tags[] = $t;
        }
        return $tags;
    }
}

// ─── 태그 조립 ───
$tags = parse_tags(isset($view['wr_7']) ? $view['wr_7'] : '');
if (!empty($view['ca_name'])) {
    array_unshift($tags, $view['ca_name']);
}

// ─── 보호글 / 멤버공개 체크 (카테고리와 무관하게 항상 실행) ───
$is_viewer = true;
$p_url     = "";

if (isset($view['wr_protect']) && $view['wr_protect'] !== '') {
    if (get_session("ss_secret_{$bo_table}_{$view['wr_num']}")
        || ($view['mb_id'] && $view['mb_id'] == $member['mb_id'])
        || $is_admin) {
        $is_viewer = true;
    } else {
        $is_viewer = false;
        $p_url = "./password.php?w=p&amp;bo_table=".$bo_table."&amp;wr_id=".$view['wr_id'].$qstr;
    }
} elseif (isset($view['wr_secret']) && $view['wr_secret'] == '1') {
    if ($board['bo_read_level'] < $member['mb_level'] && $is_member) {
        $is_viewer = true;
    } else {
        $is_viewer = false;
        $p_url = "./login.php";
    }
}

if (!$is_viewer && $p_url !== '') {
    if ($p_url === "./login.php") {
        alert("멤버공개 글입니다. 로그인 후 이용해주세요.", $p_url);
    } else {
        goto_url($p_url);
    }
}

?>
<div id="bo_v" class="view-fullbleed" style="<?php echo $poster_style?>">
  <div class="content-wrap">
    <h1 class="title"><?php echo $view['subject'] ?></h1>
    <div class="rating-line">
      <span class="rating-stars">
        <?php echo render_stars(isset($view['wr_5']) ? $view['wr_5'] : 0); ?>
      </span>
    </div>
    <div class="date">
      <?php echo isset($view['wr_4']) ? $view['wr_4'] : '' ?>
      <?php if (!empty($view['wr_8'])) : ?> ~ <?php echo $view['wr_8'] ?><?php endif; ?>
    </div>

<?php
  $wr6 = isset($view['wr_6']) ? trim($view['wr_6']) : '';
  if ($wr6 !== '') {
?>
    <div class="subnote">
      <?php echo nl2br(htmlspecialchars($wr6, ENT_QUOTES, 'UTF-8')) ?>
    </div>
<?php } ?>

    <div class="tags">
      <?php foreach ($tags as $tg) {
        if (trim($tg) !== '') echo "<span class='tag'>#".htmlspecialchars($tg, ENT_QUOTES, 'UTF-8')."</span>";
      } ?>
    </div>

    <div class="line-view" aria-hidden="true"></div>
    <div class="view-content">
      <?php echo $content ?>
    </div>
    <div class="line-view" aria-hidden="true"></div>

    <div id="bo_vc" class="view-comment-wrap">
      <?php include_once(G5_BBS_PATH.'/view_comment.php'); ?>
    </div>
  </div>

    <div id="bo_v_bot">
        <?php
        ob_start();
        ?>
        <div class="bo_v_com">
            <?php if ($update_href) { ?><a href="<?php echo $update_href ?>" class="ui-btn">수정</a><?php } ?>
            <?php if ($delete_href) { ?><a href="<?php echo $delete_href ?>" class="ui-btn admin" onclick="del(this.href); return false;">삭제</a><?php } ?>
            <?php if ($move_href) { ?><a href="<?php echo $move_href ?>" class="ui-btn admin" onclick="board_move(this.href); return false;">이동</a><?php } ?>
            <?php if ($search_href) { ?><a href="<?php echo $search_href ?>" class="ui-btn">검색</a><?php } ?>
            <a href="<?php echo $list_href ?>" class="ui-btn">목록</a>
        </div>
        <?php
        $link_buttons = ob_get_contents();
        ob_end_flush();
        ?>
    </div>
</div>

<script>
    $(document).on('click', '.blurtext', function () {
        $(this).toggleClass('off');
    });
</script>