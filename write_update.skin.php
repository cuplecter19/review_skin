<?php

if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// ── 해시태그 정제 함수 ──
if (!function_exists('sanitize_hashtags')) {
    function sanitize_hashtags($raw) {
        $raw = trim((string)$raw);
        if ($raw === '') return '';
        $parts  = explode(',', $raw);
        $result = array();
        foreach ($parts as $part) {
            $part = trim($part);
            if (mb_substr($part, 0, 1, 'UTF-8') === '#') {
                $part = mb_substr($part, 1, null, 'UTF-8');
            }
            $part = trim($part);
            if ($part !== '') $result[] = $part;
        }
        return implode(',', $result);
    }
}

if (isset($_POST['wr_7'])) {
    $_POST['wr_7'] = sanitize_hashtags($_POST['wr_7']);
}
$temp = sql_fetch("SELECT * FROM {$write_table} LIMIT 1");

if (!isset($temp['wr_protect'])) {
    sql_query("ALTER TABLE `{$write_table}` ADD `wr_protect` VARCHAR(255) NOT NULL DEFAULT '' AFTER `wr_url`");
}

// wr_content 컬럼 타입을 LONGTEXT로 변경 (INFORMATION_SCHEMA로 정확히 확인)
$col_check = sql_fetch("SELECT COLUMN_TYPE
                          FROM INFORMATION_SCHEMA.COLUMNS
                         WHERE TABLE_SCHEMA = DATABASE()
                           AND TABLE_NAME   = '{$write_table}'
                           AND COLUMN_NAME  = 'wr_content'
                         LIMIT 1");

if (!empty($col_check) && strtolower($col_check['COLUMN_TYPE']) !== 'longtext') {
    sql_query("ALTER TABLE `{$write_table}` MODIFY COLUMN `wr_content` LONGTEXT");
}

unset($temp, $col_check);


// ─── 2) POST 값 수신 (PHP 5.x 호환) ───
$link       = trim((string)(isset($_POST['wr_1']) ? $_POST['wr_1'] : ''));
$poster_del = !empty($_POST['poster_del']);
$set_secret = isset($_POST['set_secret']) ? $_POST['set_secret'] : '';
$wr_protect = isset($_POST['wr_protect']) ? $_POST['wr_protect'] : '';


// ─── 3) 표지 파일 헬퍼 함수 ───
if (!function_exists('av_bf_pick_url')) {
    function av_bf_pick_url($bo_table, $wr_id, $bf_no) {
        global $g5;
        $row = sql_fetch("SELECT bf_file FROM {$g5['board_file_table']}
                           WHERE bo_table = '".sql_real_escape_string($bo_table)."'
                             AND wr_id  = ".(int)$wr_id."
                             AND bf_no  = ".(int)$bf_no."
                           LIMIT 1");
        return !empty($row['bf_file'])
            ? G5_DATA_URL.'/file/'.$bo_table.'/'.$row['bf_file']
            : '';
    }
}

if (!function_exists('av_bf_delete')) {
    function av_bf_delete($bo_table, $wr_id, $bf_no) {
        global $g5;
        $row = sql_fetch("SELECT bf_file FROM {$g5['board_file_table']}
                           WHERE bo_table = '".sql_real_escape_string($bo_table)."'
                             AND wr_id  = ".(int)$wr_id."
                             AND bf_no  = ".(int)$bf_no."
                           LIMIT 1");
        if (!empty($row['bf_file'])) {
            $base = G5_DATA_PATH.'/file/'.$bo_table.'/';
            @unlink($base.$row['bf_file']);
            @unlink($base.'thumb/'.$row['bf_file']);
            sql_query("DELETE FROM {$g5['board_file_table']}
                        WHERE bo_table = '".sql_real_escape_string($bo_table)."'
                          AND wr_id  = ".(int)$wr_id."
                          AND bf_no  = ".(int)$bf_no);
        }
    }
}


// ─── 4) 표지(wr_1) 처리 ───
$w1 = '';

if ($poster_del) {
    av_bf_delete($bo_table, $wr_id, 0);
    $w1 = '';
    $_POST['wr_1'] = '';
    if (isset($write)) $write['wr_1'] = '';
} else {
    if ($link !== '') {
        $w1 = $link;
    } else {
        $w1 = av_bf_pick_url($bo_table, $wr_id, 0);
    }
}

sql_query("UPDATE {$write_table}
              SET wr_1 = '".sql_real_escape_string($w1)."'
            WHERE wr_id = ".(int)$wr_id);


// ─── 5) 공개/비밀/보호글 설정 (댓글 제외) ───
if ($w !== 'c' && $w !== 'cu') {

    $sec     = '';
    $mem     = 0;
    $protect = '';

    if ($set_secret !== '') {
        if ($set_secret === 'secret') {
            $sec = 'secret';
        } elseif ($set_secret === 'member') {
            $mem = 1;
        } elseif ($set_secret === 'protect' && $wr_protect !== '') {
            $protect = $wr_protect;
        }
    }

    $safe_html    = sql_real_escape_string(isset($html) ? $html : '');
    $safe_sec     = sql_real_escape_string($sec);
    $safe_protect = sql_real_escape_string($protect);

    sql_query("UPDATE {$write_table}
                  SET wr_option  = '{$safe_html},{$safe_sec}',
                      wr_secret  = '".(int)$mem."',
                      wr_protect = '{$safe_protect}'
                WHERE wr_id = ".(int)$wr_id);
}


// ─── 6) 완료 후 리다이렉트 ───
goto_url(G5_HTTP_BBS_URL.'/board.php?bo_table='.$bo_table.$qstr);

?>