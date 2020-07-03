<?php
session_start();
require('../dbconnect.php');

$id = $_REQUEST['id'];
//投稿がリツイート先の投稿の場合
$posts = $db->prepare('SELECT member_id,retweeted_post_id, push_retweet_id FROM posts WHERE id = ?');
$posts->bindParam(1, $id, PDO::PARAM_INT);
$posts->execute();
$post = $posts->fetch();
$retweetPostId = (int) $post['retweeted_post_id'];

//リツイート先で、かつログインユーザーのリツイート投稿でない場合

if (isset($_SESSION['id'])) {

    if ($retweetPostId > 0) {
        //リツイート先でボタンを押した場合
        if ($_SESSION['id'] === $post['member_id']) {
            //自身のリツイート
            $deleteMessage = $db->prepare('DELETE FROM posts WHERE id=?');
            $deleteMessage->execute(array($id));
            $deleteLike = $db->prepare('DELETE FROM likes WHERE liked_post_id = ?');
            $deleteLike->execute(array($id));
        } else {
            //他ユーザーのリツイート
            $deleteMessageRetweet = $db->prepare('DELETE FROM posts WHERE retweeted_post_id = ? AND member_id = ?');
            $deleteMessageRetweet->execute(array(
                $retweetPostId,
                $_SESSION['id']
            ));
            $deleteLikeRetweet = $db->prepare('DELETE FROM likes WHERE liked_post_id = ? AND push_member_id = ?');
            $deleteLikeRetweet->execute(array(
                $retweetPostId,
                $_SESSION['id']
            ));
        }
    } else {
        //リツイート元にてボタンを押した場合
        $deleteRetweet = $db->prepare('DELETE FROM posts WHERE retweeted_post_id = ? AND member_id = ?');
        $deleteRetweet->execute(array(
            $_REQUEST['id'],
            $_SESSION['id']
        ));
    }
}

header('Location:../index.php');
exit();
