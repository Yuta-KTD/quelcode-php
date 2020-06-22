<?php
session_start();
require('../dbconnect.php');

$id = $_REQUEST['id'];

//投稿がリツイート先の投稿の場合 $postRI > 0 となる
$post = $db->prepare('SELECT retweeted_post_id FROM posts WHERE id = ?');
$post->bindParam(1, $id, PDO::PARAM_INT);
$post->execute();
$postRI = $post->fetchColumn();

if (isset($_SESSION['id'])) {

    if ($postRI > 0) {
        //リツイート先でボタンを押した場合
        $delM = $db->prepare('DELETE FROM posts WHERE id=?');
        $delM->execute(array($id));
        $delL = $db->prepare('DELETE FROM likes WHERE liked_post_id = ?');
        $delL->execute(array($id));
    } else {
        //リツイート元にてボタンを押した場合
        $del_retweet = $db->prepare('DELETE FROM posts WHERE retweeted_post_id = ? AND push_retweet_id = ?');
        $del_retweet->execute(array(
            $_REQUEST['id'],
            $_SESSION['id']
        ));
    }
}

header('Location:../index.php');
exit();
