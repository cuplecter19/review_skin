<?
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');
$colspan = 5;
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);

$card_w = $board['bo_gallery_width']  ? (int)$board['bo_gallery_width']  : 1000; // 가로 기준
$card_h = $board['bo_gallery_height'] ? (int)$board['bo_gallery_height'] : 250;  // 세로 기준

function render_stars_html($score){
	$score = max(0, min(5, floatval($score)));
	$full  = floor($score);
	$half  = ($score - $full >= 0.5) ? 1 : 0;
	$empty = 5 - $full - $half;

	$html = '<span class="rating-stars fa-stars" aria-label="'.number_format($score,1).'점">';

	for($i=0; $i<$full; $i++){
		$html .= '<span class="star fa-star-wrap full"><i class="fa-regular fa-star star-outline"></i><i class="fa-solid fa-star star-fill"></i></span>';
	}
	if($half){
		$html .= '<span class="star fa-star-wrap half"><i class="fa-regular fa-star star-outline"></i><i class="fa-solid fa-star star-fill"></i></span>';
	}
	for($i=0; $i<$empty; $i++){
		$html .= '<span class="star fa-star-wrap empty"><i class="fa-regular fa-star star-outline"></i><i class="fa-solid fa-star star-fill"></i></span>';
	}

	$html .= '</span>';
	return $html;
}

function parse_tags($raw){
	$raw = trim((string)$raw);
	if($raw === '') return [];
	$arr = preg_split('/[\s,]+/u', $raw);
	$tags = [];
	foreach($arr as $t){
		$t = trim($t);
		if($t === '') continue;
		if(mb_substr($t,0,1) === '#') $t = mb_substr($t,1);
		if($t !== '') $tags[] = $t;
	}
	return $tags;
}

$category_option = get_category_option($bo_table, $sca);
?>
<div <?if($board['bo_table_width']>0){?>style="max-width:<?=$board['bo_table_width']?><?=$board['bo_table_width']>100 ? "px":"%"?>;margin:0 auto;"<?}?>>
<hr class="padding">
<? if($board['bo_content_head']) { ?>
	<div class="board-notice">
		<?=stripslashes($board['bo_content_head']);?>
	</div><hr class="padding" />
<? } ?>

<div class="board-skin-basic">
<nav id="bo_cate">
  <ul id="bo_cate_ul">
	<?php $on_all = ($sca === '' && (string)(isset($sfl) ? $sfl : '') === ''); ?>
	<li>
	  <a href="?bo_table=<?= $bo_table ?>" class="ui-btn<?= $on_all ? ' point' : '' ?>">전체</a>
	</li>

	<?php if ($is_category):
	  foreach (array_filter(array_map('trim', explode('|', (string)(isset($board['bo_category_list']) ? $board['bo_category_list'] : '')))) as $cat):
		$is_on = ($cat === $sca); ?>
		<li>
		  <a href="?bo_table=<?= $bo_table ?>&amp;sca=<?= urlencode($cat) ?>"
			 class="ui-btn<?= $is_on ? ' point' : '' ?>">
			<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
		  </a>
		</li>
	<?php endforeach; endif; ?>
  </ul>
</nav>

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

