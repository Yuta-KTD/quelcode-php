<?php
$array = explode(',', $_GET['array']);

// 修正はここから
// バブルソート
// 参考：https://mikaduki.info/webprogram/php/1120/

//並べ替え自体を行うセット回数
for ($i = 0; $i < count($array); $i++)
{
    $next = count($array) - $i;
    //並べ替えセット一回で行う内部の並べ替え回数
    for ($j=1; $j < $next; $j++) {
        if($array[$j] < $array[$j-1]) {
            $swap = $array[$j];
            $array[$j] = $array[$j-1];
            $array[$j-1] = $swap;
        }
    }
}

// 修正はここまで

echo "<pre>";
print_r($array);
echo "</pre>";
