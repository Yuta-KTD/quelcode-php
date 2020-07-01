<?php
session_start();
require('../dbconnect.php');

$id = $_REQUEST['id'];
//投稿がリツイート先の投稿の場合
$posts = $db->prepare('SELECT member_id,retweeted_post_id, push_retweet_id FROM posts WHERE id = ?');
$posts->bindParam(1, $id, PDO::PARAM_INT);
$posts->execute();
$post = $posts->fetch();
$postRI = (int) $post['retweeted_post_id'];

//リツイート先で、かつログインユーザーのリツイート投稿でない場合

if (isset($_SESSION['id'])) {

    if ($postRI > 0) {
        //リツイート先でボタンを押した場合
        if ($_SESSION['id'] === $post['member_id']) {
            $delM = $db->prepare('DELETE FROM posts WHERE id=?');
            $delM->execute(array($id));
            $delL = $db->prepare('DELETE FROM likes WHERE liked_post_id = ?');
            $delL->execute(array($id));
        } else {
            $delMRtweet = $db->prepare('DELETE FROM posts WHERE retweeted_post_id = ? AND member_id = ?');
            $delMRtweet->execute(array(
                $postRI,
                $_SESSION['id']
            ));
            $delLRetweet = $db->prepare('DELETE FROM likes WHERE liked_post_id = ? AND push_member_id = ?');
            $delLRetweet->execute(array(
                $postRI,
                $_SESSION['id']
            ));
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