<ul class="review-list "style="--card-width: <?=$card_w?>px; --card-height: <?=$card_h?>px;">
<?php if (count($list)) { ?>
<?php for ($i=0; $i<count($list); $i++) {
	$row = $list[$i];
	$thumb_url = '';
	if (!empty($row['wr_1'])) {
		$thumb_url = $row['wr_1'];
	} else {
		$thumb = get_list_thumbnail($bo_table, $row['wr_id'], $wrap_max_w, $card_h, false, true);
		$thumb_url = (!empty($thumb) && is_array($thumb) && isset($thumb['src'])) ? $thumb['src'] : '';
	}

	$is_blind = ($row['wr_2'] === '사용');
	$rating = isset($row['wr_5']) ? floatval($row['wr_5']) : 0;
	$stars  = render_stars_html($rating);
	$tags = parse_tags(isset($row['wr_7']) ? $row['wr_7'] : '');

	if (!empty($row['ca_name'])) {
		array_unshift($tags, $row['ca_name']);
	}

	$status = isset($row['wr_3']) ? $row['wr_3'] : '';
?>
  <li class="review-item <?= $is_blind ? 'is-blind' : '' ?>">
	<div class="review-thumb " style="background-image:url('<?=htmlspecialchars($thumb_url)?>');"></div>

	<?php if ($is_blind) { ?>
	  <div class="review-cover-blind"></div>
	<?php } ?>

	<?php if ($status !== '') { ?>
	  <div class="review-meta"><?=htmlspecialchars($status)?></div>
	<?php } ?>

	<div class="review-content">
	  <div class="rating-line">
		<?=$stars?>
	  </div>

	  <div class="review-title">
		<a href="<?=$row['href']?>"><?=htmlspecialchars($row['wr_subject'])?></a>
	  </div>

	  <?php if ($tags) { ?>
		<div class="hashtags">
		  <?php foreach($tags as $tg){ ?>
			<span>#<?=htmlspecialchars($tg)?></span>
		  <?php } ?>
		</div>
	  <?php } ?>
	</div>
<?php
$summary = trim($row['wr_6']);
if (mb_strlen($summary, 'UTF-8') > 250) {
	$summary = mb_substr($summary, 0, 250, 'UTF-8') . '...';
}
?>
	<div class="review-summary">
	  <?= nl2br(htmlspecialchars($summary)) ?>
	</div>
	<a class="card-link" href="<?=$row['href']?>" aria-label="<?=htmlspecialchars($row['wr_subject'])?>"></a>
  </li>
<?php } ?>
<?php } else { ?>
  <li class="review-item" style="display:flex;align-items:center;justify-content:center;">
	게시물이 없습니다.
  </li>
<?php } ?>
</ul>

	<? if ($list_href || $is_checkbox || $write_href) { ?>
	<div class="bo_fx txt-right">
		<? if ($list_href) { ?><a href="<? echo $list_href ?>" class="ui-btn">목록</a><? } ?>
		<? if ($write_href) { ?><a href="<? echo $write_href ?>" class="ui-btn point">글쓰기</a><? } ?>
		<? if($admin_href){?><a href="<?=$admin_href?>" class="ui-btn admin" target="_blank">관리자</a><?}?>
	</div>
	<? } ?>

	</form>
	<? echo $write_pages;  ?>

	<fieldset id="bo_sch" class="txt-center">
		<legend>게시물 검색</legend>
		<form name="fsearch" method="get">
		<input type="hidden" name="bo_table" value="<? echo $bo_table ?>">
		<input type="hidden" name="sca" value="<? echo $sca ?>">
		<input type="hidden" name="sop" value="and">
		<select name="sfl" id="sfl">
			<option value="wr_subject"<? echo get_selected($sfl, 'wr_subject', true); ?>>제목</option>
			<option value="wr_content"<? echo get_selected($sfl, 'wr_content'); ?>>내용</option>
			<option value="wr_subject||wr_content"<? echo get_selected($sfl, 'wr_subject||wr_content'); ?>>제목+내용</option>
			<option value="wr_7"<? echo get_selected($sfl, 'wr_7'); ?>>해시태그</option>
		</select>
		<input type="text" name="stx" value="<? echo stripslashes($stx) ?>" required id="stx" class="frm_input required" size="15" maxlength="20">
		<button type="submit" class="ui-btn point ico search default">검색</button>
		</form>
	</fieldset>
</div>
</div>

<? if ($is_checkbox) { ?>
<script>
function all_checked(sw) {
	var f = document.fboardlist;

	for (var i=0; i<f.length; i++) {
		if (f.elements[i].name == "chk_wr_id[]")
			f.elements[i].checked = sw;
	}
}

function fboardlist_submit(f) {
	var chk_count = 0;

	for (var i=0; i<f.length; i++) {
		if (f.elements[i].name == "chk_wr_id[]" && f.elements[i].checked)
			chk_count++;
	}

	if (!chk_count) {
		alert(document.pressed + "할 게시물을 하나 이상 선택하세요.");
		return false;
	}

	if(document.pressed == "선택복사") {
		select_copy("copy");
		return;
	}

	if(document.pressed == "선택이동") {
		select_copy("move");
		return;
	}

	if(document.pressed == "선택삭제") {
		if (!confirm("선택한 게시물을 정말 삭제하시겠습니까?\n\n한번 삭제한 자료는 복구할 수 없습니다\n\n답변글이 있는 게시글을 선택하신 경우\n답변글도 선택하셔야 게시글이 삭제됩니다."))
			return false;

		f.removeAttribute("target");
		f.action = "./board_list_update.php";
	}

	return true;
}

function select_copy(sw) {
	var f = document.fboardlist;

	if (sw == "copy")
		str = "복사";
	else
		str = "이동";

	var sub_win = window.open("", "move", "left=50, top=50, width=500, height=550, scrollbars=1");

	f.sw.value = sw;
	f.target = "move";
	f.action = "./move.php";
	f.submit();
}
</script>
<? } ?>
<!-- } 게시판 목록 끝 -->