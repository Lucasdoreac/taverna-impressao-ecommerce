<div class="container my-5">
    <div class="row">
        <!-- Menu lateral -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Minha Conta</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?php echo BASE_URL; ?>minha-conta" class="list-group-item list-group-item-action">
                        <i class="fas fa-home me-2"></i> Dashboard
                    </a>
                    <a href="<?php echo BASE_URL; ?>minha-conta/perfil" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> Meu Perfil
                    </a>
                    <a href="<?php echo BASE_URL; ?>minha-conta/pedidos" class="list-group-item list-group-item-action">
                        <i class="fas fa-shopping-bag me-2"></i> Meus Pedidos
                    </a>
                    <a href="<?php echo BASE_URL; ?>minha-conta/enderecos" class="list-group-item list-group-item-action active">
                        <i class="fas fa-map-marker-alt me-2"></i> Meus Endereços
                    </a>
                    <a href="<?php echo BASE_URL; ?>logout" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Sair
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Conteúdo principal -->
        <div class="col-md-9">
            <?php if (isset($success) && $success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo h($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error) && $error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo h($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><?php echo $isEdit ? 'Editar Endereço' : 'Adicionar Endereço'; ?></h4>
                    <a href="<?php echo BASE_URL; ?>minha-conta/enderecos" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Voltar
                    </a>
                </div>
                <div class="card-body">
                    <form action="<?php echo $isEdit ? BASE_URL . 'minha-conta/enderecos/editar/' . h($address['id']) : BASE_URL . 'minha-conta/enderecos/adicionar'; ?>" method="post" id="addressForm">
                        <?php echo $csrfToken; ?>
                        
                        <?php if ($isEdit): ?>
                            <input type="hidden" name="id" value="<?php echo h($address['id']); ?>">
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="postal_code" class="form-label">CEP</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo $isEdit ? h($address['postal_code']) : ''; ?>" required maxlength="9" placeholder="00000-000">
                                    <button class="btn btn-outline-secondary" type="button" id="cep_search">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                <div class="form-text">Digite o CEP para preenchimento automático</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="street" class="form-label">Rua/Avenida</label>
                                <input type="text" class="form-control" id="street" name="street" value="<?php echo $isEdit ? h($address['street']) : ''; ?>" required maxlength="100">
                            </div>
                            <div class="col-md-4">
                                <label for="number" class="form-label">Número</label>
                                <input type="text" class="form-control" id="number" name="number" value="<?php echo $isEdit ? h($address['number']) : ''; ?>" required maxlength="20">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="complement" class="form-label">Complemento</label>
                                <input type="text" class="form-control" id="complement" name="complement" value="<?php echo $isEdit ? h($address['complement']) : ''; ?>" maxlength="100" placeholder="Apartamento, bloco, etc. (opcional)">
                            </div>
                            <div class="col-md-6">
                                <label for="neighborhood" class="form-label">Bairro</label>
                                <input type="text" class="form-control" id="neighborhood" name="neighborhood" value="<?php echo $isEdit ? h($address['neighborhood']) : ''; ?>" required maxlength="100">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="city" class="form-label">Cidade</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo $isEdit ? h($address['city']) : ''; ?>" required maxlength="100">
                            </div>
                            <div class="col-md-4">
                                <label for="state" class="form-label">Estado</label>
                                <select class="form-select" id="state" name="state" required>
                                    <option value="">Selecione...</option>
                                    <option value="AC" <?php echo ($isEdit && $address['state'] == 'AC') ? 'selected' : ''; ?>>Acre</option>
                                    <option value="AL" <?php echo ($isEdit && $address['state'] == 'AL') ? 'selected' : ''; ?>>Alagoas</option>
                                    <option value="AP" <?php echo ($isEdit && $address['state'] == 'AP') ? 'selected' : ''; ?>>Amapá</option>
                                    <option value="AM" <?php echo ($isEdit && $address['state'] == 'AM') ? 'selected' : ''; ?>>Amazonas</option>
                                    <option value="BA" <?php echo ($isEdit && $address['state'] == 'BA') ? 'selected' : ''; ?>>Bahia</option>
                                    <option value="CE" <?php echo ($isEdit && $address['state'] == 'CE') ? 'selected' : ''; ?>>Ceará</option>
                                    <option value="DF" <?php echo ($isEdit && $address['state'] == 'DF') ? 'selected' : ''; ?>>Distrito Federal</option>
                                    <option value="ES" <?php echo ($isEdit && $address['state'] == 'ES') ? 'selected' : ''; ?>>Espírito Santo</option>
                                    <option value="GO" <?php echo ($isEdit && $address['state'] == 'GO') ? 'selected' : ''; ?>>Goiás</option>
                                    <option value="MA" <?php echo ($isEdit && $address['state'] == 'MA') ? 'selected' : ''; ?>>Maranhão</option>
                                    <option value="MT" <?php echo ($isEdit && $address['state'] == 'MT') ? 'selected' : ''; ?>>Mato Grosso</option>
                                    <option value="MS" <?php echo ($isEdit && $address['state'] == 'MS') ? 'selected' : ''; ?>>Mato Grosso do Sul</option>
                                    <option value="MG" <?php echo ($isEdit && $address['state'] == 'MG') ? 'selected' : ''; ?>>Minas Gerais</option>
                                    <option value="PA" <?php echo ($isEdit && $address['state'] == 'PA') ? 'selected' : ''; ?>>Pará</option>
                                    <option value="PB" <?php echo ($isEdit && $address['state'] == 'PB') ? 'selected' : ''; ?>>Paraíba</option>
                                    <option value="PR" <?php echo ($isEdit && $address['state'] == 'PR') ? 'selected' : ''; ?>>Paraná</option>
                                    <option value="PE" <?php echo ($isEdit && $address['state'] == 'PE') ? 'selected' : ''; ?>>Pernambuco</option>
                                    <option value="PI" <?php echo ($isEdit && $address['state'] == 'PI') ? 'selected' : ''; ?>>Piauí</option>
                                    <option value="RJ" <?php echo ($isEdit && $address['state'] == 'RJ') ? 'selected' : ''; ?>>Rio de Janeiro</option>
                                    <option value="RN" <?php echo ($isEdit && $address['state'] == 'RN') ? 'selected' : ''; ?>>Rio Grande do Norte</option>
                                    <option value="RS" <?php echo ($isEdit && $address['state'] == 'RS') ? 'selected' : ''; ?>>Rio Grande do Sul</option>
                                    <option value="RO" <?php echo ($isEdit && $address['state'] == 'RO') ? 'selected' : ''; ?>>Rondônia</option>
                                    <option value="RR" <?php echo ($isEdit && $address['state'] == 'RR') ? 'selected' : ''; ?>>Roraima</option>
                                    <option value="SC" <?php echo ($isEdit && $address['state'] == 'SC') ? 'selected' : ''; ?>>Santa Catarina</option>
                                    <option value="SP" <?php echo ($isEdit && $address['state'] == 'SP') ? 'selected' : ''; ?>>São Paulo</option>
                                    <option value="SE" <?php echo ($isEdit && $address['state'] == 'SE') ? 'selected' : ''; ?>>Sergipe</option>
                                    <option value="TO" <?php echo ($isEdit && $address['state'] == 'TO') ? 'selected' : ''; ?>>Tocantins</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" <?php echo ($isEdit && $address['is_default']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_default">
                                Definir como endereço principal
                            </label>
                            <div class="form-text">Este endereço será usado por padrão para suas compras</div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='<?php echo BASE_URL; ?>minha-conta/enderecos'">
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> <?php echo $isEdit ? 'Atualizar Endereço' : 'Salvar Endereço'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Máscara para CEP
    const postalCode = document.getElementById('postal_code');
    if (postalCode) {
        postalCode.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 8) value = value.slice(0, 8);
            
            if (value.length > 5) {
                value = value.slice(0, 5) + '-' + value.slice(5);
            }
            
            e.target.value = value;
        });
    }
    
    // Consulta de CEP
    const cepSearch = document.getElementById('cep_search');
    if (cepSearch) {
        cepSearch.addEventListener('click', function() {
            const cep = postalCode.value.replace(/\D/g, '');
            
            if (cep.length !== 8) {
                alert('Por favor, digite um CEP válido com 8 dígitos');
                return;
            }
            
            // Mostrar loading
            cepSearch.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            cepSearch.disabled = true;
            
            // Fazer consulta do CEP usando API pública
            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (data.erro) {
                        alert('CEP não encontrado');
                    } else {
                        // Preencher campos
                        document.getElementById('street').value = data.logradouro;
                        document.getElementById('neighborhood').value = data.bairro;
                        document.getElementById('city').value = data.localidade;
                        document.getElementById('state').value = data.uf;
                        
                        // Focar no campo número
                        document.getElementById('number').focus();
                    }
                })
                .catch(error => {
                    alert('Erro ao consultar CEP: ' + error.message);
                })
                .finally(() => {
                    // Restaurar botão
                    cepSearch.innerHTML = '<i class="fas fa-search"></i>';
                    cepSearch.disabled = false;
                });
        });
    }
    
    // Validação do formulário
    const form = document.getElementById('addressForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            let isValid = true;
            
            // Validar CEP
            const cep = postalCode.value.replace(/\D/g, '');
            if (cep.length !== 8) {
                alert('Por favor, digite um CEP válido com 8 dígitos');
                postalCode.focus();
                event.preventDefault();
                return;
            }
            
            // Validar demais campos
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                event.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios');
            }
        });
    }
});
</script>