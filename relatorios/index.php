<?php
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Relatórios Gerenciais</h2>
</div>

<p>Selecione um relatório para análise e exportação em PDF.</p>

<div class="row g-4 module-grid">
    <div class="col-12 col-md-6">
        <div class="card h-100 module-card">
            <div class="card-header"><i class="fas fa-boxes-stacked me-2"></i>Estoque Atual</div>
            <div class="card-body d-flex flex-column">
                <h5 class="card-title">Produtos x Estoque Mínimo</h5>
                <p class="card-text">Visualize status de estoque por produto e identifique faltas e criticidade.</p>
                <a href="estoque_atual.php" class="btn btn-primary btn-sm mt-auto align-self-start">Abrir Relatório</a>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="card h-100 module-card">
            <div class="card-header"><i class="fas fa-right-left me-2"></i>Movimentações</div>
            <div class="card-body d-flex flex-column">
                <h5 class="card-title">Movimentações por Período</h5>
                <p class="card-text">Analise entradas e saídas no período e os produtos com maior volume.</p>
                <a href="movimentacoes_periodo.php" class="btn btn-primary btn-sm mt-auto align-self-start">Abrir Relatório</a>
            </div>
        </div>
    </div>

    <?php if ($cargo_usuario === 'Administrador'): ?>
    <div class="col-12 col-md-6">
        <div class="card h-100 module-card">
            <div class="card-header"><i class="fas fa-user-shield me-2"></i>Auditoria</div>
            <div class="card-body d-flex flex-column">
                <h5 class="card-title">Ações por Usuário</h5>
                <p class="card-text">Consolidação das ações registradas por usuário em um intervalo de datas.</p>
                <a href="auditoria_usuarios.php" class="btn btn-primary btn-sm mt-auto align-self-start">Abrir Relatório</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
