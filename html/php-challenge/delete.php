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

	if ($message['member_id'] == $_SESSION['id']) {
		// 削除する
		$delM = $db->prepare('DELETE FROM posts WHERE id=?');
		$delM->execute(array($id));
		// その投稿のいいねを削除する
		$delL = $db->prepare('DELETE FROM likes WHERE liked_post_id = ?');
		$delL->execute(array($id));
	}
}

header('Location: index.php');
exit();
