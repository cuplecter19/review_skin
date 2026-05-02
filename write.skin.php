<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);

if (!function_exists('render_stars')) {
	function render_stars($score) {
		$score = floatval($score);
		$full  = floor($score);
		$half  = ($score - $full >= 0.5);
		$empty = 5 - $full - ($half ? 1 : 0);

		$html = "";
		for ($i=0; $i<$full; $i++) {
			$html .= "<span class='star full'>★</span>";
		}
		if ($half) {
			$html .= "<span class='star half'>★</span>";
		}
		for ($i=0; $i<$empty; $i++) {
			$html .= "<span class='star empty'>☆</span>";
		}
		return $html;
	}
}

?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


<hr class="padding">
<section id="bo_w" <?php if($board['bo_table_width']>0){?>style="max-width:<?php echo $board['bo_table_width']?><?php echo $board['bo_table_width']>100 ? "px":"%"?>;margin:0 auto;"<?php }?>>
	<!-- 게시물 작성/수정 시작 { -->
	<form name="fwrite" id="fwrite" action="<?php echo $action_url ?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
	<input type="hidden" name="uid" value="<?php echo get_uniqid(); ?>">
	<input type="hidden" name="w" value="<?php echo $w ?>">
	<input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
	<input type="hidden" name="wr_id" value="<?php echo $wr_id ?>">
	<input type="hidden" name="sca" value="<?php echo $sca ?>">
	<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
	<input type="hidden" name="stx" value="<?php echo $stx ?>">
	<input type="hidden" name="spt" value="<?php echo $spt ?>">
	<input type="hidden" name="sst" value="<?php echo $sst ?>">
	<input type="hidden" name="sod" value="<?php echo $sod ?>">
	<input type="hidden" name="page" value="<?php echo $page ?>">
	<?php
	$option = '';
	$option_hidden = '';
	if ($is_notice || $is_html || $is_secret || $is_mail) {
		$option = '';

		if ($is_html) {
			if ($is_dhtml_editor) {
				$option_hidden .= '<input type="hidden" value="html1" name="html">';
			} else {
				$option .= "\n".'<input type="checkbox" id="html" name="html" onclick="html_auto_br(this);" value="'.$html_value.'" '.$html_checked.'>'."\n".'<label for="html">html</label>';
			}
		}

		if ($is_secret) {
			if ($is_admin || $is_secret==1) {
				if($secret_checked)$sec_select="selected";
				$sec .='<option value="secret" '.$sec_select.'>비밀글</option>';
			} else {
				$option_hidden .= '<input type="hidden" name="secret" value="secret">';
			}
		}

		if ($is_mail) {
			$option .= "\n".'<input type="checkbox" id="mail" name="mail" value="mail" '.$recv_email_checked.'>'."\n".'<label for="mail">답변메일받기</label>';
		}
	}

	echo $option_hidden;
		if($write['wr_secret']=='1') $mem_select="selected";
		if($write['wr_protect']!='') $pro_select="selected";
		if($is_member) {$sec .='<option value="protect" '.$pro_select.'>보호글</option>';
		$sec .='<option value="member" '.$mem_select.'>멤버공개</option>';}
	?>

	<div class="board-write theme-box">
<div class="top-line">
  <?php if ($is_category) { ?>
	<label class="item">
	  <span class="lbl">분류</span>
	  <select name="ca_name" id="ca_name" required class="required">
		<option value="">선택</option>
		<?php echo $category_option ?>
	  </select>
	</label>
  <?php } ?>
  <?php if($is_secret!=2 || $is_admin){ ?>
	<label class="item">
	  <span class="lbl">공개</span>
	  <select name="set_secret" id="set_secret">
		<option value="">전체공개</option>
		<?php echo $sec?>
	  </select>
	</label>
  <?php } ?>
  <?php if ($option) { ?>
	<span class="item option-box">
	  <?php echo $option?>
	</span>
  <?php } ?>
  <label class="item" id="set_protect_inline" style="display:<?php echo $w=='u' && $pro_select ? 'inline-flex':'none'?>;">
	<span class="lbl">암호</span>
	<input type="text" name="wr_protect" id="wr_protect" value="<?php echo $write['wr_protect']?>" maxlength="20" class="frm_input">
  </label>
</div>

<!-- 캠페인 UI -->
<dl class="campaign-row">
    <dt><label>캠페인</label></dt>
    <dd>
        <label>
            <input type="checkbox" id="use_campaign" name="use_campaign" value="1"
                <?php echo (!empty($write['wr_9'])) ? 'checked' : '' ?>>
            캠페인에 포함
        </label>
        <div id="campaign_selector" style="display:<?php echo (!empty($write['wr_9'])) ? 'block' : 'none' ?>;margin-top:8px;">
            <?php
            $sql_camp = "SELECT DISTINCT wr_9 FROM {$write_table} WHERE wr_is_comment=0 AND wr_9 != '' ORDER BY wr_9";
            $res_camp = sql_query($sql_camp);
            $camp_list = array();
            while ($crow = sql_fetch_array($res_camp)) { $camp_list[] = $crow['wr_9']; }
            ?>
            <?php if (!empty($camp_list)) { ?>
            <select id="campaign_select" class="frm_input">
                <option value="">-- 기존 캠페인 선택 --</option>
                <?php foreach ($camp_list as $cn) { ?>
                <option value="<?php echo htmlspecialchars($cn, ENT_QUOTES, 'UTF-8') ?>"
                    <?php echo (isset($write['wr_9']) && $write['wr_9'] === $cn) ? 'selected' : '' ?>>
                    <?php echo htmlspecialchars($cn, ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php } ?>
            </select>
            <?php } ?>
            <input type="text" id="campaign_new_input" class="frm_input" placeholder="새 캠페인명 직접 입력" value="<?php echo (!empty($write['wr_9']) && !in_array($write['wr_9'], $camp_list)) ? htmlspecialchars($write['wr_9'], ENT_QUOTES, 'UTF-8') : '' ?>">
            <input type="hidden" name="wr_9" id="wr_9" value="<?php echo get_text(isset($write['wr_9']) ? $write['wr_9'] : ''); ?>">
            <small style="display:block;margin-top:4px;opacity:.7;">기존 캠페인 선택 또는 새 캠페인명 입력 (둘 다 입력 시 직접 입력값 우선)</small>
        </div>
    </dd>
</dl>

	<dl>
		<dt>작품명</dt>
		<dd><input type="text" name="wr_subject" value="<?php echo $subject ?>" id="wr_subject" required class="frm_input required full" size="50" maxlength="255"></dd>
	</dl>
		<?php if($board['bo_1']) { ?>
		<div class="write-notice">
			<?php echo $board['bo_1']?>
		</div>
		<?php } ?>

	<dl class="poster_file">
	<dt><label for="poster_file">표지</label></dt>
	<dd>
		<div class="poster-grid">
		<input type="file" name="bf_file[]" id="poster_file" title="파일첨부 : 용량 <?php echo $upload_max_filesize ?> 이하만 업로드 가능" class="frm_file">
		<input type="text" name="wr_1" id="photocard_link_back" value="<?php echo get_text(isset($write['wr_1']) ? $write['wr_1'] : ''); ?>" class="frm_input" placeholder="외부 링크 이용시 사용">
		<?php if ($w == 'u' && !empty($write['wr_1'])) { ?>
			<label class="back_del">
			<input type="checkbox" name="poster_del" value="1"> 표지 파일 삭제
			</label>
		<?php } ?>
		</div>
	</dd>
	</dl>
<div class="form-bpd">
<dl>
  <dt><label for="wr_2">작가명</label></dt>
  <dd>
    <input type="text" name="wr_2" id="wr_2" value="<?php echo get_text(isset($write['wr_2']) ? $write['wr_2'] : ''); ?>" class="frm_input full">
  </dd>
</dl>
  <dl>
	<dt><label for="wr_3">진행 상황</label></dt>
	<dd>
	  <select name="wr_3" id="wr_3" class="frm_input">
		<option value="">선택</option>
		<option value="감상 중" <?php echo get_selected(isset($write['wr_3']) ? $write['wr_3'] : '', '감상 중')?>>감상 중</option>
		<option value="감상 완료" <?php echo get_selected(isset($write['wr_3']) ? $write['wr_3'] : '', '감상 완료')?>>감상 완료</option>
		<option value="중도 하차" <?php echo get_selected(isset($write['wr_3']) ? $write['wr_3'] : '', '중도 하차')?>>중도 하차</option>
	  </select>
	</dd>
  </dl>
</div>
<div class="form-date">
  <dl>
	<dt><label for="wr_4_start">감상 기간</label></dt>
	<dd class="date-wrap">
	  <input type="date" name="wr_4" value="<?php echo get_text(isset($write['wr_4']) ? $write['wr_4'] : '')?>" class="frm_input date_input">
	  <span>~</span>
	  <input type="date" name="wr_8"   value="<?php echo get_text(isset($write['wr_8']) ? $write['wr_8'] : '')?>"   class="frm_input date_input">
	</dd>
  </dl>
</div>
	<dl>
		<dt>평점</dt>
		<dd class="rating-wrap">
			<div id="rating-stars">
			<?php echo render_stars(isset($write['wr_5']) ? $write['wr_5'] : 0); ?>
			</div>
			<input type="text" name="wr_5" id="rating-input"
				value="<?php echo get_text(isset($write['wr_5']) ? $write['wr_5'] : '')?>"
				class="frm_input required"
				size="5" maxlength="4">
			<span>점</span>
		</dd>
	</dl>
	<dl>
		<dt>줄거리</dt>
		<dd><div class="wr_content" style="width:100%;max-width:100%;box-sizing:border-box;overflow:hidden;"><textarea name="wr_6" id="wr_6"><?php echo isset($write['wr_6']) ? $write['wr_6'] : '' ?></textarea></div></dd>
	</dl>
	<dl>
		<dd><div class="wr_content" style="width:100%;max-width:100%;box-sizing:border-box;overflow:hidden;">
			<?php if($write_min || $write_max) { ?>
			<p id="char_count_desc">이 게시판은 최소 <strong><?php echo $write_min; ?></strong>글자 이상, 최대 <strong><?php echo $write_max; ?></strong>글자 이하까지 글을 쓰실 수 있습니다.</p>
			<?php } ?>
			<?php echo $editor_html;?>
			<?php if(!$board['bo_use_dhtml_editor']) { ?>
			<div class="editor-toolbar">
			<?php if (function_exists('emote_popup_tag')) { echo emote_popup_tag('wr_content'); } else { ?>
			<button type="button" class="emoticon-btn" onclick="open_emoticon_write();" title="이모티콘">
			    <i class="fa-regular fa-face-smile"></i> 이모티콘
			</button>
			<?php } ?>
			</div>
			<?php } ?>
			<?php if($write_min || $write_max) { ?>
			<div id="char_count_wrap"><span id="char_count"></span>글자</div>
			<?php } ?>
		</div></dd>
	</dl>
<?php if(!$board['bo_use_dhtml_editor']){?>
<?php }?>
	<dl>
		<dt>해시태그</dt>
		<dd>
            <input type="text" name="wr_7" value="<?php echo $wr_7 ?>" id="wr_7" class="frm_input full" maxlength="255">
            <small style="display:block;margin-top:4px;opacity:.7;">쉼표(,)로 구분. 단어 내 띄어쓰기는 허용. 예: 판타지 소설, SF, 추천</small>
        </dd>
	</dl>
<?php if(!$is_member){?>
	<dl>
		<dt></dt>
		<dd class="txt-right">
	<?php if ($is_name) { ?>
		<label for="wr_name">NAME<strong class="sound_only">필수</strong></label>
		<input type="text" name="wr_name" value="<?php echo $name ?>" id="wr_name" required class="frm_input required" >
	<?php } ?>

	<?php if ($is_password) { ?>
		&nbsp;&nbsp;
		<label for="wr_password">PASSWORD<strong class="sound_only">필수</strong></label>
		<input type="password" name="wr_password" id="wr_password" <?php echo $password_required ?> class="frm_input <?php echo $password_required ?>" >
	<?php } ?>
	</dd>
	</dl>
	<?php }?>
	</div>
	<hr class="padding" />
	<div class="btn_confirm txt-center">
		<input type="submit" value="작성완료" id="btn_submit" accesskey="s" class="btn_submit ui-btn point">
		<a href="./board.php?bo_table=<?php echo $bo_table ?>" class="btn_cancel ui-btn">취소</a>
	</div>
	</form>

	<script>
	<?php if($write_min || $write_max) { ?>
	var char_min = parseInt(<?php echo $write_min; ?>);
	var char_max = parseInt(<?php echo $write_max; ?>);
	check_byte("wr_content", "char_count");
	$(function() {
		$("#wr_content").on("keyup", function() {
			check_byte("wr_content", "char_count");
		});
	});
	<?php } ?>
	function html_auto_br(obj)
	{
		if (obj.checked) {
			result = confirm("자동 줄바꿈을 하시겠습니까?\n\n자동 줄바꿈은 게시물 내용중 줄바뀐 곳을<br>태그로 변환하는 기능입니다.");
			if (result)
				obj.value = "html2";
			else
				obj.value = "html1";
		}
		else
			obj.value = "";
	}
	function fwrite_submit(f)
	{
		<?php echo $editor_js; // 에디터 사용시 자바스크립트에서 내용을 폼필드로 넣어주며 내용이 입력되었는지 검사함   ?>
		var subject = "";
		var content = "";
		$.ajax({
			url: g5_bbs_url+"/ajax.filter.php",
			type: "POST",
			data: {
				"subject": f.wr_subject.value,
				"content": f.wr_content.value
			},
			dataType: "json",
			async: false,
			cache: false,
			success: function(data, textStatus) {
				subject = data.subject;
				content = data.content;
			}
		});
		if (subject) {
			alert("제목에 금지단어('"+subject+"')가 포함되어있습니다");
			f.wr_subject.focus();
			return false;
		}

		if (content) {
			alert("내용에 금지단어('"+content+"')가 포함되어있습니다");
			if (typeof(ed_wr_content) != "undefined")
				ed_wr_content.returnFalse();
			else
				f.wr_content.focus();
			return false;
		}
		if (document.getElementById("char_count")) {
			if (char_min > 0 || char_max > 0) {
				var cnt = parseInt(check_byte("wr_content", "char_count"));
				if (char_min > 0 && char_min > cnt) {
					alert("내용은 "+char_min+"글자 이상 쓰셔야 합니다.");
					return false;
				}
				else if (char_max > 0 && char_max < cnt) {
					alert("내용은 "+char_max+"글자 이하로 쓰셔야 합니다.");
					return false;
				}
			}
		}
		document.getElementById("btn_submit").disabled = "disabled";
		return true;
	}
	$('#set_secret').on('change', function() {
		var selection = $(this).val();
		if(selection=='protect') $('#set_protect').css('display','block');
		else {$('#set_protect').css('display','none'); $('#wr_protect').val('');}
	});
	</script>
</section>
<!-- } 게시물 작성/수정 끝 -->

<script>
(function(){
  var starEl = document.getElementById("rating-stars");
  var inputEl = document.getElementById("rating-input");

  function renderStars(val){
	var full = Math.floor(val);
	var half = (val - full >= 0.5) ? 1 : 0;
	var html = "";
	for(var i=1;i<=5;i++){
	  if(i <= full) html += '<i class="fa-solid fa-star"></i>';
	  else if(half && i === full+1){ html += '<i class="fa-solid fa-star-half-stroke"></i>'; half=0; }
	  else html += '<i class="fa-regular fa-star"></i>';
	}
	starEl.innerHTML = html;
  }

  var val = parseFloat(inputEl.value) || 0;
  renderStars(val);

  inputEl.addEventListener("input", function(){
	var v = parseFloat(inputEl.value) || 0;
	if(v>5) v=5;
	if(v<0) v=0;
	v = Math.round(v*2)/2;
	inputEl.value = v.toFixed(1);
	renderStars(v);
  });

  starEl.addEventListener("click", function(e){
	var rect = starEl.getBoundingClientRect();
	var unit = rect.width/5;
	var raw = (e.clientX - rect.left) / unit + 0.5;
	var score = Math.min(5, Math.max(0.5, Math.round(raw*2)/2));
	inputEl.value = score.toFixed(1);
	renderStars(score);
  });
})();

$('#set_secret').on('change', function() {
  var v = $(this).val();
  if (v === 'protect') $('#set_protect_inline').css('display','inline-flex');
  else { $('#set_protect_inline').hide(); $('#wr_protect').val(''); }
});

// ── 캠페인 UI ──
var useCampaignEl = document.getElementById('use_campaign');
var campaignSelEl = document.getElementById('campaign_select');
var campaignNewEl = document.getElementById('campaign_new_input');
var wr9El         = document.getElementById('wr_9');

if (useCampaignEl) {
    useCampaignEl.addEventListener('change', function() {
        var sel = document.getElementById('campaign_selector');
        sel.style.display = this.checked ? 'block' : 'none';
        if (!this.checked && wr9El) wr9El.value = '';
    });
}
if (campaignSelEl) {
    campaignSelEl.addEventListener('change', function() {
        if (this.value && wr9El) wr9El.value = this.value;
    });
}
if (campaignNewEl) {
    campaignNewEl.addEventListener('input', function() {
        if (this.value.trim()) {
            if (wr9El) wr9El.value = this.value.trim();
        } else if (campaignSelEl && campaignSelEl.value) {
            if (wr9El) wr9El.value = campaignSelEl.value;
        } else {
            if (wr9El) wr9El.value = '';
        }
    });
}

// ── 이모티콘 (스킨 내 emoticon_list.php 사용) ──
function open_emoticon_write() {
    var url = '<?php echo $board_skin_url ?>/emoticon_list.php?target_id=wr_content';
    window.open(url, 'emoticon', 'width=400,height=500,scrollbars=yes');
}
</script>

<script>
/* 에디터 iframe/textarea 너비 강제 보정 (PHP 5.6 호환) */
$(function(){
  setTimeout(function(){
    $('#bo_w .wr_content').find('iframe, textarea, table, .note-editor, .se2_inputarea')
      .css({'width':'100%','max-width':'100%','box-sizing':'border-box'});
  }, 500);
});
</script>
