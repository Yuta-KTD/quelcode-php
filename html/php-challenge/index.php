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
$pushMessages_sql = 'SELECT liked_post_id FROM likes WHERE push_member_id=?';
$pushMessages = $db->prepare($pushMessages_sql);
$pushMessages->bindParam(1, $_SESSION['id'], PDO::PARAM_INT);
$pushMessages->execute();
$pushMsg = array();
foreach ($pushMessages as $pMsg) {
	$pushMsg[] = $pMsg;
}
//リツイート済かのチェック
$pushRetweets = $db->prepare('SELECT retweeted_post_id FROM posts WHERE push_retweet_id=?');
$pushRetweets->bindParam(1, $_SESSION['id'], PDO::PARAM_INT);
$pushRetweets->execute();
$pushRet = array();
foreach ($pushRetweets as $pRet) {
	$pushRet[] = $pRet;
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
					<img src="member_picture/<?php echo h($post['picture']); ?>" width="48" height="48" alt="<?php echo h($post['name']); ?>" />
					<p><?php echo makeLink(h($post['message'])); ?><span class="name">（<?php echo h($post['name']); ?>）</span>[<a href="index.php?res=<?php echo h($post['id']); ?>">Re</a>]
						<!-- リツイートを押したidが存在するとき(押したid)が０以上のとき、リツイート投稿であることを表示する -->
						<?php
						$postRtId = (int) $post['push_retweet_id'];
						?>
						<?php
						if ($postRtId > 0) :
						?>
							<span class="retweet-post">リツイート投稿</span>
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
						$likes_sql = 'SELECT COUNT(liked_post_id) AS like_cnt FROM likes WHERE liked_post_id =?';
						$likes_post = $db->prepare($likes_sql);
						$likes_post->bindParam(1, $post['id'], PDO::PARAM_INT);
						$likes_post->execute();
						$likes_posts = $likes_post->fetch();
						//リツイート先のいいね数
						$likes_post_retweet = $db->prepare('SELECT liked_post_id, push_member_id, COUNT(liked_post_id) AS like_cnt FROM likes WHERE liked_post_id =?');
						$likes_post_retweet->bindParam(1, $post['retweeted_post_id'], PDO::PARAM_INT);
						$likes_post_retweet->execute();
						$likes_posts_retweet = $likes_post_retweet->fetch();

						//リツイート先投稿でのリツイートしているかの確認
						$postRtId =  $post['push_retweet_id'];
						?>
						<?php
						//参考：https://qiita.com/blacklions20/items/ffa0354e625c43c95582
						//初期化している
						$haveLike = 0;
						$haveLike_r = 0;
						//いいねにおけるリツイート投稿とそうでない時の場合わけ
						for ($i = 0; $i < count($pushMsg); $i++) {
							if ($pushMsg[$i]['liked_post_id'] === $post['id']) {
								$haveLike = $post['id'];
							} else if ($pushMsg[$i]['liked_post_id'] === $post['retweeted_post_id']) {
								$haveLike_r =  $post['retweeted_post_id'];
							}
						}
						//リツイート数
						$retweet_sql = 'SELECT COUNT(*) FROM posts WHERE retweeted_post_id =?';
						$retweet_post = $db->prepare($retweet_sql);
						$retweet_post->bindParam(1, $post['id'], PDO::PARAM_INT);
						$retweet_post->execute();
						$retweet_posts = $retweet_post->fetchColumn();
						//リツイート先でのリツイート数
						$retweetPost = $db->prepare($retweet_sql);
						$retweetPost->bindParam(1, $post['retweeted_post_id'], PDO::PARAM_INT);
						$retweetPost->execute();
						$retweetPosts = $retweetPost->fetchColumn();
						//毎回初期化している
						$haveRetweet = 0;
						//リツイート元投稿のリツイートしているかの確認
						for ($i = 0; $i < count($pushRet); $i++) {
							if ($pushRet[$i]['retweeted_post_id'] === $post['id']) {
								$haveRetweet = $post['id'];
							}
						}
						$haveRetweet_post = $db->prepare($retweet_sql);
						$haveRetweet_post->bindParam(1, $post['id'], PDO::PARAM_INT);
						$haveRetweet_post->execute();
						$haveRetweet_posts = $haveRetweet_post->fetch();
						?>
						<!-- いいねここから -->
						<?php
						if ($postRtId > 0) :
						?>
							<!-- リツイート投稿の場合 -->
							<?php
							if ($haveLike_r === $post['retweeted_post_id']) :
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
							<?php echo h($likes_posts_retweet['like_cnt']); ?>
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
							<?php echo h($likes_posts['like_cnt']); ?>
						<?php
						endif;
						?>
						<!-- リツイート -->
						<?php
						if ($haveRetweet > 0 || $postRtId === $_SESSION['id']) :
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
						if ($retweet_posts > 0) {
							echo h($retweet_posts);
						}
						if ($postRtId > 0) {
							echo h($retweetPosts);
						}
						?>
						<?php
						if ($_SESSION['id'] == $post['member_id']) :
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