<?php
require_once 'app/views/partials/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-10 mx-auto">
            <h1 class="mb-4">Sobre Nós</h1>
            
            <div class="row mb-5">
                <div class="col-md-8">
                    <h2>Nossa História</h2>
                    <p class="lead">
                        [Breve descrição sobre a história da empresa e sua missão]
                    </p>
                    <p>
                        [Detalhes adicionais sobre a trajetória da empresa, valores e objetivos]
                    </p>
                </div>
                <div class="col-md-4">
                    <img src="/assets/img/about-image.jpg" alt="Nossa Empresa" class="img-fluid rounded">
                </div>
            </div>
            
            <div class="row mb-5">
                <div class="col-md-12">
                    <h2>Nossa Missão</h2>
                    <p>
                        [Descrição da missão da empresa]
                    </p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h3 class="card-title">Valores</h3>
                            <ul class="list-unstyled">
                                <li>✓ [Valor 1]</li>
                                <li>✓ [Valor 2]</li>
                                <li>✓ [Valor 3]</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h3 class="card-title">Visão</h3>
                            <p>
                                [Descrição da visão da empresa]
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h3 class="card-title">Diferenciais</h3>
                            <ul class="list-unstyled">
                                <li>✓ [Diferencial 1]</li>
                                <li>✓ [Diferencial 2]</li>
                                <li>✓ [Diferencial 3]</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'app/views/partials/footer.php';
?>