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
    $pk = $_POST['pk'];
    $id_value = $_POST['id'];
    $value = $_POST['value'];

    $mysqli->select_db($db_name);
    $mysqli->query("UPDATE `$table_name` SET `$column`='".$mysqli->real_escape_string($value)."' WHERE `$pk`='".$mysqli->real_escape_string($id_value)."'");
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
function editCell(td, db, table, column, id_value, pk){
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
        xhr.send("update=1&db="+encodeURIComponent(db)+
                 "&table="+encodeURIComponent(table)+
                 "&column="+encodeURIComponent(column)+
                 "&pk="+encodeURIComponent(pk)+
                 "&id="+encodeURIComponent(id_value)+
                 "&value="+encodeURIComponent(value));
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

    // Fetch primary key
    $pk = '';
    $res_pk = $mysqli->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
    if($row_pk = $res_pk->fetch_assoc()){
        $pk = $row_pk['Column_name'];
    }
    if($pk==''){
        echo "<p><b>Error:</b> Table has no primary key. Editing is disabled.</p>";
    }

    // Fetch columns
    $columns = [];
    $res = $mysqli->query("SHOW COLUMNS FROM `$table`");
    while($row = $res->fetch_assoc()){
        $columns[] = $row['Field'];
    }

    // Fetch data (limit 100 rows for safety)
    $res = $mysqli->query("SELECT * FROM `$table` LIMIT 100");

    echo "<table><tr>";
    foreach($columns as $col) echo "<th>$col</th>";
    echo "</tr>";

    while($row = $res->fetch_assoc()){
        echo "<tr>";
        foreach($columns as $col){
            if($pk!=''){
                $id_value = $row[$pk];
                echo "<td class='editable' ondblclick='editCell(this,\"$db\",\"$table\",\"$col\",\"$id_value\",\"$pk\")'>".$row[$col]."</td>";
            } else {
                echo "<td>".$row[$col]."</td>";
            }
        }
        echo "</tr>";
    }
    echo "</table>";
    echo "<br><a href='?db=$db'>Back to tables</a> | <a href='?'>Back to databases</a>";
}
?>
</body>
</html>
