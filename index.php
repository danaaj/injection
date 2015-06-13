<?
/*
Alex Dana
CSE467
Spring 2015
Simple web app used to investigate security flaws

displays a simply bbs
*/

session_start();
require_once "creds.php";


//make sure db setup is done
if ($user === "" || $password==="") {
die ("must configure user and password in source");
}


//connect to db
//mysql_connect("localhost",$user,$password);
//mysql_select_db("injection");

$servername = "localhost";
$dbname = "injection";
$conn = new mysqli($servername, $user, $password, $dbname);
if ($conn->connect_error) {
	die ("Connection failed: " . $conn->connect_error);
}
/*
get comments
*/
function comments($id) {
	global $conn;
	$sql = $conn->prepare("select comment from comments where fkpage=?");
	$sql->bind_param("i", $id); 
	print "<div class='comments'>comments:<ul>";
	if ($sql->execute()) {
		$sql->store_result();
		$sql->bind_result($results);
		while ($sql->fetch()) {
			print "<li class='comment'>$results</li>";
		}
	}
	print "</ul></div>";
}

//add comment
function addComment($id,$comment) {
	$comment = htmlspecialchars($comment, ENT_QUOTES);
	global $conn;
	$sql = $conn->prepare("insert into comments (fkpage, comment, fkuser) values (?,?,?)");
	$sql->bind_param("isi", $id, $comment, $_SESSION["userid"]);
	$sql->execute();
	syslog(LOG_INFO, 'comment added');
}

//check for commands and perform them
if (isset($_REQUEST['cmd']) && isset($_REQUEST['id'])) {
	$cmd = $_REQUEST['cmd'];
	$id=$_REQUEST['id'];
	if ($cmd == "addcomment") {
		addComment($_REQUEST['id'],$_REQUEST['comment']);
	}
}

?>
<html>
<head>
<input type='hidden' name='csrfToken' value='<?php echo($_SESSION['csrfToken'])?>'/>
<style>
.date {
color: green;
margin-bottom: 10px;
font-size: 9px;
}
#login {
float: right;
width: 50px;
height: 20px;
}
.comments {
	margin-top: 20px;
	font-size: 10px;
}
.addcomment {
	margin-top: 10px;
	font-size: 9px;
}
.comment {
}
	
input {
font-size: 7px;
}

div#nav {
width: 400px;
border: thin solid black;
}
div#nav ul {
list-style-type: square;
} 
div#nav li {
display: inline;
padding: 5px;
}
</style>
</head>
<body>
<div id='login'>
<?if (isset($_SESSION['user']) &&  $_SESSION['level'] == 1):?>
<a href='admin.php'>admin</a>
<?endif;?>

<a href='login.php'>Login</a>
</div>
<div>
Select a page to view
</div>

<div id='nav'>
<ul>
<?
//display navigation choicces
$q=$conn->query("select id,title from data order by title");
if (!$q) {
	print $conn->error();
}
While ($r=mysqli_fetch_array($q, MYSQLI_NUM)) {
	print "<li><a href='index.php?id=" . $r[0] . "'>" . $r[1] . "<a></li>";
}
?>
</ul>
</div>

<div id='content'>
<?
if (isset($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
	$sql = $conn->prepare("select title,content,`date` from `data` where id=?");
	$sql->bind_param("i", $id);
	if (!$sql->execute()) {
		print "error - " . $conn->error() . " " . $sql;
	} else {
		$sql->store_result();
		$sql->bind_result($t, $c, $d);
		$result = array();
		if ($sql->fetch()) {
			array_push($result, $t, $c, $d);
		}
		if (count($result) == 3) {
			print "<div class='content'><h2>". $result[0] . "</h2><span class='date'>Date: " . $result[2] . "</span><div>" . $result[1] . "</div>";
			print comments($id);
			if (isset($_SESSION['user'])) {	//only show comment add for valid users
				print "<div class='addcomment'><form method='post'>Comment: <input type='text' name='comment'><input type='hidden' name='id' value='$id'><input type='hidden' name='cmd' value='addcomment'><input type='submit'></form></div>";
				print "</div>";
			}
		}
	}
}
?>
</div>
</body>

<script>
var entityMap = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': '&quot;',
    "'": '&#39;',
    "/": '&#x2F;'
  };

function escapeHtml(string) {
    return String(string).replace(/[&<>"'\/]/g, function (s) {
      document.getElementById(string) = entityMap[s];
    });
}
</script>
</html>
