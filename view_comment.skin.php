<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if (!function_exists('apply_blur_tags')) {
    function apply_blur_tags($str) {
        $str = str_replace("(블러시작)", '<span class="blurtext">', $str);
        $str = str_replace("(블러끝)", "</span>", $str);
        return $str;
    }
}

// 변수 초기화
$c_id          = isset($c_id) ? $c_id : '';
$c_wr_content  = isset($c_wr_content) ? $c_wr_content : '';
$comment_min   = isset($comment_min) ? (int)$comment_min : 0;
$comment_max   = isset($comment_max) ? (int)$comment_max : 0;
?>

<script>
var char_min = parseInt(<?php echo $comment_min ?>);
var char_max = parseInt(<?php echo $comment_max ?>);
</script>

<div class="board-comment-list theme-box">
    <?php
    $cmt_amt = count($list);
    for ($i = 0; $i < $cmt_amt; $i++) {
        $comment_id = $list[$i]['wr_id'];
        $cmt_depth  = strlen($list[$i]['wr_comment_reply']) * 10;
        $comment    = $list[$i]['content'];

        $list[$i]['name'] = "<a href='".G5_BBS_URL."/memo_form.php?me_recv_mb_id={$list[$i]['mb_id']}' class='send_memo'>{$list[$i]['wr_name']}</a>";

        $comment = preg_replace("/\[\<a\s.*href\=\"(http|https|ftp|mms)\:\/\/([^[:space:]]+)\.(mp3|wma|wmv|asf|asx|mpg|mpeg)\".*\<\/a\>\]/i", "<script>doc_write(obj_movie('$1://$2.$3'));</script>", $comment);
        $cmt_sv = $cmt_amt - $i + 1;
    ?>
    <?php if ($i == 0) { ?><hr class="co-line" /><?php } ?>
    <div class="item <?php echo ($cmt_depth ? "reply" : "")?>" id="c_<?php echo $comment_id ?>" <?php if ($cmt_depth) { ?>style="border-left-width: <?php echo $cmt_depth ?>px;"<?php } ?>>
        <div class="co-name txt-point">
        <?php echo get_text($list[$i]['wr_name']); ?>
        </div>
        <div class="co-content">
            <div class="co-inner">
                <?php if (strstr($list[$i]['wr_option'], "secret")) { ?><span class="secret">[ 비밀글 ]</span><?php } ?>
                <?php echo apply_blur_tags($comment) ?>
            </div>

            <div class="co-info">
                <span><?php echo date('m.d H:i', strtotime($list[$i]['wr_datetime'])) ?></span>
                <?php if ($list[$i]['is_reply'] || $list[$i]['is_edit'] || $list[$i]['is_del']) {
                    $query_string = clean_query_string($_SERVER['QUERY_STRING']);

                    if ($w == 'cu') {
                        $sql = " select wr_id, wr_content, mb_id from $write_table where wr_id = '$c_id' and wr_is_comment = '1' ";
                        $cmt = sql_fetch($sql);
                        if (!($is_admin || ($member['mb_id'] == $cmt['mb_id'] && $cmt['mb_id'])))
                            $cmt['wr_content'] = '';
                        $c_wr_content = $cmt['wr_content'];
                    }
                    $c_reply_href = './board.php?'.$query_string.'&amp;c_id='.$comment_id.'&amp;w=c#bo_vc_w';
                    $c_edit_href  = './board.php?'.$query_string.'&amp;c_id='.$comment_id.'&amp;w=cu#bo_vc_w';
                ?>
                <?php if ($list[$i]['is_reply']) { ?><span><a href="<?php echo $c_reply_href;  ?>" onclick="comment_box('<?php echo $comment_id ?>', 'c'); return false;">답변</a></span><?php } ?>
                <?php if ($list[$i]['is_edit']) { ?><span><a href="<?php echo $c_edit_href;  ?>" onclick="comment_box('<?php echo $comment_id ?>', 'cu'); return false;">수정</a></span><?php } ?>
                <?php if ($list[$i]['is_del'])  { ?><span><a href="<?php echo $list[$i]['del_link'];  ?>" onclick="return comment_delete();">삭제</a></span><?php } ?>
                <?php } ?>
            </div>
            <span id="edit_<?php echo $comment_id ?>"></span><!-- 수정 -->
            <span id="reply_<?php echo $comment_id ?>"></span><!-- 답변 -->
            <input type="hidden" value="<?php echo strstr($list[$i]['wr_option'],"secret") ?>" id="secret_comment_<?php echo $comment_id ?>">
            <textarea id="save_comment_<?php echo $comment_id ?>" style="display:none"><?php echo get_text($list[$i]['content1'], 0) ?></textarea>
        </div>
    </div>
    <hr class="co-line" />
    <?php } ?>
