<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container" style="min-height: 70vh; display: flex; align-items: center; justify-content: center; text-align: center; padding: 5rem 1rem;">
    <div>
        <h1 style="font-size: 4rem; margin-bottom: 1rem; color: var(--primary);">404</h1>
        <h2 style="font-size: 2rem; margin-bottom: 2rem;">Página Não Encontrada</h2>
        <p style="margin-bottom: 2rem; max-width: 600px;">Ops! Parece que a página que você está procurando desapareceu em um portal dimensional ou foi devorada por um mimic!</p>
        <a href="<?= BASE_URL ?>" class="btn">Retornar à Taverna</a>
    </div>
</div>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>