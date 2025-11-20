<?php
// --- MySQL Connection ---
$mysqli = new mysqli("db2","styleup_db","Fseuktie4t42Eq221bCS\$A@!","");
if($mysqli->connect_error){
    die("Connection failed: ".$mysqli->connect_error);
}

// --- Handle DB / Table selection ---
$db = isset($_GET['db']) ? $_GET['db'] : '';
$table = isset($_GET['table']) ? $_GET['table'] : '';

// --- Handle update of a cell ---
if(isset($_POST['update'])){
    $db_name = $_POST['db'];
    $table_name = $_POST['table'];
    $column = $_POST['column'];
    $id = $_POST['id'];
    $value = $_POST['value'];

    $mysqli->select_db($db_name);
    $mysqli->query("UPDATE `$table_name` SET `$column`='".$mysqli->real_escape_string($value)."' WHERE id=$id");
    echo "Updated successfully!";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>PHP MySQL Explorer</title>
<style>
table { border-collapse: collapse; margin-top: 10px; }
td, th { border: 1px solid #ccc; padding: 5px; }
td.editable { cursor: pointer; background-color: #f9f9f9; }
</style>
<script>
function editCell(td, db, table, column, id){
    var oldValue = td.innerText;
    var input = document.createElement('input');
    input.value = oldValue;
    input.style.width = '100%';
    td.innerText = '';
    td.appendChild(input);
    input.focus();

    input.onblur = function(){
        var value = this.value;
        td.innerText = value;

        // Send AJAX request to update
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "", true);
        xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        xhr.send("update=1&db="+encodeURIComponent(db)+"&table="+encodeURIComponent(table)+"&column="+encodeURIComponent(column)+"&id="+encodeURIComponent(id)+"&value="+encodeURIComponent(value));
    }
}
</script>
</head>
<body>
<h2>PHP MySQL Explorer</h2>

<?php
// --- Step 1: List databases ---
if($db==''){
    echo "<h3>Databases:</h3>";
    $res = $mysqli->query("SHOW DATABASES");
    while($row = $res->fetch_array()){
        echo "<a href='?db=".$row[0]."'>".$row[0]."</a><br>";
    }
}
// --- Step 2: List tables of selected DB ---
elseif($db != '' && $table==''){
    echo "<h3>Database: $db</h3>";
    $mysqli->select_db($db);
    $res = $mysqli->query("SHOW TABLES");
    while($row = $res->fetch_array()){
        $tbl = $row[0];
        echo "<a href='?db=$db&table=$tbl'>$tbl</a><br>";
    }
    echo "<br><a href='?'>Back to databases</a>";
}
// --- Step 3: Show table data ---
elseif($db != '' && $table != ''){
    echo "<h3>Database: $db | Table: $table</h3>";
    $mysqli->select_db($db);

    // Fetch columns
    $columns = [];
    $res = $mysqli->query("SHOW COLUMNS FROM `$table`");
    while($row = $res->fetch_assoc()){
        $columns[] = $row['Field'];
    }

    // Fetch data
    $res = $mysqli->query("SELECT * FROM `$table` LIMIT 100"); // limit 100 rows for safety

    echo "<table><tr>";
    foreach($columns as $col) echo "<th>$col</th>";
    echo "</tr>";

    $row_id = 0;
    while($row = $res->fetch_assoc()){
        echo "<tr>";
        foreach($columns as $col){
            $id = $row_id; // simple row index
            echo "<td class='editable' ondblclick='editCell(this,\"$db\",\"$table\",\"$col\",$id)'>".$row[$col]."</td>";
        }
        echo "</tr>";
        $row_id++;
    }
    echo "</table>";
    echo "<br><a href='?db=$db'>Back to tables</a> | <a href='?'>Back to databases</a>";
}
?>
</body>
</html>
