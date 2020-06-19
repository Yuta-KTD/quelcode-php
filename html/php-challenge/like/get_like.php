<?php
session_start();
require('../dbconnect.php');
if (isset($_SESSION['id'])) {
    // いいねがログインされたユーザーからか調べる

    $likes = $db->prepare('SELECT COUNT(liked_post_id) AS like_cnt FROM likes WHERE liked_post_id = ? AND push_member_id = ?');
    $likes->execute(array(
        $_REQUEST['id'],
        $_SESSION['id']
    ));
    $like = $likes->fetch(PDO::FETCH_COLUMN);
    //like_cntを数値に変換
    $likeInt = (int) $like;

    //いいねの登録
    if ($likeInt === 0) {

        $get_like = $db->prepare('INSERT INTO likes SET liked_post_id = ?, push_member_id = ?, created=NOW()');
        $get_like->execute(array(
            //いいねされる人のID
            $_REQUEST['id'],
            //いいねする人のID（ログイン時のID）
            $_SESSION['id']
        ));
    }

    //いいねする投稿にリツイートIDがあるか調べる
    $checkRetweeted = $db->prepare('SELECT message, retweeted_post_id FROM posts WHERE id = ?');
    $checkRetweeted->execute(array(
        $_REQUEST['id']
    ));
    $checkRe = $checkRetweeted->fetch(PDO::FETCH_ASSOC);

    if ($checkRe === false) {
        header('Location:../index.php?');
        exit();
    }
    $checkReId = (int) $checkRe['retweeted_post_id'];

    if ($checkReId > 0) {
        //リツイートの投稿が編集されていないリツイートか調べる
        $checkPost = $db->prepare('SELECT message FROM posts WHERE id = ?');
        $checkPost->execute(array(
            //リツイート先の投稿
            $_REQUEST['id']
        ));
        $checkP = $checkPost->fetchAll(PDO::FETCH_COLUMN);



        $checkRetweetPost = $db->prepare('SELECT message FROM posts WHERE id = ?');
        $checkRetweetPost->execute(array(
            //リツイート先の投稿
            $checkReId
        ));
        $checkRP = $checkRetweetPost->fetchAll(PDO::FETCH_COLUMN);


        if ($checkP === $checkRP) {
            $get_like_r = $db->prepare('INSERT INTO likes SET liked_post_id = ?, push_member_id = ?, created=NOW()');
            $get_like_r->execute(array(
                //いいねされる人のID(リツイート元の投稿)
                $checkReId,
                //いいねする人のID（ログイン時のID）
                $_SESSION['id']
            ));
        }
    }
}


header('Location:../index.php?');
exit();
