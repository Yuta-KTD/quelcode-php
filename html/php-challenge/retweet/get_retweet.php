<?php

session_start();
require('../dbconnect.php');

if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
    // ログインしている
    $_SESSION['time'] = time();

    $members = $db->prepare('SELECT * FROM members WHERE id=?');
    $members->execute(array($_SESSION['id']));
    $member = $members->fetch();
} else {
    // ログインしていない
    header('Location: ../login.php');
    exit();
}

if (isset($_SESSION['id'])) {
    // リツイートがログインされたユーザーからか調べる
    $retweets = $db->prepare('SELECT COUNT(retweeted_post_id) AS retweet_cnt FROM posts WHERE retweeted_post_id = ? AND push_retweet_id = ?');
    $retweets->execute(array(
        $_REQUEST['id'],
        $_SESSION['id']
    ));
    $retweet = $retweets->fetch(PDO::FETCH_COLUMN);
    //数値に変換
    $retweetInt = (int) $retweet;

    //リツイート済の投稿だった場合もどる
    if ($retweetInt > 0) {
        header('Location:../index.php');
        exit();
    }
}
if (empty($_REQUEST['id'])) {
    header('Location:../index.php');
    exit();
}

// 投稿を取得する
$posts = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id AND p.id=? ORDER BY p.created DESC');
$posts->execute(array($_REQUEST['id']));


// 投稿を記録する
if (!empty($_POST)) {
    if ($_POST['message'] != '') {
        $message = $db->prepare('INSERT INTO posts SET member_id=?, message=?, retweeted_post_id=?, push_retweet_id=?, created=NOW()');
        $message->execute(array(
            $member['id'],
            $_POST['message'],
            $_POST['retweeted_post_id'],
            $_POST['push_retweet_id']
        ));

        header('Location:../index.php');
        exit();
    }
}
// リツイート元投稿を表示する
$response = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id AND p.id=?');
$response->execute(array($_REQUEST['id']));

$table = $response->fetch();
$message = $table['message'];

// htmlspecialcharsのショートカット
function h($value)
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <script src="https://kit.fontawesome.com/413ad63a84.js" crossorigin="anonymous"></script>
    <title>ひとこと掲示板 | リツイート作成</title>

    <link rel="stylesheet" href="../style.css" />
</head>

<body>
    <div id="wrap">
        <div id="head">
            <h1>ひとこと掲示板 | リツイートする</h1>
        </div>
        <div id="content">
            <form action="" method="post">
                <dl>
                    <dt><?php echo h($member['name']); ?>さん、メッセージをどうぞ（メッセージは記入しなくても投稿可能です）</dt>
                    <dd>
                        <textarea name="message" cols="50" rows="5"><?php echo h($message); ?></textarea>
                        <input type="hidden" name="retweeted_post_id" value="<?php echo h($_REQUEST['id']); ?>" />
                        <input type="hidden" name="push_retweet_id" value="<?php echo h($_SESSION['id']); ?>" />
                    </dd>
                </dl>
                <div>
                    <p>
                        <input type="submit" value="リツイートする" />
                        [<a href="../index.php" style="color: #F33;">キャンセル</a>]
                    </p>
                </div>
            </form>
        </div>
    </div>
</body>

</html>