</div>
<?php if ($cmt_amt == 0) { ?>
<script>
    $('.board-comment-list').remove();
</script>
<?php } ?>
<?php if (isset($is_comment_write) && $is_comment_write) { //@210403
    if ($w == '')
        $w = 'c';
?>
<!-- 댓글 쓰기 시작 { -->
<div id="bo_vc_w" class="board-comment-write">
    <form name="fviewcomment" action="./write_comment_update.php" onsubmit="return fviewcomment_submit(this);" method="post" autocomplete="off">
        <input type="hidden" name="w" value="<?php echo $w ?>" id="w">
        <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
        <input type="hidden" name="wr_id" value="<?php echo $wr_id ?>">
        <input type="hidden" name="comment_id" value="<?php echo $c_id ?>" id="comment_id">
        <input type="hidden" name="sca" value="<?php echo $sca ?>">
        <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
        <input type="hidden" name="stx" value="<?php echo $stx ?>">
        <input type="hidden" name="spt" value="<?php echo $spt ?>">
        <input type="hidden" name="page" value="<?php echo $page ?>">
        <input type="hidden" name="is_good" value="">
        <div class="board-comment-form">
    <?php if ($comment_min || $comment_max) { ?><strong id="char_cnt"><span id="char_count"></span>글자</strong><?php } ?>
            <textarea id="wr_content" name="wr_content" maxlength="10000" required class="required" title="내용"
            <?php if ($comment_min || $comment_max) { ?>onkeyup="check_byte('wr_content', 'char_count');"<?php } ?>><?php echo $c_wr_content;  ?></textarea>
            <?php if ($comment_min || $comment_max) { ?><script> check_byte('wr_content', 'char_count'); </script><?php } ?>
            <script>
            $(document).on( "keyup change", "textarea#wr_content[maxlength]", function(){
                var str = $(this).val()
                var mx = parseInt($(this).attr("maxlength"))
                if (str.length > mx) {
                    $(this).val(str.substr(0, mx));
                    return false;
                }
            });
            </script>
            <p>
            <?php if ($is_guest) { //@210403 ?>
            <label for="wr_name" class="sound_only">이름<strong> 필수</strong></label>
            <input type="text" name="wr_name" value="<?php echo get_cookie("ck_sns_name"); ?>" id="wr_name" required class="frm_input required" size="25" placeholder="이름">
            <label for="wr_password" class="sound_only">비밀번호<strong> 필수</strong></label>
            <input type="password" name="wr_password" id="wr_password" required class="frm_input required" size="25"  placeholder="비밀번호">&nbsp;&nbsp;
            <?php } ?>
            <input type="checkbox" name="secret" value="secret" id="wr_secret"> <label for="wr_secret">비밀글</label> <?php //@230616 비밀글 처리?>
            </p>

            <div class="btn_confirm">
                <button type="submit" id="btn_submit" class="ui-btn">타래잇기</button>
            </div>
        </div>
    </form>
</div>
<script>
var save_before = '';
var save_html = document.getElementById('bo_vc_w').innerHTML;

function good_and_write()
{
    var f = document.fviewcomment;
    if (fviewcomment_submit(f)) {
        f.is_good.value = 1;
        f.submit();
    } else {
        f.is_good.value = 0;
    }
}
function fviewcomment_submit(f)
{
    var pattern = /(^\s*)|(\s*$)/g; // \s 공백 문자

    f.is_good.value = 0;

    var subject = "";
    var content = "";
    $.ajax({
        url: g5_bbs_url+"/ajax.filter.php",
        type: "POST",
        data: {
            "subject": "",
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
    if (content) {
        alert("내용에 금지단어('"+content+"')가 포함되어있습니다");
        f.wr_content.focus();
        return false;
    }
    var pattern = /(^\s*)|(\s*$)/g; // \s 공백 문자
    document.getElementById('wr_content').value = document.getElementById('wr_content').value.replace(pattern, "");
    if (char_min > 0 || char_max > 0)
    {
        check_byte('wr_content', 'char_count');
        var cnt = parseInt(document.getElementById('char_count').innerHTML);
        if (char_min > 0 && char_min > cnt)
        {
            alert("댓글은 "+char_min+"글자 이상 쓰셔야 합니다.");
            return false;
        } else if (char_max > 0 && char_max < cnt)
        {
            alert("댓글은 "+char_max+"글자 이하로 쓰셔야 합니다.");
            return false;
        }
    }
    else if (!document.getElementById('wr_content').value)
    {
        alert("댓글을 입력하여 주십시오.");
        return false;
    }

    if (typeof(f.wr_name) != 'undefined')
    {
        f.wr_name.value = f.wr_name.value.replace(pattern, "");
        if (f.wr_name.value == '')
        {
            alert('이름이 입력되지 않았습니다.');
            f.wr_name.focus();
            return false;
        }
    }

    if (typeof(f.wr_password) != 'undefined')
    {
        f.wr_password.value = f.wr_password.value.replace(pattern, "");
        if (f.wr_password.value == '')
        {
            alert('비밀번호가 입력되지 않았습니다.');
            f.wr_password.focus();
            return false;
        }
    }
    set_comment_token(f);

    document.getElementById("btn_submit").disabled = "disabled";

    return true;
}
function comment_box(comment_id, work)
{
    var el_id;

    if (comment_id)
    {
        if (work == 'c')
            el_id = 'reply_' + comment_id;
        else
            el_id = 'edit_' + comment_id;
    }
    else
        el_id = 'bo_vc_w';

    if (save_before != el_id)
    {
        if (save_before)
        {
            document.getElementById(save_before).style.display = 'none';
            document.getElementById(save_before).innerHTML = '';
        }

        document.getElementById(el_id).style.display = '';
        document.getElementById(el_id).innerHTML = save_html;

        if (work == 'cu')
        {
            document.getElementById('wr_content').value = document.getElementById('save_comment_' + comment_id).value;
            if (typeof char_count != 'undefined')
                check_byte('wr_content', 'char_count');
            if (document.getElementById('secret_comment_'+comment_id).value)
                document.getElementById('wr_secret').checked = true;
            else
                document.getElementById('wr_secret').checked = false;
        }

        document.getElementById('comment_id').value = comment_id;
        document.getElementById('w').value = work;


        save_before = el_id;
    }
}

function comment_delete()
{
    return confirm("이 댓글을 삭제하시겠습니까?");
}
comment_box('', 'c'); // 댓글 입력폼이 보이도록 처리하기위해서 추가 (root님)
<?php if (isset($board['bo_use_sns']) && $board['bo_use_sns'] && ($config['cf_facebook_appid'] || $config['cf_twitter_key'])) { ?>
$(function() {
    $("#bo_vc_send_sns").load(
        "<?php echo G5_SNS_URL; ?>/view_comment_write.sns.skin.php?bo_table=<?php echo $bo_table; ?>",
        function() {
            save_html = document.getElementById('bo_vc_w').innerHTML;
        }
    );
});
<?php } ?>


</script>
<?php } ?>