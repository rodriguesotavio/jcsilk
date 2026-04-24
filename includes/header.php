<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$sub_dir = '/jcsilk';
$base_url .= $sub_dir;

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: " . $base_url . "/login.php");
    exit;
}

require_once "conexao.php";
require_once "log_helper.php";

define('LIMITE_DIAS_ESTORNO', 2);

$nome_usuario = $_SESSION["nome"];
$cargo_usuario = $_SESSION["cargo"];

$current_path = $_SERVER['PHP_SELF'];
$project_root_path = $sub_dir;

$is_dashboard_active = (
    $current_path == $project_root_path . 'index.php'
    ||
    trim($_SERVER['REQUEST_URI'], '/') == trim($project_root_path, '/')
);

if ($project_root_path === '/') {
    $is_dashboard_active = ($current_path === '/index.php' || $_SERVER['REQUEST_URI'] === '/');
}

$is_dashboard_active = ($current_path == $project_root_path . '/index.php');
$url_parts = explode('/', trim(str_replace($project_root_path, '', $current_path), '/'));
$active_module = isset($url_parts[0]) && !empty($url_parts[0]) && $url_parts[0] != 'index.php' ? $url_parts[0] : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JC Silk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/system.css">
</head>
<body class="app-shell">

<div class="app-layout">
    <aside class="sidebar d-none d-md-flex flex-column">
        <div class="sidebar-top">
            <div>
                <h4 class="text-center mb-2 text-white sidebar-brand">JC Silk</h4>
                <p class="sidebar-subtitle text-center mb-4">Painel de Gestão</p>
            </div>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $is_dashboard_active ? 'active' : ''; ?>" href="<?php echo $base_url; ?>" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Dashboard">
                    <i class="fas fa-home me-2"></i><span class="nav-label">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_module == 'produtos') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/produtos" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Produtos">
                    <i class="fas fa-cubes me-2"></i><span class="nav-label">Produtos</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_module == 'estoque') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/estoque" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Movimentação de Estoque">
                    <i class="fas fa-boxes me-2"></i><span class="nav-label">Movimentação de Estoque</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_module == 'fornecedores') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/fornecedores" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Fornecedores">
                    <i class="fas fa-truck me-2"></i><span class="nav-label">Fornecedores</span>
                </a>
            </li>
            <?php if ($cargo_usuario == 'Administrador'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_module == 'usuarios') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/usuarios" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Usuários">
                    <i class="fas fa-users-cog me-2"></i><span class="nav-label">Usuários</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo ($active_module == 'logs') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/logs" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Log de Auditoria">
                    <i class="fas fa-history me-2"></i><span class="nav-label">Log de Auditoria</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item mt-auto">
                <a class="nav-link" href="<?php echo $base_url; ?>/logout.php" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Sair">
                    <i class="fas fa-sign-out-alt me-2"></i><span class="nav-label">Sair</span>
                </a>
            </li>
        </ul>
        <div class="sidebar-user mt-auto">
             <small class="sidebar-user-label">Logado como: <?php echo htmlspecialchars($nome_usuario); ?></small>
             <div class="sidebar-footer-controls mt-2">
                <button type="button" class="btn btn-sm btn-outline-light sidebar-toggle" id="sidebarToggleBtn" title="Reduzir/expandir menu">
                    <i class="fas fa-angles-left" id="sidebarToggleIcon"></i>
                </button>
             </div>
        </div>
    </aside>

    <main class="content flex-grow-1">