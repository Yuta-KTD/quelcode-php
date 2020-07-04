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
if (empty($_REQUEST['id'])) {
    header('Location:../index.php');
    exit();
}

// 投稿を取得する
$posts = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id AND p.id=?');
$posts->execute(array($_REQUEST['id']));
$getPosts = $posts->fetch();
$getRetweetedPostId = $getPosts['retweeted_post_id'];

if ($getRetweetedPostId > 0) {
    $posts->execute(array($getRetweetedPostId));
}


// 投稿を記録する
//投稿がリツイートされた投稿だった場合
if ($getRetweetedPostId > 0) {
    $retweetMessage = $db->prepare('INSERT INTO posts SET member_id=?, message=?, retweeted_post_id=?, push_retweet_id=?, origin_retweet_post_id = ?,created=NOW()');
    $retweetMessage->execute(array(
        $member['id'],
        $getPosts['message'],
        // リツイートの最初の投稿をたどる
        $getRetweetedPostId,
        $getPosts['member_id'],
        $_REQUEST['id']
    ));
} else {

    //リツイート投稿でない場合
    $message = $db->prepare('INSERT INTO posts SET member_id=?, message=?, retweeted_post_id=?, push_retweet_id=?,origin_retweet_post_id = ?,created=NOW()');
    $message->execute(array(
        $member['id'],
        $getPosts['message'],
        $getPosts['id'],
        $getPosts['member_id'],
        $_REQUEST['id']
    ));
}

header('Location:../index.php');
exit();
