<?php
/**
 * =============================================================================
 * Moal General Suppliers - Staff Portal Footer & Controllers
 * =============================================================================
 */
?>
    </div> <!-- /admin-container -->

    <footer style="background: #FFFFFF; border-top: 1px solid var(--c-border); padding: 0.85rem 2rem; font-size: 0.82rem; color: var(--c-muted); display: flex; justify-content: space-between; align-items: center; margin-top: auto; flex-wrap: wrap; gap: 0.5rem;">
        <div>
            &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> &bull; Staff Operations
        </div>
        <div>
            Authorized Access
        </div>
    </footer>

</div> <!-- /admin-main -->

<script>
(function() {
    // 1. Sidebar Collapse Controller with Persistence (< / >)
    var body = document.body;
    var collapseBtn = document.getElementById('sidebarCollapseBtn');
    var collapseIcon = document.getElementById('collapseIcon');

    var savedState = localStorage.getItem('moal_staff_sidebar_collapsed');
    if (savedState === 'true' && window.innerWidth > 992) {
        body.classList.add('sidebar-collapsed');
        if (collapseIcon) collapseIcon.textContent = '>';
    }

    if (collapseBtn) {
        collapseBtn.addEventListener('click', function() {
            body.classList.toggle('sidebar-collapsed');
            var isCollapsed = body.classList.contains('sidebar-collapsed');
            localStorage.setItem('moal_staff_sidebar_collapsed', isCollapsed ? 'true' : 'false');
            if (collapseIcon) {
                collapseIcon.textContent = isCollapsed ? '>' : '<';
            }
            // Trigger resize for Chart.js canvases
            setTimeout(function() {
                window.dispatchEvent(new Event('resize'));
            }, 200);
        });
    }

    // 2. Notification Dropdown Toggle
    var notifBtn = document.getElementById('notifBtn');
    var notifDropdown = document.getElementById('notifDropdown');
    if (notifBtn && notifDropdown) {
        notifBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notifDropdown.classList.toggle('show');
        });
        document.addEventListener('click', function(e) {
            if (!notifDropdown.contains(e.target) && e.target !== notifBtn) {
                notifDropdown.classList.remove('show');
            }
        });
    }

    // 3. Password visibility toggles
    document.querySelectorAll('.password-toggle-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var targetId = this.getAttribute('data-target');
            var input = document.getElementById(targetId);
            if (!input) return;
            var eyeClosed = this.querySelector('.eye-closed');
            var eyeOpen = this.querySelector('.eye-open');
            if (input.type === 'password') {
                input.type = 'text';
                if (eyeClosed) eyeClosed.style.display = 'none';
                if (eyeOpen) eyeOpen.style.display = 'block';
            } else {
                input.type = 'password';
                if (eyeClosed) eyeClosed.style.display = 'block';
                if (eyeOpen) eyeOpen.style.display = 'none';
            }
        });
    });
})();
</script>

</body>
</html>