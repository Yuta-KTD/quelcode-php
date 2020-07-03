<?php
session_start();
require('dbconnect.php');

if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
	// ログインしている
	$_SESSION['time'] = time();
	$members = $db->prepare('SELECT * FROM members WHERE id=?');
	$members->execute(array($_SESSION['id']));
	$member = $members->fetch();
} else {
	// ログインしていない
	header('Location: login.php');
	exit();
}
// 投稿を記録する
if (!empty($_POST)) {
	if ($_POST['message'] != '') {
		$message = $db->prepare('INSERT INTO posts SET member_id=?, message=?, reply_post_id=?, created=NOW()');
		$message->execute(array(
			$member['id'],
			$_POST['message'],
			$_POST['reply_post_id']
		));

		header('Location: index.php');
		exit();
	}
}
// 投稿を取得する
$page = $_REQUEST['page'];
if ($page == '') {
	$page = 1;
}
$page = max($page, 1);
// 最終ページを取得する
$counts = $db->query('SELECT COUNT(*) AS cnt FROM posts');
$cnt = $counts->fetch();
$maxPage = ceil($cnt['cnt'] / 5);
$page = min($page, $maxPage);
$start = ($page - 1) * 5;
$start = max(0, $start);
$posts = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id ORDER BY p.created DESC LIMIT ?, 5');
$posts->bindParam(1, $start, PDO::PARAM_INT);
$posts->execute();
//いいね済かのチェック
$pushLike = $db->prepare('SELECT liked_post_id FROM likes WHERE push_member_id=?');
$pushLike->bindParam(1, $_SESSION['id'], PDO::PARAM_INT);
$pushLike->execute();
$pushLikes = array();
foreach ($pushLike as $pushL) {
	$pushLikes[] = $pushL;
}
//リツイートのチェック
$pushRetweets = $db->prepare('SELECT origin_retweet_post_id FROM posts WHERE member_id=?');
$pushRetweets->bindParam(1, $_SESSION['id'], PDO::PARAM_INT);
$pushRetweets->execute();
$pushRet = array();
foreach ($pushRetweets as $pRet) {
	$pushRet[] = $pRet;
}
//リツイートのチェック
$retweetCheck = $db->prepare('SELECT retweeted_post_id FROM posts WHERE member_id = ?');
$retweetCheck->bindParam(1, $_SESSION['id'], PDO::PARAM_INT);
$retweetCheck->execute();
$retweetCh = array();
foreach ($retweetCheck as $retweetChecks) {
	$retweetCh[] = $retweetChecks;
}

// 返信の場合
if (isset($_REQUEST['res'])) {
	$response = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id AND p.id=? ORDER BY p.created DESC');
	$response->execute(array($_REQUEST['res']));

	$table = $response->fetch();
	$message = '@' . $table['name'] . ' ' . $table['message'];
}

