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
    $checkRe = $checkRetweeted->fetch();
    if ($checkRe === false) {
        header('Location:../index.php?');
        exit();
    }
    $checkReId = (int) $checkRe['retweeted_post_id'];
    if ($checkReId > 0) {
        //リツイート投稿をいいねしようとしている場合
        $get_like_r = $db->prepare('INSERT INTO likes SET liked_post_id = ?, push_member_id = ?, created=NOW()');
        $get_like_r->execute(array(
            //いいねされる人のID(リツイート元の投稿)
            $checkReId,
            //いいねする人のID（ログイン時のID）
            $_SESSION['id']
        ));
    } else {
        //リツイート投稿でないとき
        $get_like = $db->prepare('INSERT INTO likes SET liked_post_id = ?, push_member_id = ?, created=NOW()');
        $get_like->execute(array(
            //いいねされる人のID
            $_REQUEST['id'],
            //いいねする人のID（ログイン時のID）
            $_SESSION['id']
        ));
    }
}
header('Location:../index.php?');
exit();
