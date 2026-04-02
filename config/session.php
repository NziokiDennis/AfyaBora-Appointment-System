<?php
function app_base_path() {
    static $basePath = null;

    if ($basePath !== null) {
        return $basePath;
    }

    $appRoot = realpath(dirname(__DIR__));
    $docRoot = isset($_SERVER["DOCUMENT_ROOT"]) ? realpath($_SERVER["DOCUMENT_ROOT"]) : false;

    if ($appRoot && $docRoot && strpos($appRoot, $docRoot) === 0) {
        $relative = str_replace(DIRECTORY_SEPARATOR, "/", substr($appRoot, strlen($docRoot)));
        $basePath = $relative !== "" ? rtrim($relative, "/") : "";
    } else {
        $basePath = "/" . basename($appRoot ?: dirname(__DIR__));
    }

    return $basePath;
}

function app_url($path = "") {
    $basePath = app_base_path();
    $path = trim($path);

    if ($path === "" || $path === "/") {
        return $basePath !== "" ? $basePath . "/" : "/";
    }

    return ($basePath !== "" ? $basePath : "") . "/" . ltrim($path, "/");
}

function app_start_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";
    session_set_cookie_params([
        "path" => app_base_path() ?: "/",
        "httponly" => true,
        "secure" => $isHttps,
        "samesite" => "Lax",
    ]);
    session_start();
}

function app_redirect($path) {
    header("Location: " . app_url($path));
    exit;
}
?>