// htmlspecialcharsのショートカット
function h($value)
{
	return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// 本文内のURLにリンクを設定します
function makeLink($value)
{
	return mb_ereg_replace("(https?)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)", '<a href="\1\2">\1\2</a>', $value);
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<script src="https://kit.fontawesome.com/413ad63a84.js" crossorigin="anonymous"></script>
	<title>ひとこと掲示板</title>

	<link rel="stylesheet" href="style.css" />
</head>

<body>
	<div id="wrap">
		<div id="head">
			<h1>ひとこと掲示板</h1>
		</div>
		<div id="content">
			<div style="text-align: right"><a href="logout.php">ログアウト</a></div>
			<form action="" method="post">
				<dl>
					<dt><?php echo h($member['name']); ?>さん、メッセージをどうぞ</dt>
					<dd>
						<textarea name="message" cols="50" rows="5"><?php echo h($message); ?></textarea>
						<input type="hidden" name="reply_post_id" value="<?php echo h($_REQUEST['res']); ?>" />
					</dd>
				</dl>
				<div>
					<p>
						<input type="submit" value="投稿する" />
					</p>
				</div>
			</form>
			<?php
			foreach ($posts as $post) :
			?>
				<div class="msg">
					<?php

					$pushRtId =  (int) $post['push_retweet_id'];
					//リツイート投稿の投稿表示
					if ($pushRtId > 0) {
						$postsRetweet = $db->prepare('SELECT m.name, m.picture FROM members m, posts p WHERE m.id=p.member_id AND p.id = ?');
						$postsRetweet->execute(array($post['retweeted_post_id']));
						$postRetweet = $postsRetweet->fetch();
						$retweetName = $post['name'];
						$post['picture'] = $postRetweet['picture'];
						$post['name'] = $postRetweet['name'];
					}
					?>
					<img src="member_picture/<?php echo h($post['picture']); ?>" width="48" height="48" alt="<?php echo h($post['name']); ?>" />
					<p><?php echo makeLink(h($post['message'])); ?><span class="name">（<?php echo h($post['name']); ?>）</span>[<a href="index.php?res=<?php echo h($post['id']); ?>">Re</a>]
						<!-- リツイートを押したidが存在するとき(押したid)が０以上のとき、リツイート投稿であることを表示する -->

						<?php
						if ($pushRtId > 0) :
						?>
							<span class="retweet-post"><?php echo h($retweetName) ?>のリツイート投稿</span>
						<?php
						endif;
						?>
					</p>
					<p class="day"><a href="view.php?id=<?php echo h($post['id']); ?>"><?php echo h($post['created']); ?></a>
						<?php
						if ($post['reply_post_id'] > 0) :
						?>
							<a href="view.php?id=<?php echo
														h($post['reply_post_id']); ?>">
								返信元のメッセージ</a>
						<?php
						endif;
						?>
						<?php
						//いいね数
						$likesPost = $db->prepare('SELECT COUNT(liked_post_id) AS like_cnt FROM likes WHERE liked_post_id =?');
						$likesPost->bindParam(1, $post['id'], PDO::PARAM_INT);
						$likesPost->execute();
						$likesPosts = $likesPost->fetch();

						//リツイート先のいいね数
						$likesPostRetweet = $db->prepare('SELECT liked_post_id, push_member_id, COUNT(liked_post_id) AS like_cnt FROM likes WHERE liked_post_id =?');
						$likesPostRetweet->bindParam(1, $post['retweeted_post_id'], PDO::PARAM_INT);
						$likesPostRetweet->execute();
						$likesPostsRetweet = $likesPostRetweet->fetch();

						//リツイート先投稿でのリツイートしているかの確認
						$pushRtId =  (int) $post['push_retweet_id'];
						$originRtPost = (int) $post['origin_retweet_post_id'];
						?>
						<?php
						//参考：https://qiita.com/blacklions20/items/ffa0354e625c43c95582
						//初期化している
						$haveLike = 0;
						$haveLikeRetweet = 0;
						//いいねにおけるリツイート投稿とそうでない時の場合わけ
						for ($i = 0; $i < count($pushLikes); $i++) {
							if ($pushLikes[$i]['liked_post_id'] === $post['id']) {
								$haveLike = $post['id'];
							} else if ($pushLikes[$i]['liked_post_id'] === $post['retweeted_post_id']) {
								$haveLikeRetweet =  $post['retweeted_post_id'];
							}
						}
						//リツイート数
						$retweetSql = 'SELECT COUNT(*) FROM posts WHERE retweeted_post_id =?';
						$retweetPostOrigin = $db->prepare($retweetSql);
						$retweetPostOrigin->bindParam(1, $post['id'], PDO::PARAM_INT);
						$retweetPostOrigin->execute();
						$retweetPostsOrigin = $retweetPostOrigin->fetchColumn();
						//リツイート先でのリツイート数
						$retweetPost = $db->prepare($retweetSql);
						$retweetPost->bindParam(1, $post['retweeted_post_id'], PDO::PARAM_INT);
						$retweetPost->execute();
						$retweetPosts = $retweetPost->fetchColumn();
						//毎回初期化している
						$haveRetweet = 0;
						//リツイート元投稿のリツイートしているかの確認
						for ($i = 0; $i < count($pushRet); $i++) {
							if ($pushRet[$i]['origin_retweet_post_id'] === $post['id']) {
								$haveRetweet = $post['id'];
							}
						}
						//リツイート投稿をリツイートされた時の検索方法
						$retweetC = 0;
						$retweetCR = 0;
						for ($i = 0; $i < count($retweetCh); $i++) {
							if ($retweetCh[$i]['retweeted_post_id'] === $post['id']) {

								$retweetCR = $post['id'];
							} else if ($retweetCh[$i]['retweeted_post_id'] === $post['retweeted_post_id']) {
								$retweetC = $post['retweeted_post_id'];
							}
						}
						//リツイート判定
						if ($pushRtId > 0 && $_SESSION['id'] === $post['id']) {
							$myRetweetedPostId = $db->prepare('SELECT retweeted_post_id FROM posts WHERE member_id = ?');
							$myRetweetedPostId->bindParam(1, $_SESSION['id'], PDO::PARAM_INT);
							$myRetweetedPostId->execute();
							$myRetweetedPostsId = $myRetweetedPostId->fetch();
						}
						?>
						<!-- いいねここから -->
						<?php
						if ($pushRtId > 0) :
						?>
							<!-- リツイート投稿の場合 -->
							<?php
							if ($haveLikeRetweet === $post['retweeted_post_id']) :
							?>
								<!-- いいねしていた場合（いいね削除) -->
								<a class="like" href="like/delete_like.php?id=<?php echo h($post['id']); ?>"><i class="far fa-heart heart-red"></i></a>
							<?php
							else :
							?>
								<!-- いいねしていなかった場合(いいね登録) -->
								<a class="like" href="like/get_like.php?id=<?php echo h($post['id']); ?>"><i class="far fa-heart"></i></a>
							<?php
							endif;
							?>
							<!-- いいね数の表示（リツイート） -->
							<?php echo h($likesPostsRetweet['like_cnt']); ?>
						<?php
						else :
						?>
							<!-- リツイート投稿でない場合 -->
							<?php
							if ($haveLike > 0) :
							?>
								<!-- いいねしていた場合（いいね削除) -->
								<a class="like" href="like/delete_like.php?id=<?php echo h($post['id']); ?>"><i class="far fa-heart heart-red"></i></a>

							<?php
							else :
							?>
								<!-- いいねしていなかった場合(いいね登録) -->
								<a class="like" href="like/get_like.php?id=<?php echo h($post['id']); ?>"><i class="far fa-heart"></i></a>
							<?php
							endif;
							?>
							<!-- いいね数の表示 -->
							<?php echo h($likesPosts['like_cnt']); ?>
						<?php
						endif;
						?>
						<!-- リツイート -->
						<?php
						if ($haveRetweet > 0 || $retweetC > 0 || $retweetCR > 0) :
						?>
							<a class="retweet" href="retweet/delete_retweet.php?id=<?php echo h($post['id']); ?>"><i class="fas fa-retweet retweet-blue"></i></a>
						<?php
						else :
						?>
							<a class="retweet" href="retweet/get_retweet.php?id=<?php echo h($post['id']); ?>"><i class="fas fa-retweet"></i></a>
						<?php
						endif;
						?>
						<!-- リツイート数の表示 -->
						<?php
						if ($retweetPostsOrigin > 0) {
							echo h($retweetPostsOrigin);
						} else if ($pushRtId > 0) {
							echo h($retweetPosts);
						} else {
							echo 0;
						}
						?>
						<?php
						if ($_SESSION['id'] === $post['member_id']) :
						?>
							[<a href="delete.php?id=<?php echo h($post['id']); ?>" style="color: #F33;">削除</a>]
						<?php
						endif;
						?>
					<?php
				endforeach;
					?>
					</p>
				</div>
				<ul class="paging">
					<?php
					if ($page > 1) {
					?>
						<li><a href="index.php?page=<?php print($page - 1); ?>">前のページへ</a></li>
					<?php
					} else {
					?>
						<li>前のページへ</li>
					<?php
					}
					?>
					<?php
					if ($page < $maxPage) {
					?>
						<li><a href="index.php?page=<?php print($page + 1); ?>">次のページへ</a></li>
					<?php
					} else {
					?>
						<li>次のページへ</li>
					<?php
					}
					?>
				</ul>
		</div>
	</div>
</body>

</html>