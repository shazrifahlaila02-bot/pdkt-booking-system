<?php
/** Ikon tempatan berdasarkan nama aset, tanpa imej luar. */
function ikon_aset($nama) {
    $nama = mb_strtolower((string)$nama);
    if (preg_match('/kamera|camera|canon|nikon|eos/', $nama)) {
        $bentuk = '<path d="M4 7h4l2-3h4l2 3h4a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z"/><circle cx="12" cy="13" r="4"/><path d="M18 10h1"/>';
    } elseif (preg_match('/telefon|phone|samsung|iphone/', $nama)) {
        $bentuk = '<rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M10 5h4M11 19h2"/>';
    } elseif (preg_match('/komputer|riba|laptop|dell|probook|notebook/', $nama)) {
        $bentuk = '<rect x="4" y="3" width="16" height="13" rx="1.5"/><path d="m4 16-2 4h20l-2-4M9 20h6M7 7h10M7 10h6"/>';
    } else {
        $bentuk = '<path d="m12 3 9 5v8l-9 5-9-5V8l9-5ZM3 8l9 5 9-5M12 13v8"/>';
    }
    return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round">'.$bentuk.'</svg>';
}
