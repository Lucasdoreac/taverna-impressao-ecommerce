<?php include_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">Termos e Condições para Upload de Modelos 3D</h1>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Importante:</strong> Ao enviar modelos 3D para impressão na TAVERNA DA IMPRESSÃO, você concorda com todos os termos e condições abaixo.
                    </div>
                    
                    <h2 class="h4 mb-3">1. Propriedade Intelectual e Direitos de Uso</h2>
                    <p>1.1. Ao enviar um modelo 3D para impressão, você declara e garante que:</p>
                    <ul class="mb-4">
                        <li>Você é o criador original do modelo e possui todos os direitos de propriedade intelectual sobre ele; ou</li>
                        <li>Você possui licença ou permissão legítima para usar, modificar e imprimir o modelo 3D enviado; ou</li>
                        <li>O modelo está em domínio público ou sob licença que permite explicitamente seu uso para impressão 3D.</li>
                    </ul>
                    
                    <p>1.2. A TAVERNA DA IMPRESSÃO não reivindica propriedade sobre os modelos enviados pelos clientes. Você mantém todos os direitos de propriedade intelectual sobre seu modelo.</p>
                    
                    <p>1.3. Ao enviar um modelo 3D, você concede à TAVERNA DA IMPRESSÃO uma licença limitada e não exclusiva para:</p>
                    <ul class="mb-4">
                        <li>Analisar, processar e modificar o modelo conforme necessário para viabilizar a impressão;</li>
                        <li>Armazenar o modelo em nossos servidores pelo tempo necessário para a conclusão do pedido;</li>
                        <li>Produzir a impressão 3D física do modelo conforme solicitado.</li>
                    </ul>
                    
                    <h2 class="h4 mb-3">2. Conteúdo Proibido</h2>
                    <p>2.1. Você concorda em não enviar modelos 3D que:</p>
                    <ul class="mb-4">
                        <li>Infrinjam direitos autorais, marcas registradas, patentes ou outros direitos de propriedade intelectual de terceiros;</li>
                        <li>Contenham conteúdo obsceno, difamatório, ameaçador, pornográfico ou que promova discriminação, ódio ou violência;</li>
                        <li>Representem armas de fogo realistas ou peças funcionais de armas;</li>
                        <li>Violem leis ou regulamentos locais, estaduais, nacionais ou internacionais;</li>
                        <li>Contenham malware, vírus ou qualquer código malicioso.</li>
                    </ul>
                    
                    <h2 class="h4 mb-3">3. Análise e Aprovação de Modelos</h2>
                    <p>3.1. Todos os modelos 3D enviados passarão por uma análise técnica e de conteúdo antes de serem aprovados para impressão.</p>
                    
                    <p>3.2. A TAVERNA DA IMPRESSÃO reserva-se o direito de recusar a impressão de qualquer modelo que:</p>
                    <ul class="mb-4">
                        <li>Viole qualquer uma das disposições destes Termos;</li>
                        <li>Não seja tecnicamente viável para impressão devido a características do design;</li>
                        <li>Exija recursos ou materiais que não estejam disponíveis em nossas instalações;</li>
                        <li>Represente risco para nossos equipamentos ou colaboradores.</li>
                    </ul>
                    
                    <p>3.3. O tempo de análise varia de acordo com a complexidade do modelo e a demanda do momento.</p>
                    
                    <h2 class="h4 mb-3">4. Considerações Técnicas</h2>
                    <p>4.1. Modelos 3D enviados devem estar em formato STL ou OBJ.</p>
                    
                    <p>4.2. O tamanho máximo de arquivo aceito é de 50MB.</p>
                    
                    <p>4.3. Para garantir a viabilidade da impressão, seu modelo deve:</p>
                    <ul class="mb-4">
                        <li>Ser estanque (watertight), sem erros de geometria;</li>
                        <li>Ter espessura adequada em todas as partes (mínimo de 1mm para partes estruturais);</li>
                        <li>Ser adaptado para a tecnologia FDM (Fused Deposition Modeling).</li>
                    </ul>
                    
                    <p>4.4. A TAVERNA DA IMPRESSÃO pode sugerir modificações para viabilizar a impressão de modelos que apresentem problemas técnicos.</p>
                    
                    <h2 class="h4 mb-3">5. Armazenamento e Segurança</h2>
                    <p>5.1. Os modelos 3D enviados serão armazenados em nossos servidores seguros durante o processamento do pedido.</p>
                    
                    <p>5.2. Modelos aprovados permanecerão em nosso sistema, associados à sua conta, para facilitar pedidos futuros, a menos que você solicite sua exclusão.</p>
                    
                    <p>5.3. Você pode solicitar a exclusão de seus modelos a qualquer momento, desde que não estejam vinculados a pedidos em andamento.</p>
                    
                    <h2 class="h4 mb-3">6. Limitação de Responsabilidade</h2>
                    <p>6.1. A TAVERNA DA IMPRESSÃO não se responsabiliza por quaisquer danos diretos, indiretos, incidentais ou consequenciais resultantes do uso de modelos 3D impressos, incluindo, mas não se limitando a, danos à propriedade, lesões pessoais ou perda de lucros.</p>
                    
                    <p>6.2. A qualidade final da impressão pode variar devido a limitações técnicas da tecnologia FDM, sendo que pequenas imperfeições são consideradas normais e aceitáveis.</p>
                    
                    <h2 class="h4 mb-3">7. Modificações dos Termos</h2>
                    <p>7.1. A TAVERNA DA IMPRESSÃO reserva-se o direito de modificar estes Termos a qualquer momento, publicando a versão atualizada em nosso site.</p>
                    
                    <p>7.2. Ao continuar a utilizar nossos serviços após a publicação de alterações, você aceita e concorda com os novos termos.</p>
                    
                    <div class="alert alert-warning mt-4">
                        <p class="mb-0"><strong>Data da última atualização:</strong> 29 de março de 2025</p>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <a href="<?= BASE_URL ?>customer-models/upload" class="btn btn-primary">
                        <i class="fas fa-upload me-1"></i> Ir para Upload de Modelos
                    </a>
                    <a href="<?= BASE_URL ?>" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-home me-1"></i> Voltar para Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once VIEWS_PATH . '/partials/footer.php'; ?>
