<?php

session_start();
require('../dbconnect.php');

if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
    // ログインしている
    $_SESSION['time'] = time();

    $members = $db->prepare('SELECT * FROM members WHERE id=?');
    $members->execute(array($_SESSION['id']));
    $member = $members->fetch();
} else {
    // ログインしていない
    header('Location: ../login.php');
    exit();
}

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

    //リツイート済の投稿だった場合もどる
    if ($retweetInt > 0) {
        header('Location:../index.php');
        exit();
    }
}
if (empty($_REQUEST['id'])) {
    header('Location:../index.php');
    exit();
}

// 投稿を取得する
$posts = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id AND p.id=?');
$posts->execute(array($_REQUEST['id']));
$getPosts = $posts->fetch();
// 投稿を記録する

$message = $db->prepare('INSERT INTO posts SET member_id=?, message=?, retweeted_post_id=?, push_retweet_id=?, created=NOW()');
$message->execute(array(
    $member['id'],
    $getPosts['message'],
    $getPosts['id'],
    $member['id']
));

header('Location:../index.php');
exit();
