        </main>
    </div>

    <!-- Mobile Navigation & Profile Dropdown Controller -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('mobileMenuBtn');
            const closeBtn = document.getElementById('mobileMenuCloseBtn');
            const backdrop = document.getElementById('mobileMenuBackdrop');
            const sidebar = document.getElementById('sidebarNav');
            const iconOpen = document.getElementById('menuIconOpen');
            const iconClose = document.getElementById('menuIconClose');

            function openMenu() {
                if (sidebar) sidebar.classList.remove('-translate-x-full');
                if (backdrop) backdrop.classList.remove('hidden');
                if (iconOpen) iconOpen.classList.add('hidden');
                if (iconClose) iconClose.classList.remove('hidden');
                document.body.classList.add('overflow-hidden', 'md:overflow-auto');
            }

            function closeMenu() {
                if (sidebar) sidebar.classList.add('-translate-x-full');
                if (backdrop) backdrop.classList.add('hidden');
                if (iconOpen) iconOpen.classList.remove('hidden');
                if (iconClose) iconClose.classList.add('hidden');
                document.body.classList.remove('overflow-hidden', 'md:overflow-auto');
            }

            if (btn) btn.addEventListener('click', () => {
                const isOpen = sidebar && !sidebar.classList.contains('-translate-x-full');
                if (isOpen) closeMenu(); else openMenu();
            });

            if (closeBtn) closeBtn.addEventListener('click', closeMenu);
            if (backdrop) backdrop.addEventListener('click', closeMenu);

            // Profile dropdown
            const profileBtn = document.getElementById('profileDropdownBtn');
            const profileMenu = document.getElementById('profileDropdownMenu');
            if (profileBtn && profileMenu) {
                profileBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    profileMenu.classList.toggle('hidden');
                });
                document.addEventListener('click', () => {
                    profileMenu.classList.add('hidden');
                });
            }
        });
    </script>
</body>
</html>
