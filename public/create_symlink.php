<?php
$target = '/home/don/domains/mcr.cbg.ru/public_html/storage/app'; // Цель: storage/app
$link = '/home/don/domains/mcr.cbg.ru/public_html/public/storage'; // Ссылка: public/storage

if (file_exists($link)) {
    if (is_link($link) && readlink($link) === $target) {
        echo 'Symbolic link already exists and points to: ' . readlink($link);
    } else {
        echo 'Storage exists but is not a symbolic link. Please remove public/storage folder.';
    }
} elseif (!is_dir($target)) {
    echo 'Target directory does not exist: ' . $target;
} elseif (!is_writable(dirname($link))) {
    echo 'Public directory is not writable: ' . dirname($link);
} else {
    try {
        symlink($target, $link);
        echo 'Symbolic link created successfully!';
    } catch (Exception $e) {
        echo 'Error creating symbolic link: ' . $e->getMessage();
    }
}
?>