<?php
include_once('./_common.php');

$target_id = isset($_GET['target_id']) ? htmlspecialchars($_GET['target_id'], ENT_QUOTES, 'UTF-8') : 'wr_content';

$sql    = "SELECT * FROM {$g5['emoticon_table']}";
$result = sql_query($sql);
$i      = 0;
$rows   = array();
while ($row = sql_fetch_array($result)) {
    $rows[] = $row;
    $i++;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title>이모티콘</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    background: #1e1e1e;
    color: #fff;
    font-family: 'Pretendard', -apple-system, sans-serif;
}
#emote-header {
    padding: 10px 14px;
    border-bottom: 1px solid rgba(255,255,255,.1);
    font-size: 13px;
    font-weight: 600;
    letter-spacing: .03em;
}
#emote-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 12px;
    list-style: none;
}
#emote-list li {
    cursor: pointer;
    text-align: center;
    padding: 8px;
    border-radius: 8px;
    transition: background .15s;
}
#emote-list li:hover { background: rgba(255,255,255,.12); }
#emote-list li img  { max-width: 56px; display: block; }
#emote-list li span {
    font-size: 10px;
    display: block;
    margin-top: 4px;
    opacity: .65;
    word-break: break-all;
    max-width: 60px;
}
.no-data { padding: 24px; opacity: .55; width: 100%; font-size: 13px; }
</style>
</head>
<body>
<div id="emote-header">이모티콘</div>
<ul id="emote-list">
<?php if ($i > 0): ?>
    <?php foreach ($rows as $row): ?>
    <li data-text="<?php echo htmlspecialchars($row['me_text'], ENT_QUOTES, 'UTF-8') ?>">
        <img src="<?php echo G5_URL . $row['me_img'] ?>" alt="">
        <span><?php echo htmlspecialchars($row['me_text'], ENT_QUOTES, 'UTF-8') ?></span>
    </li>
    <?php endforeach; ?>
<?php else: ?>
    <li class="no-data">등록된 이모티콘이 없습니다.</li>
<?php endif; ?>
</ul>
<script>
(function () {
    var targetId = '<?php echo $target_id ?>';
    var items = document.querySelectorAll('#emote-list li[data-text]');
    for (var k = 0; k < items.length; k++) {
        (function (li) {
            li.addEventListener('click', function () {
                var text = li.getAttribute('data-text');
                try {
                    var op = window.opener;
                    if (op) {
                        var el = op.document.getElementById(targetId);
                        if (el) {
                            var s = el.selectionStart || 0;
                            var e = el.selectionEnd   || s;
                            el.value = el.value.substring(0, s) + text + el.value.substring(e);
                            el.focus();
                            el.selectionStart = el.selectionEnd = s + text.length;
                        }
                    }
                } catch (err) {}
                window.close();
            });
        })(items[k]);
    }
})();
</script>
</body>
</html>
