import './bootstrap';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

const initThemeToggle = () => {
    const toggle = document.querySelector('[data-theme-toggle]');

    if (!toggle) {
        return;
    }

    const icon = toggle.querySelector('[data-theme-icon]');
    const label = toggle.querySelector('.theme-toggle-label');
    const themeColor = document.querySelector('meta[name="theme-color"]');

    const applyTheme = theme => {
        const isDark = theme === 'dark';

        document.documentElement.dataset.theme = theme;
        document.documentElement.style.colorScheme = theme;
        toggle.setAttribute('aria-label', isDark ? 'تفعيل الوضع الفاتح' : 'تفعيل الوضع الداكن');

        if (label) {
            label.textContent = isDark ? 'الوضع الفاتح' : 'الوضع الداكن';
        }

        if (icon) {
            icon.className = isDark ? 'bi bi-sun' : 'bi bi-moon-stars';
            icon.setAttribute('data-theme-icon', '');
            icon.setAttribute('aria-hidden', 'true');
        }

        themeColor?.setAttribute('content', isDark ? '#121110' : '#292524');
    };

    applyTheme(document.documentElement.dataset.theme || 'light');

    toggle.addEventListener('click', () => {
        const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';

        try {
            localStorage.setItem('cativa-theme', nextTheme);
        } catch (error) {
            // The theme still works for the current page when storage is unavailable.
        }

        applyTheme(nextTheme);
    });

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
        try {
            if (localStorage.getItem('cativa-theme')) {
                return;
            }
        } catch (error) {
            return;
        }

        applyTheme(event.matches ? 'dark' : 'light');
    });
};

document.addEventListener('DOMContentLoaded', initThemeToggle);

const initProductMasonry = () => {
    const galleries = document.querySelectorAll('.product-gallery');

    galleries.forEach(gallery => {
        gallery.classList.add('is-masonry');

        const layoutItems = () => {
            const styles = window.getComputedStyle(gallery);
            const rowHeight = Number.parseFloat(styles.gridAutoRows);
            const rowGap = Number.parseFloat(styles.rowGap);

            gallery.querySelectorAll('.product-gallery-item').forEach(item => {
                const content = item.firstElementChild;

                if (!content) {
                    return;
                }

                item.style.gridRowEnd = 'auto';
                const itemHeight = content.getBoundingClientRect().height;
                const rowSpan = Math.ceil((itemHeight + rowGap) / (rowHeight + rowGap));
                item.style.gridRowEnd = 'span ' + rowSpan;
            });
        };

        const scheduleLayout = () => window.requestAnimationFrame(layoutItems);

        gallery.querySelectorAll('img').forEach(image => {
            if (!image.complete) {
                image.addEventListener('load', scheduleLayout, { once: true });
            }
        });

        scheduleLayout();
        window.addEventListener('resize', scheduleLayout, { passive: true });
    });
};

document.addEventListener('DOMContentLoaded', initProductMasonry);

const initProductImageZoom = () => {
    const supportsHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

    if (!supportsHover) {
        return;
    }

    document.querySelectorAll('.product-main-image-frame').forEach(frame => {
        const sourceImage = frame.querySelector(':scope > .product-detail-image');

        if (!sourceImage) {
            return;
        }

        const lens = document.createElement('div');
        lens.className = 'product-image-lens';
        lens.setAttribute('aria-hidden', 'true');
        const zoomedImage = document.createElement('img');
        zoomedImage.src = sourceImage.currentSrc || sourceImage.src;
        zoomedImage.alt = '';
        zoomedImage.draggable = false;
        lens.append(zoomedImage);
        frame.append(lens);

        const updateLens = event => {
            const frameRect = frame.getBoundingClientRect();
            const x = Math.min(Math.max(event.clientX - frameRect.left, 0), frameRect.width);
            const y = Math.min(Math.max(event.clientY - frameRect.top, 0), frameRect.height);
            const lensWidth = lens.clientWidth;
            const lensHeight = lens.clientHeight;
            const zoom = 2.5;

            lens.style.left = x + 'px';
            lens.style.top = y + 'px';
            zoomedImage.style.width = frameRect.width * zoom + 'px';
            zoomedImage.style.height = frameRect.height * zoom + 'px';
            zoomedImage.style.left = lensWidth / 2 - x * zoom + 'px';
            zoomedImage.style.top = lensHeight / 2 - y * zoom + 'px';
        };

        frame.addEventListener('mouseenter', event => {
            updateLens(event);
            lens.classList.add('is-active');
        });
        frame.addEventListener('mousemove', updateLens);
        frame.addEventListener('mouseleave', () => lens.classList.remove('is-active'));
    });
};

document.addEventListener('DOMContentLoaded', initProductImageZoom);
