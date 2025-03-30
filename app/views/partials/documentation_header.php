<?php
/**
 * Header específico para as páginas de documentação
 * 
 * @version 1.0
 */
?>
<div class="doc-header">
    <div class="container">
        <div class="row">
            <div class="col-xs-8 col-sm-9">
                <h1 class="doc-header-title"><?php echo htmlspecialchars($data['title']); ?></h1>
                
                <!-- Breadcrumb de navegação -->
                <ol class="breadcrumb doc-breadcrumb">
                    <li><a href="?page=home">Home</a></li>
                    <li><a href="?page=documentation&action=index">Documentação</a></li>
                    
                    <?php if ($data['activePage'] != 'index'): ?>
                        <li class="active">
                            <?php echo htmlspecialchars($data['sections'][$data['activePage']]['title']); ?>
                        </li>
                    <?php else: ?>
                        <li class="active">Visão Geral</li>
                    <?php endif; ?>
                </ol>
            </div>
            
            <div class="col-xs-4 col-sm-3 text-right">
                <!-- Botão para expandir/colapsar o menu lateral em dispositivos móveis -->
                <button id="toggleSidebarBtn" class="btn btn-default doc-sidebar-toggle visible-xs visible-sm">
                    <i class="fa fa-bars"></i> Menu
                </button>
            </div>
        </div>
    </div>
</div>
