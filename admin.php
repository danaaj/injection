<?
/*
Alex Dana
CSE467
Spring 2015
Simple web app used to investigate security flaws

*/


session_start();
require_once "creds.php";
//mysql_connect("localhost",$user,$password);
//mysql_select_db("injection");

$servername = "localhost";
$dbname = "injection";
$conn = new mysqli($servername, $user, $password, $dbname);
if ($conn->connect_error) {
	die ("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user']) || $_SESSION['level'] != 1) {	//make sure they are logged in and are an admin
	header("Location: index.php");
}


//perform commands
if (isset($_REQUEST['cmd'])) {
	$cmd = $_REQUEST['cmd'];
	if ($cmd=="update") {
		global $conn;
		$id = $_POST['id'];
		$title = $_POST['title'];
		$content = addslashes($_POST['content']);
		$title = htmlspecialchars($title, ENT_QUOTES);
		$content = htmlspecialchars($content, ENT_QUOTES);
		$sql = $conn->prepare("update data set title=?,content=? where id=?");
		$sql->bind_param("ssi", $title, $content, $id);
		$sql->execute();
		syslog(LOG_INFO, 'page updated');
	}
	if ($cmd=="new") {
		global $conn;
		$title = $_POST['title'];
		$content = addslashes($_POST['content']);
		$title = htmlspecialchars($title, ENT_QUOTES);
		$content = htmlspecialchars($content, ENT_QUOTES);
		$sql = $conn->prepare("insert into data (title,content) values (?, ?)");
		$sql->bind_param("ss", $title, $content);
		$sql->execute();
		syslog(LOG_INFO, 'new page added');
		print $conn->error;
	}

	if ($cmd=="clearcomments") {
		global $conn;
		$id=$_REQUEST['id'];
		$sql = $conn->prepare("delete from comments where fkpage=?");
		$sql->bind_param("i", $id);
		$sql->execute();
		syslog(LOG_INFO, 'comments cleared');
	}
	if ($cmd=="delete") {
		global $conn;
		$id=$_REQUEST['id'];
		$sql = $conn->prepare("delete from comments where fkpage=?");
		$sql->bind_param("i", $id);
		$sql->execute();
		print $conn->error;
		$sql->prepare("delete from data where id=?");
		$sql->bind_param("i", $id);
		$sql->execute();
		syslog(LOG_INFO, 'page deleted');
		print $conn->error;
	}
}
?>
<html>
<head>
<style>
<input type='hidden' name='csrfToken' value='<?php echo($_SESSION['csrfToken'])?>'/>
.item {
margin-bottom: 10px;
}
</style>
</head>
<body>
<a href='index.php'>Index</a>
<a href='login.php'>logout</a>
<div>Admin</div>
<?
$q=$conn->query("select * from data");
while ($r=mysqli_fetch_array($q, MYSQLI_NUM)) {
	print "<div class='item'><form method='post' action='admin.php'><input type='hidden' name='cmd' value='update'><input type='hidden' name='id' value='$r[0]'>";
?>
<div class='title'>title: <input type='text' name='title' value='<?print $r[1];?>'></div>
<div class='content'>content: <textarea rows='10' cols='80' name='content'><?print $r[2];?></textarea></div>
<input type='submit' name='update'>
</form>
<a href='admin.php?cmd=clearcomments&id=<?print $r[0];?>'>clear comments</a>
<a href='admin.php?cmd=delete&id=<?print $r[0];?>'>delete item</a>
<hr>
</div>
<?}?>
<div class='newitem'>Add Item: <form method='post' action='admin.php'><input type='hidden' name='cmd' value='new'>
<div class='title'>title: <input type='text' name='title' </div>
<div class='content'>content: <textarea rows='10' cols='80' name='content'></textarea></div>
<input type='submit' name='Add'>
</form>
<hr>
</div>


</body>
</html>
