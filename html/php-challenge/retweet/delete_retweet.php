<?php
session_start();
require('../dbconnect.php');

$id = $_REQUEST['id'];
//投稿がリツイート先の投稿の場合
$post = $db->prepare('SELECT member_id,retweeted_post_id, push_retweet_id FROM posts WHERE id = ?');
$post->bindParam(1, $id, PDO::PARAM_INT);
$post->execute();
$postRI = $post->fetch();

if (isset($_SESSION['id'])) {

    if ($postRI['retweeted_post_id'] > 0) {
        //リツイート先でボタンを押した場合
        if ($_SESSION['id'] === $postRI['member_id']) {
            $delM = $db->prepare('DELETE FROM posts WHERE id=?');
            $delM->execute(array($id));
            $delL = $db->prepare('DELETE FROM likes WHERE liked_post_id = ?');
            $delL->execute(array($id));
        } else {
            $delMRtweet = $db->prepare('DELETE FROM posts WHERE origin_retweet_post_id=?');
            $delMRtweet->execute(array($id));
            $delLRetweet = $db->prepare('DELETE FROM likes WHERE liked_post_id = ?');
            $delLRetweet->execute(array($id));
        }
    } else {
        //リツイート元にてボタンを押した場合
        $del_retweet = $db->prepare('DELETE FROM posts WHERE retweeted_post_id = ? AND member_id = ?');
        $del_retweet->execute(array(
            $_REQUEST['id'],
            $_SESSION['id']
        ));
    }
}

header('Location:../index.php');
exit();
