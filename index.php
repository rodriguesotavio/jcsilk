<?php
require_once './includes/header.php';

$total_produtos = 0;
$total_sem_estoque = 0;
$total_estoque_critico = 0;
$total_estoque_baixo = 0;
$produtos_alerta = [];

$sql_resumo = "SELECT
    COUNT(*) AS total_produtos,
    SUM(CASE WHEN quantidade_estoque = 0 THEN 1 ELSE 0 END) AS total_sem_estoque,
    SUM(CASE WHEN quantidade_estoque > 0 AND quantidade_estoque <= estoque_minimo THEN 1 ELSE 0 END) AS total_estoque_critico,
    SUM(CASE WHEN quantidade_estoque > estoque_minimo AND quantidade_estoque <= (estoque_minimo + 10) THEN 1 ELSE 0 END) AS total_estoque_baixo
FROM produtos";
$result_resumo = mysqli_query($link, $sql_resumo);
if ($result_resumo) {
    $row_resumo = mysqli_fetch_assoc($result_resumo);
    $total_produtos = (int) ($row_resumo['total_produtos'] ?? 0);
    $total_sem_estoque = (int) ($row_resumo['total_sem_estoque'] ?? 0);
    $total_estoque_critico = (int) ($row_resumo['total_estoque_critico'] ?? 0);
    $total_estoque_baixo = (int) ($row_resumo['total_estoque_baixo'] ?? 0);
    mysqli_free_result($result_resumo);
}

$sql_alertas = "SELECT
    p.id_produto,
    p.nome_produto,
    p.categoria,
    p.quantidade_estoque,
    p.estoque_minimo,
    f.nome AS nome_fornecedor
FROM produtos p
JOIN fornecedores f ON p.fornecedor_id = f.id_fornecedor
WHERE p.quantidade_estoque <= (p.estoque_minimo + 10)
ORDER BY p.quantidade_estoque ASC, p.nome_produto ASC
LIMIT 15";
$result_alertas = mysqli_query($link, $sql_alertas);
if ($result_alertas) {
    while ($row_alerta = mysqli_fetch_assoc($result_alertas)) {
        $produtos_alerta[] = $row_alerta;
    }
    mysqli_free_result($result_alertas);
}
?>

        <div class="page-header">
            <h1 class="page-title">Bem-vindo(a), <?php echo htmlspecialchars($nome_usuario); ?>!</h1>
            <a href="logout.php" class="btn btn-danger">Sair</a>
        </div>

        <?php
            if(isset($_GET['status'])){
                $status = $_GET['status'];
                if($status == 'permission_denied'){
                    echo '<div class="alert alert-danger"><b>Acesso Negado:</b> Você não tem permissão de Administrador para acessar o recurso solicitado.</div>';
                }
            }
        ?>

        <p>Você está logado como <b><?php echo htmlspecialchars($cargo_usuario); ?></b>.</p>
        <hr>

        <div class="row g-4 mb-2">
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <p class="text-muted small mb-2">Total de Produtos</p>
                        <h3 class="mb-0"><?php echo $total_produtos; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-danger">
                    <div class="card-body">
                        <p class="text-muted small mb-2">Produtos em Falta</p>
                        <h3 class="mb-0 text-danger"><?php echo $total_sem_estoque; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-warning">
                    <div class="card-body">
                        <p class="text-muted small mb-2">Abaixo do Mínimo</p>
                        <h3 class="mb-0 text-warning"><?php echo $total_estoque_critico; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-info">
                    <div class="card-body">
                        <p class="text-muted small mb-2">Próximo do Mínimo</p>
                        <h3 class="mb-0 text-info"><?php echo $total_estoque_baixo; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Relatório de Produtos em Falta / Mínimo</span>
                <a href="<?php echo $base_url; ?>/produtos" class="btn btn-sm btn-primary">Ver cadastro completo</a>
            </div>
            <div class="card-body">
                <?php if (count($produtos_alerta) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Categoria</th>
                                    <th>Fornecedor</th>
                                    <th class="text-end">Estoque Atual</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($produtos_alerta as $produto_alerta): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($produto_alerta['nome_produto']); ?></td>
                                    <td><?php echo htmlspecialchars($produto_alerta['categoria']); ?></td>
                                    <td><?php echo htmlspecialchars($produto_alerta['nome_fornecedor']); ?></td>
                                    <td class="text-end"><?php echo (int) $produto_alerta['quantidade_estoque']; ?></td>
                                    <td>
                                        <?php if ((int) $produto_alerta['quantidade_estoque'] === 0): ?>
                                            <span class="badge bg-danger">Em Falta</span>
                                        <?php elseif ((int) $produto_alerta['quantidade_estoque'] <= (int) $produto_alerta['estoque_minimo']): ?>
                                            <span class="badge bg-warning text-dark">Crítico</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark">Baixo</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success mb-0">Nenhum produto em faixa de alerta no momento.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card mb-3 h-100">
                    <div class="card-header bg-primary text-white">Controle de Produtos</div>
                    <div class="card-body">
                        <h5 class="card-title">Gerenciar Produtos</h5>
                        <p class="card-text">Visualize e edite os produtos disponíveis no estoque.</p>
                        <a href="<?php echo $base_url; ?>/produtos" class="btn btn-primary btn-sm">Ir para Produtos</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-3 h-100">
                    <div class="card-header bg-info text-white">Movimentação e Saldo</div>
                    <div class="card-body">
                        <h5 class="card-title">Lançar Entrada/Saída</h5>
                        <p class="card-text">Registre todas as entradas e saídas para manter o saldo atualizado.</p>
                        <a href="<?php echo $base_url; ?>/estoque" class="btn btn-primary btn-sm">Ir para Estoque</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-3 h-100">
                    <div class="card-header bg-success text-white">Registro de Fornecedores</div>
                    <div class="card-body">
                        <h5 class="card-title">Cadastro de Fornecedores</h5>
                        <p class="card-text">Mantenha os dados dos fornecedores atualizados.</p>
                        <a href="<?php echo $base_url; ?>/fornecedores" class="btn btn-primary btn-sm">Ir para Fornecedores</a>
                    </div>
                </div>
            </div>

            <?php if ($cargo_usuario == 'Administrador'): ?>
            <div class="col-md-4">
                <div class="card mb-3 h-100">
                    <div class="card-header bg-secondary text-white">Controle de Acesso</div>
                    <div class="card-body">
                        <h5 class="card-title">Gerenciar Usuários</h5>
                        <p class="card-text">Adicione ou inative usuários do sistema (apenas Administradores).</p>
                        <a href="<?php echo $base_url; ?>/usuarios" class="btn btn-primary btn-sm">Ir para Usuários</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

<?php require_once './includes/footer.php'; ?>