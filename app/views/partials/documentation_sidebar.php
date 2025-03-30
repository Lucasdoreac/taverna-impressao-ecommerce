<?php
/**
 * Sidebar de navegação para a documentação de usuários
 * 
 * @version 1.0
 */
?>
<div class="doc-sidebar">
    <div class="doc-sidebar-header">
        <h3>Documentação</h3>
        <button id="closeSidebarBtn" class="doc-sidebar-close visible-xs visible-sm">
            <i class="fa fa-times"></i>
        </button>
    </div>
    
    <div class="doc-sidebar-content">
        <ul class="doc-menu">
            <?php foreach ($data['sections'] as $sectionKey => $section): ?>
                <li class="doc-menu-item <?php echo ($data['activePage'] == $sectionKey) ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($section['url']); ?>" class="doc-menu-link">
                        <i class="fa <?php echo htmlspecialchars($section['icon']); ?>"></i>
                        <?php echo htmlspecialchars($section['title']); ?>
                    </a>
                    
                    <?php if (isset($section['subsections']) && $data['activePage'] == $sectionKey): ?>
                        <ul class="doc-submenu">
                            <?php foreach ($section['subsections'] as $subKey => $subTitle): ?>
                                <li class="doc-submenu-item">
                                    <a href="#<?php echo htmlspecialchars($subKey); ?>" class="doc-submenu-link">
                                        <?php echo htmlspecialchars($subTitle); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <div class="doc-sidebar-footer">
        <form action="?page=documentation&action=search" method="get" class="doc-search-form">
            <input type="hidden" name="page" value="documentation">
            <input type="hidden" name="action" value="search">
            <div class="input-group">
                <input type="text" name="q" class="form-control doc-search-input" 
                       placeholder="Buscar na documentação..."
                       value="<?php echo isset($data['query']) ? htmlspecialchars($data['query']) : ''; ?>">
                <span class="input-group-btn">
                    <button class="btn btn-primary doc-search-btn" type="submit">
                        <i class="fa fa-search"></i>
                    </button>
                </span>
            </div>
        </form>
    </div>
</div>

<div class="doc-sidebar-backdrop visible-xs visible-sm"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar on mobile
    const toggleBtn = document.getElementById('toggleSidebarBtn');
    const closeBtn = document.getElementById('closeSidebarBtn');
    const sidebar = document.querySelector('.doc-sidebar');
    const backdrop = document.querySelector('.doc-sidebar-backdrop');
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            backdrop.classList.toggle('visible');
            document.body.classList.toggle('sidebar-open');
        });
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            sidebar.classList.remove('open');
            backdrop.classList.remove('visible');
            document.body.classList.remove('sidebar-open');
        });
    }
    
    if (backdrop) {
        backdrop.addEventListener('click', function() {
            sidebar.classList.remove('open');
            backdrop.classList.remove('visible');
            document.body.classList.remove('sidebar-open');
        });
    }
    
    // Smooth scroll for anchor links
    document.querySelectorAll('.doc-submenu-link').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                e.preventDefault();
                
                // On mobile, close sidebar when clicking a link
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('open');
                    backdrop.classList.remove('visible');
                    document.body.classList.remove('sidebar-open');
                }
                
                // Smooth scroll to target
                window.scrollTo({
                    top: targetElement.offsetTop - 70, // Adjust for header height
                    behavior: 'smooth'
                });
                
                // Update URL
                history.pushState(null, null, '#' + targetId);
            }
        });
    });
});
</script>
