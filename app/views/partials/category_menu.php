<?php
/**
 * Partial view para exibir o menu hierárquico de categorias
 * Utiliza recursividade para exibir categorias e subcategorias
 */
?>

<div class="category-menu">
    <?php if (!empty($categories)): ?>
        <ul class="list-group category-tree">
            <?php renderCategoryMenu($categories); ?>
        </ul>
    <?php else: ?>
        <div class="alert alert-info">Nenhuma categoria disponível.</div>
    <?php endif; ?>
</div>

<?php
/**
 * Função auxiliar para renderizar o menu de categorias de forma recursiva
 */
function renderCategoryMenu($categories, $level = 0) {
    foreach ($categories as $category) {
        $hasChildren = !empty($category['subcategories']);
        $indentation = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
        $activeClass = '';
        
        // Verificar se a categoria atual é a categoria ativa (baseado na URL atual)
        $currentUrl = $_SERVER['REQUEST_URI'];
        $categoryUrl = BASE_URL . 'categoria/' . $category['slug'];
        if (strpos($currentUrl, $categoryUrl) !== false) {
            $activeClass = ' active';
        }
        
        echo '<li class="list-group-item category-item' . $activeClass . '" data-level="' . $level . '">';
        
        // Ícone para indicar se há subcategorias
        if ($hasChildren) {
            echo '<span class="category-toggle"><i class="fas fa-caret-right"></i>&nbsp;</span>';
        } else {
            echo '<span class="category-indent">' . $indentation . '</span>';
        }
        
        // Link da categoria
        echo '<a href="' . BASE_URL . 'categoria/' . $category['slug'] . '" class="category-link">';
        echo $category['name'];
        
        // Mostrar contador de produtos se disponível
        if (isset($category['product_count']) && $category['product_count'] > 0) {
            echo ' <span class="badge bg-secondary rounded-pill">' . $category['product_count'] . '</span>';
        }
        
        echo '</a>';
        
        // Renderizar subcategorias recursivamente
        if ($hasChildren) {
            echo '<ul class="list-group category-children" style="display: ' . ($activeClass ? 'block' : 'none') . ';">';
            renderCategoryMenu($category['subcategories'], $level + 1);
            echo '</ul>';
        }
        
        echo '</li>';
    }
}
?>

<style>
.category-tree {
    border: 0;
}

.category-item {
    border-left: 0;
    border-right: 0;
    padding: 0.5rem 0.25rem;
    position: relative;
}

.category-item[data-level="0"] {
    font-weight: bold;
}

.category-item.active > .category-link {
    color: #0d6efd;
    font-weight: bold;
}

.category-toggle {
    cursor: pointer;
    display: inline-block;
    width: 20px;
    margin-right: 5px;
}

.category-toggle .fa-caret-down, 
.category-toggle .fa-caret-right {
    transition: transform 0.2s ease-in-out;
}

.category-indent {
    display: inline-block;
    width: 20px;
}

.category-children {
    padding-left: 20px;
    margin-top: 5px;
}

.category-link {
    text-decoration: none;
    color: #333;
}

.category-link:hover {
    text-decoration: underline;
    color: #0d6efd;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle para mostrar/esconder subcategorias
    document.querySelectorAll('.category-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            const item = this.closest('.category-item');
            const children = item.querySelector('.category-children');
            const icon = this.querySelector('i');
            
            if (children) {
                if (children.style.display === 'none') {
                    children.style.display = 'block';
                    icon.classList.remove('fa-caret-right');
                    icon.classList.add('fa-caret-down');
                } else {
                    children.style.display = 'none';
                    icon.classList.remove('fa-caret-down');
                    icon.classList.add('fa-caret-right');
                }
            }
        });
    });
    
    // Auto-expandir categorias ativas
    document.querySelectorAll('.category-item.active').forEach(function(item) {
        let parent = item.parentElement;
        while (parent && parent.classList.contains('category-children')) {
            parent.style.display = 'block';
            
            // Atualizar ícone do toggle
            const parentItem = parent.closest('.category-item');
            if (parentItem) {
                const icon = parentItem.querySelector('.category-toggle i');
                if (icon) {
                    icon.classList.remove('fa-caret-right');
                    icon.classList.add('fa-caret-down');
                }
            }
            
            parent = parent.parentElement.parentElement;
        }
    });
});
</script>
