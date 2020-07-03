<?php
session_start();
require('dbconnect.php');

if (isset($_SESSION['id'])) {
	$id = $_REQUEST['id'];

	// 投稿を検査する
	$messages = $db->prepare('SELECT * FROM posts WHERE id=?');
	$messages->execute(array($id));
	$message = $messages->fetch();

	$likes = $db->prepare('SELECT * FROM likes WHERE liked_post_id = ?');
	$likes->execute(array($id));
	$like = $likes->fetch();

	if ($message['retweeted_post_id'] === 0) {
		//リツイート元、またはリツイート無しの投稿の場合
		// 削除する
		$delM = $db->prepare('DELETE FROM posts WHERE id=?');
		$delM->execute(array(
			$id
		));
		// その投稿のいいねを削除する
		$delL = $db->prepare('DELETE FROM likes WHERE liked_post_id = ?');
		$delL->execute(array(
			$id
		));
		//リツイートも削除する
		$delR = $db->prepare('DELETE FROM posts WHERE retweeted_post_id = ?');
		$delR->execute(array(
			$_REQUEST['id']
		));
	} else {
		//リツイート投稿の場合
		$delMRetweet = $db->prepare('DELETE FROM posts WHERE id=?');
		$delMRetweet->execute(array(
			$message['retweeted_post_id']
		));
		// その投稿のいいねを削除する
		$delLRetweet = $db->prepare('DELETE FROM likes WHERE liked_post_id = ?');
		$delLRetweet->execute(array(
			$message['retweeted_post_id']
		));
		//リツイートも削除する
		$delRRetweet = $db->prepare('DELETE FROM posts WHERE retweeted_post_id = ?');
		$delRRetweet->execute(array(
			$message['retweeted_post_id']
		));
	}
}

header('Location: index.php');
exit();
