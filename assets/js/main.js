/**
 * main.js – Smart Stock v3.1
 */
document.addEventListener('DOMContentLoaded', function () {

    // ── Mobile sidebar
    const sidebar   = document.querySelector('.sidebar');
    const overlay   = document.querySelector('.sidebar-overlay');
    const hamburger = document.querySelector('.hamburger');
    const openSB    = () => { sidebar?.classList.add('open'); overlay?.classList.add('open'); document.body.style.overflow='hidden'; };
    const closeSB   = () => { sidebar?.classList.remove('open'); overlay?.classList.remove('open'); document.body.style.overflow=''; };
    hamburger?.addEventListener('click', openSB);
    overlay?.addEventListener('click', closeSB);
    document.querySelectorAll('.nav-item').forEach(el => el.addEventListener('click', () => { if(window.innerWidth<=768) closeSB(); }));

    // ── Topbar shadow
    const topbar = document.querySelector('.topbar');
    window.addEventListener('scroll', () => topbar?.classList.toggle('scrolled', window.scrollY > 10), { passive: true });

    // ── Flash auto-dismiss
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity .4s, transform .4s';
            el.style.opacity = '0'; el.style.transform = 'translateY(-8px)';
            setTimeout(() => el.remove(), 420);
        }, 4000);
    });

    // ── Confirm dialogs
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function(e) { if (!confirm(this.dataset.confirm || 'Are you sure?')) e.preventDefault(); });
    });

    // ── Ripple on buttons
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const r = this.getBoundingClientRect();
            const size = Math.max(r.width, r.height);
            const sp = document.createElement('span');
            sp.classList.add('ripple-circle');
            sp.style.cssText = `width:${size}px;height:${size}px;left:${e.clientX-r.left-size/2}px;top:${e.clientY-r.top-size/2}px`;
            this.appendChild(sp);
            sp.addEventListener('animationend', () => sp.remove());
        });
    });

    // ── Intersection Observer reveal
    const obs = new IntersectionObserver((entries) => {
        entries.forEach((en, i) => {
            if (en.isIntersecting) {
                setTimeout(() => en.target.classList.add('visible'), i * 60);
                obs.unobserve(en.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });
    document.querySelectorAll('.reveal, .reveal-left, .reveal-right').forEach(el => obs.observe(el));

    // ── Count-up for stat cards
    document.querySelectorAll('.stat-value[data-count]').forEach(el => {
        const target = parseFloat(el.dataset.count);
        if (isNaN(target)) return;
        const o2 = new IntersectionObserver(en => {
            if (en[0].isIntersecting) { animateCount(el, target); o2.disconnect(); }
        }, { threshold: 0.5 });
        o2.observe(el);
    });

    function animateCount(el, target, dur=1100) {
        const start = performance.now();
        const isFloat = target % 1 !== 0;
        (function step(now) {
            const p = Math.min((now-start)/dur, 1);
            const e = 1 - Math.pow(1-p, 3);
            el.textContent = isFloat ? (target*e).toFixed(2) : Math.floor(target*e).toLocaleString();
            if (p < 1) requestAnimationFrame(step);
            else el.textContent = isFloat ? target.toFixed(2) : target.toLocaleString();
        })(start);
    }

});

// ── Universal accordion toggle (used by categories & suppliers)
function toggleAccordion(trigger) {
    const row   = trigger.closest('[data-accordion-row]');
    const inner = row.querySelector('.accordion-inner');
    const id    = row.dataset.loadId;
    const url   = row.dataset.loadUrl;
    const isOpen = row.classList.contains('is-open');

    document.querySelectorAll('[data-accordion-row].is-open').forEach(r => { if(r!==row) r.classList.remove('is-open'); });

    if (isOpen) { row.classList.remove('is-open'); trigger.setAttribute('aria-expanded','false'); return; }
    row.classList.add('is-open');
    trigger.setAttribute('aria-expanded','true');

    if (!url || inner.dataset.loaded === 'true') return;

    inner.innerHTML = `<div class="products-loading"><div class="spinner"></div>Loading…</div>`;

    fetch(window.BASE_URL + url + '?id=' + id)
        .then(r => r.json())
        .then(data => {
            inner.dataset.loaded = 'true';
            const renderer = window['render_' + (row.dataset.renderer || 'products')];
            if (renderer) inner.innerHTML = renderer(data);
            else inner.innerHTML = '<div class="alert alert-danger">No renderer found.</div>';
        })
        .catch(() => { inner.innerHTML = '<div class="alert alert-danger" style="margin:12px;">Failed to load. Please try again.</div>'; });
}

// ── Product card renderer (for categories)
window.render_products = function(data) {
    if (!data.products || !data.products.length)
        return '<div class="empty-state" style="padding:28px;"><div class="empty-state-icon">📦</div><div class="empty-state-msg">No active products in this category.</div></div>';
    return '<div class="products-grid">' + data.products.map(p => {
        const qty=parseInt(p.stock_qty), min=parseInt(p.min_stock_level);
        const cls = qty===0?'out':qty<=min?'low':'ok';
        const lbl = qty===0?'Out of Stock':qty<=min?'Low: '+qty:qty+' in stock';
        return `<a href="${window.BASE_URL}/inventory/view.php?id=${p.id}" class="product-mini-card">
            <div class="pmcard-sku">${p.sku}</div>
            <div class="pmcard-name">${p.name}</div>
            <div class="pmcard-footer">
                <span class="pmcard-price">$${parseFloat(p.price).toFixed(2)}</span>
                <span class="pmcard-stock ${cls}">${lbl}</span>
            </div></a>`;
    }).join('') + '</div>';
};

// ── Supplier product renderer
window.render_supplier_products = function(data) {
    if (!data.products || !data.products.length)
        return '<div class="empty-state" style="padding:28px;"><div class="empty-state-icon">📦</div><div class="empty-state-msg">No active products from this supplier.</div></div>';
    return '<div class="products-grid">' + data.products.map(p => {
        const qty=parseInt(p.stock_qty), min=parseInt(p.min_stock_level);
        const cls = qty===0?'out':qty<=min?'low':'ok';
        const lbl = qty===0?'Out of Stock':qty<=min?'Low: '+qty:qty+' in stock';
        return `<a href="${window.BASE_URL}/inventory/view.php?id=${p.id}" class="product-mini-card">
            <div class="pmcard-sku">${p.sku}</div>
            <div class="pmcard-name">${p.name}</div>
            <div class="pmcard-footer">
                <span class="pmcard-price">$${parseFloat(p.price).toFixed(2)}</span>
                <span class="pmcard-stock ${cls}">${lbl}</span>
            </div></a>`;
    }).join('') + '</div>';
};

// Keyboard support for accordions
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.accordion-trigger').forEach(t => {
        t.addEventListener('keydown', e => {
            if (e.key==='Enter'||e.key===' ') { e.preventDefault(); toggleAccordion(t); }
        });
    });
});
