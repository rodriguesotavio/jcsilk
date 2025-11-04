<?php
require_once './includes/header.php'; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Bem-vindo(a), <?php echo htmlspecialchars($nome_usuario); ?>!</h1>
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

        <div class="row">
            <div class="col-md-4">
                <div class="card border-primary mb-3">
                    <div class="card-header bg-primary text-white">Controle de Produtos</div>
                    <div class="card-body">
                        <h5 class="card-title">Gerenciar Produtos</h5>
                        <p class="card-text">Visualize e edite os produtos disponíveis no estoque.</p>
                        <a href="<?php echo $base_url; ?>/produtos" class="btn btn-light btn-sm">Ir para Produtos</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-info mb-3">
                    <div class="card-header bg-info text-white">Movimentação e Saldo</div>
                    <div class="card-body">
                        <h5 class="card-title">Lançar Entrada/Saída</h5>
                        <p class="card-text">Registre todas as entradas e saídas para manter o saldo atualizado.</p>
                        <a href="<?php echo $base_url; ?>/estoque" class="btn btn-light btn-sm">Ir para Estoque</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success mb-3">
                    <div class="card-header bg-success text-white">Registro de Fornecedores</div>
                    <div class="card-body">
                        <h5 class="card-title">Cadastro de Fornecedores</h5>
                        <p class="card-text">Mantenha os dados dos fornecedores atualizados.</p>
                        <a href="<?php echo $base_url; ?>/fornecedores" class="btn btn-light btn-sm">Ir para Fornecedores</a>
                    </div>
                </div>
            </div>

            <?php if ($cargo_usuario == 'Administrador'): ?>
            <div class="col-md-4">
                <div class="card border-secondary mb-3">
                    <div class="card-header bg-secondary text-white">Controle de Acesso</div>
                    <div class="card-body">
                        <h5 class="card-title">Gerenciar Usuários</h5>
                        <p class="card-text">Adicione ou inative usuários do sistema (apenas Administradores).</p>
                        <a href="<?php echo $base_url; ?>/usuarios" class="btn btn-light btn-sm">Ir para Usuários</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

<?php require_once './includes/footer.php'; ?>