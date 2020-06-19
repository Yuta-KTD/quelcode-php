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

    //いいねの消去
    if ($likeInt > 0) {
        $del_like = $db->prepare('DELETE FROM likes WHERE liked_post_id = ? AND push_member_id = ?');
        $del_like->execute(array(
            $_REQUEST['id'],
            $_SESSION['id']
        ));
    }
    //削除投稿にリツイートIDがあるか調べる
    $checkRetweeted = $db->prepare('SELECT message, retweeted_post_id FROM posts WHERE id = ?');
    $checkRetweeted->execute(array(
        $_REQUEST['id']
    ));
    $checkRe = $checkRetweeted->fetch(PDO::FETCH_ASSOC);

    if ($checkRetweeted === false) {
        header('Location:../index.php?');
        exit();
    }

    $checkReId = (int) $checkRe['retweeted_post_id'];
    if ($checkRetweeted > 0) {
        //リツイートの投稿が編集されていないリツイートか調べる
        $checkPost = $db->prepare('SELECT message FROM posts WHERE id = ?');
        $checkPost->execute(array(
            //リツイート先の投稿
            $_REQUEST['id']
        ));
        $checkP = $checkPost->fetch(PDO::FETCH_ASSOC);

        $checkRetweetPost = $db->prepare('SELECT message FROM posts WHERE id = ?');
        $checkRetweetPost->execute(array(
            //リツイート先の投稿
            $checkReId
        ));
        $checkRP = $checkRetweetPost->fetch(PDO::FETCH_ASSOC);


        if ($checkP === $checkRP) {
            $get_like = $db->prepare('DELETE FROM likes WHERE liked_post_id = ? AND push_member_id = ?');
            $get_like->execute(array(
                //削除される人のID
                $checkReId,
                //削除する人のID（ログイン時のID）
                $_SESSION['id']
            ));
        }
    }
}

$indexPage = $_REQUEST['page'];
$indexUrl = '../index.php?page=' . print($page);
header('Location:' . $indexUrl);
exit();
