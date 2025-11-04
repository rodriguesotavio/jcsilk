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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer" />
    <style>
        .sidebar {
            height: 100vh;
            background-color: #343a40;
            color: white;
            padding-top: 20px;
            min-width: 250px;
        }
        .sidebar a {
            color: #adb5bd;
            padding: 10px 15px;
            display: block;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .sidebar a:visited,
        .sidebar a:focus,
        .sidebar a:active {
            color: #adb5bd;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #495057;
            color: white;
        }
        .content {
            padding: 20px;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar d-none d-md-block">
        <h4 class="text-center mb-4 text-white">JC Silk</h4>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $is_dashboard_active ? 'active' : ''; ?>" href="<?php echo $base_url; ?>">
                    <i class="fas fa-home me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_module == 'produtos') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/produtos">
                    <i class="fas fa-cubes me-2"></i> Produtos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_module == 'estoque') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/estoque">
                    <i class="fas fa-boxes me-2"></i> Movimentação de Estoque
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_module == 'fornecedores') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/fornecedores">
                    <i class="fas fa-truck me-2"></i> Fornecedores
                </a>
            </li>
            <?php if ($cargo_usuario == 'Administrador'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_module == 'usuarios') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/usuarios">
                    <i class="fas fa-users-cog me-2"></i> Usuários
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo ($active_module == 'logs') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/logs">
                    <i class="fas fa-history me-2"></i> Log de Auditoria
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item mt-auto">
                <a class="nav-link" href="<?php echo $base_url; ?>/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i> Sair
                </a>
            </li>
        </ul>
        <div class="mt-auto p-3">
             <small class="text-white-50">Logado como: <?php echo htmlspecialchars($nome_usuario); ?></small>
        </div>
    </div>

    <div class="flex-grow-1 content">