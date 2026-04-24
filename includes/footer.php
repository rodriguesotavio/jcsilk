    </main>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var toggleBtn = document.getElementById('sidebarToggleBtn');
        var appShell = document.body;
        var collapsedClass = 'sidebar-collapsed';
        var storageKey = 'jcsilk.sidebar.collapsed';
        var tooltipTriggerList = document.querySelectorAll('.sidebar [data-bs-toggle="tooltip"]');
        var tooltipInstances = [];

        tooltipTriggerList.forEach(function (el) {
            tooltipInstances.push(new bootstrap.Tooltip(el, { trigger: 'hover focus' }));
        });

        if (!toggleBtn || !appShell) return;

        function updateSidebarTooltips() {
            var isCollapsed = appShell.classList.contains(collapsedClass);
            tooltipInstances.forEach(function (instance) {
                if (isCollapsed) {
                    instance.enable();
                } else {
                    instance.disable();
                    instance.hide();
                }
            });
        }

        try {
            if (localStorage.getItem(storageKey) === 'true') {
                appShell.classList.add(collapsedClass);
            }
        } catch (e) {}

        updateSidebarTooltips();

        toggleBtn.addEventListener('click', function () {
            appShell.classList.toggle(collapsedClass);
            try {
                localStorage.setItem(storageKey, appShell.classList.contains(collapsedClass) ? 'true' : 'false');
            } catch (e) {}
            updateSidebarTooltips();
        });
    });
</script>
</body>
</html>
<?php
if(isset($link) && is_object($link)){
    mysqli_close($link);
}
?>
