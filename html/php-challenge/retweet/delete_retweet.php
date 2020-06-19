<?php
session_start();
require('../dbconnect.php');

if (isset($_SESSION['id'])) {
    // リツイートがログインされたユーザーからか調べる
    $retweets = $db->prepare('SELECT COUNT(retweeted_post_id) AS retweet_cnt FROM posts WHERE retweeted_post_id = ? AND push_retweet_id = ?');
    $retweets->execute(array(
        $_REQUEST['id'],
        $_SESSION['id']
    ));
    $retweet = $retweets->fetch(PDO::FETCH_COLUMN);
    //数値に変換
    $retweetInt = (int) $retweet;

    //いいねの消去
    if ($retweetInt > 0) {
        $del_retweet = $db->prepare('DELETE FROM posts WHERE retweeted_post_id = ? AND push_retweet_id = ?');
        $del_retweet->execute(array(
            $_REQUEST['id'],
            $_SESSION['id']
        ));
    }
}

header('Location:../index.php');
exit();
