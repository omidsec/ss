<?php
$mysqli = new mysqli("db2","styleup_db","Fseuktie4t42Eq221bCS\$A@!","");
if($mysqli->connect_error){
    die("Connection failed: ".$mysqli->connect_error);
}

$db = isset($_GET['db']) ? $_GET['db'] : '';
$table = isset($_GET['table']) ? $_GET['table'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 100;
$offset = ($page-1)*$per_page;

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
body { font-family: Arial; }
table { border-collapse: collapse; width: 100%; margin-top: 10px; }
th, td { border: 1px solid #ccc; padding: 5px; }
td.editable { cursor: pointer; background-color: #f9f9f9; }
.pagination a { margin: 0 5px; text-decoration: none; }
.pagination a.current { font-weight: bold; }
.rows-per-page { margin: 10px 0; }
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

        var xhr = new XMLHttpRequest();
        xhr.open("POST","",true);
        xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        xhr.send("update=1&db="+encodeURIComponent(db)+
                 "&table="+encodeURIComponent(table)+
                 "&column="+encodeURIComponent(column)+
                 "&pk="+encodeURIComponent(pk)+
                 "&id="+encodeURIComponent(id_value)+
                 "&value="+encodeURIComponent(value));
    }
}

function changePerPage(){
    var perPage = document.getElementById('per_page_input').value;
    if(perPage < 1) perPage = 1;
    var url = new URL(window.location.href);
    url.searchParams.set('per_page', perPage);
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
}
</script>
</head>
<body>
<h2>PHP MySQL Explorer</h2>

<?php
if($db==''){
    echo "<h3>Databases:</h3>";
    $res = $mysqli->query("SHOW DATABASES");
    while($row = $res->fetch_array()){
        echo "<a href='?db=".$row[0]."'>".$row[0]."</a><br>";
    }
} elseif($db != '' && $table==''){
    echo "<h3>Database: $db</h3>";
    $mysqli->select_db($db);
    $res = $mysqli->query("SHOW TABLES");
    while($row = $res->fetch_array()){
        $tbl = $row[0];
        echo "<a href='?db=$db&table=$tbl'>$tbl</a><br>";
    }
    echo "<br><a href='?'>Back to databases</a>";
} elseif($db != '' && $table != ''){
    echo "<h3>Database: $db | Table: $table</h3>";
    $mysqli->select_db($db);

    // --- Rows per page input box ---
    echo "<div class='rows-per-page'>Rows per page: <input type='number' id='per_page_input' value='$per_page' style='width:70px;'> <button onclick='changePerPage()'>Set</button></div>";

    // --- Primary key ---
    $pk = '';
    $res_pk = $mysqli->query("SHOW KEYS FROM `$table` WHERE Key_name='PRIMARY'");
    if($row_pk = $res_pk->fetch_assoc()){
        $pk = $row_pk['Column_name'];
    }
    if($pk=='') echo "<p><b>Warning:</b> Table has no primary key. Editing disabled.</p>";

    // --- Columns ---
    $columns = [];
    $res = $mysqli->query("SHOW COLUMNS FROM `$table`");
    while($row = $res->fetch_assoc()) $columns[] = $row['Field'];

    // --- Data ---
    $res = $mysqli->query("SELECT * FROM `$table` LIMIT $offset,$per_page");
    echo "<table><tr>";
    foreach($columns as $col) echo "<th>$col</th>";
    echo "</tr>";

    while($row = $res->fetch_assoc()){
        echo "<tr>";
        foreach($columns as $col){
            if($pk!=''){
                $id_val = $row[$pk];
                echo "<td class='editable' ondblclick='editCell(this,\"$db\",\"$table\",\"$col\",\"$id_val\",\"$pk\")'>".$row[$col]."</td>";
            } else echo "<td>".$row[$col]."</td>";
        }
        echo "</tr>";
    }
    echo "</table>";

    // --- Pagination ---
    $res_count = $mysqli->query("SELECT COUNT(*) as total FROM `$table`");
    $total_rows = $res_count->fetch_assoc()['total'];
    $total_pages = ceil($total_rows/$per_page);

    echo "<div class='pagination'>";
    if($page>1) echo "<a href='?db=$db&table=$table&page=".($page-1)."&per_page=$per_page'>Prev</a>";
    for($p=1;$p<=$total_pages;$p++){
        $cls = ($p==$page)?"current":"";
        echo "<a class='$cls' href='?db=$db&table=$table&page=$p&per_page=$per_page'>$p</a>";
    }
    if($page<$total_pages) echo "<a href='?db=$db&table=$table&page=".($page+1)."&per_page=$per_page'>Next</a>";
    echo "</div>";

    echo "<br><a href='?db=$db'>Back to tables</a> | <a href='?'>Back to databases</a>";
}
?>
</body>
</html>
