<?php
session_start();
require('../dbconnect.php');
if (isset($_SESSION['id'])) {
    //いいねの登録
    //いいねする投稿にリツイートIDがあるか調べる
    $checkRetweeted = $db->prepare('SELECT retweeted_post_id FROM posts WHERE id = ?');
    $checkRetweeted->execute(array(
        $_REQUEST['id']
    ));
    $checkRetweet = $checkRetweeted->fetch();
    if ($checkRetweet === false) {
        header('Location:../index.php?');
        exit();
    }
    $checkRetweetId = (int) $checkRetweet['retweeted_post_id'];
    if ($checkRetweetId > 0) {
        //リツイート投稿をいいねしようとしている場合
        $getLikeRetweet = $db->prepare('INSERT INTO likes SET liked_post_id = ?, push_member_id = ?, created=NOW()');
        $getLikeRetweet->execute(array(
            //いいねされる人のID(リツイート元の投稿)
            $checkRetweetId,
            //いいねする人のID（ログイン時のID）
            $_SESSION['id']
        ));
    } else {
        //リツイート投稿でないとき
        $getLike = $db->prepare('INSERT INTO likes SET liked_post_id = ?, push_member_id = ?, created=NOW()');
        $getLike->execute(array(
            //いいねされる人のID
            $_REQUEST['id'],
            //いいねする人のID（ログイン時のID）
            $_SESSION['id']
        ));
    }
}
header('Location:../index.php?');
exit();
