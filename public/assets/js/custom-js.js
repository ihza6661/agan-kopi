(() => {
	const STORAGE_KEY = 'sidebar:collapsed';
	const sidebar = document.getElementById('appSidebar');
	const toggleBtn = document.getElementById('desktopSidebarToggle');

	const applyState = (collapsed) => {
		if (!sidebar) return;
		if (collapsed) {
			sidebar.classList.add('sidebar-collapsed');
			document.body.classList.add('sidebar-collapsed');
			toggleBtn?.setAttribute('aria-pressed', 'true');
		} else {
			sidebar.classList.remove('sidebar-collapsed');
			document.body.classList.remove('sidebar-collapsed');
			toggleBtn?.setAttribute('aria-pressed', 'false');
		}
	};

	try {
		const saved = localStorage.getItem(STORAGE_KEY);
		applyState(saved === '1');
	} catch (e) {}

	toggleBtn?.addEventListener('click', () => {
		const collapsed = !document.body.classList.contains('sidebar-collapsed');
		applyState(collapsed);
		try { localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0'); } catch (e) {}
	});
})();
