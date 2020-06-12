<?php
$dsn = 'mysql:dbname=test;host=mysql';
$dbuser = 'test';
$dbpassword = 'test';
try {
    $db = new PDO($dsn,$dbuser,$dbpassword);
} catch (PDOException $e){
    http_response_code(500);
    echo '[' . http_response_code() . ']';    
}

$limit = $_GET['target'];
if (preg_match("/^[0-9]+$/",$limit) && !preg_match("/^[0]/",$limit) && $limit >= 1) {
    $limit = (int)$limit;
}else {
    http_response_code(400);
    echo '[' . http_response_code() . ']';
}

//数値の配列に変換 参照:https://www.softel.co.jp/blogs/tech/archives/6072
$sql = 'SELECT value FROM prechallenge3';
$tempValues = $db->query($sql)->fetchAll(PDO::FETCH_COLUMN);
$values = [];
foreach ($tempValues as $makeInt) {
    $values[] = (int) $makeInt;
}
//参考:https://stabucky.com/wp/archives/2188
//このファンクションを繰り返すことで全組み合わせを出す
//array_unshiftにて$arrsに切り取ったものを挿入することを繰り返している？
function getCombination($array,$extract){
    $arrayCount = count($array);    
    if($arrayCount < $extract) {
        return;
    }elseif($extract === 1){
        for($i = 0;$i < $arrayCount;$i++){
        $arrs[$i]=array($array[$i]);
        }
    }elseif($extract > 1){
        $j = 0;
        for($i = 0;$i<$arrayCount - $extract + 1;$i++){
            $ts=getCombination(array_slice($array,$i + 1),$extract - 1);
            foreach($ts as $t){
            array_unshift($t,$array[$i]);
            $arrs[$j] = $t;
            $j++;
            }
        }
    }
    return $arrs;
}

$valueCount = count($values);
//空のcombinationsを用意
$combinations = array();
//抜き取りは１つ以上以下存在しないので$i = 1にてcombinarion関数を使う
for($i = 1;$i <= $valueCount;$i++) {
    $temps = getCombination($values,$i);
    foreach($temps as $temp) {
        if(array_sum($temp) === $limit) {
            //参考資料だとimplodeだが、int型なのでarray_push→これによって組み合わせを包括した組み合わせができる
            array_push($combinations,$temp);
        }
    }
}

echo json_encode($combinations);
?>