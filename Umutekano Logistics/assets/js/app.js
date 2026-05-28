document.addEventListener('DOMContentLoaded', function() {
    const observers = document.querySelectorAll('.animate-on-scroll');
    if ('IntersectionObserver' in window && observers.length) {
        const scrollObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.18 });
        observers.forEach(el => scrollObserver.observe(el));
    } else {
        observers.forEach(el => el.classList.add('in-view'));
    }

    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        window.setTimeout(() => {
            alert.classList.add('fade-out');
        }, 6500);
    });

    document.querySelectorAll('.hero-feature').forEach((item, index) => {
        item.style.transitionDelay = `${index * 80}ms`;
    });

    document.querySelectorAll('.animate-on-scroll.animated-pop').forEach((el, idx) => {
        el.style.transitionDelay = `${idx * 70}ms`;
    });

    document.querySelectorAll('.sidebar a').forEach(link => {
        link.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (window.innerWidth < 900 && sidebar && overlay) {
                sidebar.classList.remove('open');
                overlay.classList.remove('open');
            }
        });
    });
});
