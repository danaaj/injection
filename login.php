<?
/*
Alex Dana
CSE467
Spring 2015
Simple web app used to investigate security flaws

Handles authentication of user
level -0 regular, 1 = admin
*/

function generateHash($salt, $curpass) {
        $iterations = 1000;
        //echo "<br>salt = " . $salt . "<br>";
        //echo "curpass = " . $curpass . "<br>";
        $hash = hash_pbkdf2("sha256", $curpass, $salt, $iterations, 20);
        return $hash;
}

session_start();
require_once "GoogleAuthenticator.php";
require_once "creds.php";
//mysql_connect("localhost",$user,$password);
//mysql_select_db("injection");

$servername = "localhost";
$dbname = "injection";
$conn = new mysqli($servername, $user, $password, $dbname);
if ($conn->connect_error) {
	die ("Connection failed: " . $conn->connect_error);
}

$randomtoken = base64_encode(openssl_random_pseudo_bytes(32));
$_SESSION['csrfToken'] = $randomtoken;


//get commands and perform them
if (isset($_REQUEST['cmd'])) {
	$cmd = $_REQUEST['cmd'];
	if ($cmd=="logout") {
		unset($_SESSION['user']);
		header("Location: index.php");
		syslog(LOG_INFO, 'user logout');
	}
	elseif ($cmd=="login") {
		$user = $_REQUEST['user'];
		$pass = $_REQUEST['password'];
		$code = $_REQUEST['code'];

		$user = htmlspecialchars($user, ENT_QUOTES);
		$pass = htmlspecialchars($pass, ENT_QUOTES);
		$code = htmlspecialchars($code, ENT_QUOTES);
		

		$get_salt = $conn->query("select salt from users where user='$user'");
		if (!$get_salt) {
			print $conn->error;
		}
		while ($row=mysqli_fetch_array($get_salt, MYSQLI_NUM)) {
			$salt = $row[0];
		}
		$hash = generateHash($salt, $pass);

		$sql = $conn->prepare("select level,user,id from users where user=? and hash = ?");
		$sql->bind_param("ss", $user, $hash);

		$ga = new PHPGangsta_GoogleAuthenticator();
		$secret = "47XW7THZKNUZJ3Q7";
		//echo "Secret is: " .$secret."\n\n";
		$oneCode = $ga->getCode($secret);
		echo "Checking Code '$oneCode' and Secret '$secret':\n";
		$checkResult = $ga->verifyCode($secret, $oneCode, 2);

		$checkCode = $ga->verifyCode($secret, $code, 2);
		//echo "User entered code '$code'\n";
		if ($sql->execute() && $checkCode) {
			$result = array();
			$sql->bind_result($lvl, $usr, $usrid);
			while ($sql->fetch()) {
				array_push($result, $lvl, $usr, $usrid);
			}
			if ($result[1] == $user) {
				$_SESSION['user'] = $user;
				$_SESSION['level'] = $result[0];
				$_SESSION['userid'] = $result[2];
				header("Location: admin.php");
			}
			syslog(LOG_INFO, 'user login');
		}
		$sql->close();
		print mysql_error();
	}
}//end of perform commands
?>
<html>
<head>
<style>
<input type='hidden' name='csrfToken' value='<?php echo($_SESSION['csrfToken'])?>'/>
</style>
</head>
<body>
<?if (isset($_SESSION['user'])):?>
<a href='login.php?cmd=logout'>Logout</a>
<?else:?>
<div>Login</div>
<form method='post' action='login.php'>
<div>User: <input type='text' name='user'></div>
<div>Password: <input type='password' name='password'></div>
<div>Code: <input type='text' name='code'></div>
<div><input type='submit' name='login'></div>
<input type='hidden' name='cmd' value='login'>
</form>
<?endif;?>
</body>

<!--<script type="text/javascript">
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
      return entityMap[s];
    });
}
</script>-->
</html>




