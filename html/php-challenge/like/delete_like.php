<?php
session_start();
require('../dbconnect.php');

if (isset($_SESSION['id'])) {

    $del_like = $db->prepare('DELETE FROM likes WHERE liked_post_id = ? AND push_member_id = ?');
    $del_like->execute(array(
        $_REQUEST['id'],
        $_SESSION['id']
    ));
    //削除投稿にリツイートIDがあるか調べる
    $checkRetweeted = $db->prepare('SELECT * FROM posts WHERE id = ?');
    $checkRetweeted->execute(array(
        $_REQUEST['id']
    ));
    $checkRe = $checkRetweeted->fetch(PDO::FETCH_ASSOC);

    if ($checkRetweeted === false) {
        header('Location:../index.php?');
        exit();
    }
    $checkReId = (int) $checkRe['retweeted_post_id'];
    if ($checkReId > 0) {

        $get_like = $db->prepare('DELETE FROM likes WHERE liked_post_id = ? AND push_member_id = ?');
        $get_like->execute(array(
            //削除される人のID
            $checkReId,
            //削除する人のID（ログイン時のID）
            $_SESSION['id']
        ));
    }
}
$indexPage = $_REQUEST['page'];
$indexUrl = '../index.php?page=' . print($page);
header('Location:' . $indexUrl);
exit();
