<?php
session_start();

// --- Handle connection form ---
if(isset($_POST['connect'])){
    $_SESSION['db_host'] = $_POST['host'];
    $_SESSION['db_user'] = $_POST['user'];
    $_SESSION['db_pass'] = $_POST['pass'];
    $_SESSION['db_name'] = $_POST['db'];
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// --- Attempt connection if credentials exist ---
$mysqli = null;
$error = '';
if(isset($_SESSION['db_user'], $_SESSION['db_pass'], $_SESSION['db_host'])){
    $mysqli = @new mysqli(
        $_SESSION['db_host'],
        $_SESSION['db_user'],
        $_SESSION['db_pass'],
        $_SESSION['db_name'] ?? ''
    );
    if($mysqli->connect_error) $error = "Connection failed: ".$mysqli->connect_error;
}

// --- Logout / reset connection ---
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// --- Handle updates, uploads, etc. --- (reuse previous code)
// For brevity, here we focus on dynamic login first
?>
<!DOCTYPE html>
<html>
<head>
<title>Dynamic PHP MySQL Explorer</title>
<style>
body { font-family: Arial; }
input { padding:5px; margin:5px; }
</style>
</head>
<body>

<h2>Dynamic MySQL Explorer</h2>

<?php if(!$mysqli || $error): ?>
    <h3>Enter MySQL Connection Details</h3>
    <?php if($error) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="post">
        Host: <input type="text" name="host" value="localhost"><br>
        Username: <input type="text" name="user"><br>
        Password: <input type="password" name="pass"><br>
        Database (optional): <input type="text" name="db"><br>
        <button type="submit" name="connect">Connect</button>
    </form>
<?php else: ?>
    <p style="color:green;">Connected as <?php echo htmlspecialchars($_SESSION['db_user']); ?>@<?php echo htmlspecialchars($_SESSION['db_host']); ?> </p>
    <a href="?logout=1">Change credentials</a>

    <?php
    // --- Here you can include all the previous code for:
    // - Database selection
    // - Table browsing
    // - Editable cells
    // - Pagination
    // - Download buttons
    // - Directory browsing
    // etc.
    ?>
<?php endif; ?>

</body>
</html>
