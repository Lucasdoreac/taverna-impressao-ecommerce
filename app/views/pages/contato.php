<?php
require_once 'app/views/partials/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <h1 class="mb-4">Contato</h1>
            
            <div class="card">
                <div class="card-body">
                    <form id="contactForm" method="post" action="/contato">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Assunto</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Mensagem</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Enviar Mensagem</button>
                    </form>
                </div>
            </div>
            
            <div class="mt-5">
                <h2>Informações de Contato</h2>
                <p><strong>Endereço:</strong> [Seu endereço aqui]</p>
                <p><strong>Telefone:</strong> [Seu telefone aqui]</p>
                <p><strong>Email:</strong> [Seu email aqui]</p>
                
                <div class="mt-4">
                    <h3>Horário de Atendimento</h3>
                    <p>Segunda a Sexta: 9h às 18h<br>
                    Sábado: 9h às 13h</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'app/views/partials/footer.php';
?>