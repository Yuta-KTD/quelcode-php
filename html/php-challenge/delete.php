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
		$deleteMessage = $db->prepare('DELETE FROM posts WHERE id=?');
		$deleteMessage->execute(array(
			$id
		));
		// その投稿のいいねを削除する
		$deleteLike = $db->prepare('DELETE FROM likes WHERE liked_post_id = ?');
		$deleteLike->execute(array(
			$id
		));
		//リツイートも削除する
		$deleteRetweet = $db->prepare('DELETE FROM posts WHERE retweeted_post_id = ?');
		$deleteRetweet->execute(array(
			$_REQUEST['id']
		));
	} else {
		//リツイート投稿の場合
		$deleteMessageRetweet = $db->prepare('DELETE FROM posts WHERE id=?');
		$deleteMessageRetweet->execute(array(
			$message['retweeted_post_id']
		));
		// その投稿のいいねを削除する
		$deleteLikeRetweet = $db->prepare('DELETE FROM likes WHERE liked_post_id = ?');
		$deleteLikeRetweet->execute(array(
			$message['retweeted_post_id']
		));
		//リツイートも削除する
		$deleteRetweetLine = $db->prepare('DELETE FROM posts WHERE retweeted_post_id = ?');
		$deleteRetweetLine->execute(array(
			$message['retweeted_post_id']
		));
	}
}

header('Location: index.php');
exit();
