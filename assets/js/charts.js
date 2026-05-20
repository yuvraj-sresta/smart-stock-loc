/**
 * charts.js – Smart Stock Dashboard Charts v2
 * NOTE: Loaded at bottom of page so DOM is already ready — no DOMContentLoaded needed
 */

const PALETTE = [
    '#6366f1','#06b6d4','#10b981','#f43f5e','#f59e0b','#8b5cf6','#0891b2','#ec4899'
];

function ssChartDefaults() {
    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.font.size   = 12;
    Chart.defaults.color       = '#64748b';
    Chart.defaults.plugins.tooltip.backgroundColor = '#0f172a';
    Chart.defaults.plugins.tooltip.titleColor      = '#f8fafc';
    Chart.defaults.plugins.tooltip.bodyColor       = '#94a3b8';
    Chart.defaults.plugins.tooltip.borderColor     = '#1e293b';
    Chart.defaults.plugins.tooltip.borderWidth     = 1;
    Chart.defaults.plugins.tooltip.padding         = 12;
    Chart.defaults.plugins.tooltip.cornerRadius    = 10;
    Chart.defaults.plugins.tooltip.boxPadding      = 4;
}

function initDonut(canvasId, data) {
    var el = document.getElementById(canvasId);
    if (!el || !data || !data.labels.length) return;
    new Chart(el.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values,
                backgroundColor: PALETTE.slice(0, data.labels.length),
                borderColor: '#ffffff',
                borderWidth: 3,
                hoverOffset: 12,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '68%',
            animation: { animateScale: true, animateRotate: true, duration: 900, easing: 'easeInOutQuart' },
            plugins: {
                legend: { position: 'bottom', labels: { padding: 18, usePointStyle: true, pointStyleWidth: 10, font: { size: 11, weight: '600' } } },
                tooltip: { callbacks: { label: function(c) {
                    var total = c.dataset.data.reduce(function(a,b){return a+b;},0);
                    return '  '+c.label+': '+c.raw+' units ('+(c.raw/total*100).toFixed(1)+'%)';
                }}}
            }
        }
    });
}

function initBar(canvasId, data) {
    var el = document.getElementById(canvasId);
    if (!el || !data || !data.labels.length) return;
    new Chart(el.getContext('2d'), {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Stock Value (AUD)',
                data: data.values,
                backgroundColor: PALETTE.slice(0, data.labels.length),
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            animation: { duration: 900, easing: 'easeInOutQuart', delay: function(c){ return c.dataIndex * 80; } },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function(c){ return '  Value: $'+Number(c.raw).toFixed(2); } } }
            },
            scales: {
                x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 11, weight: '600' } } },
                y: { grid: { color: 'rgba(0,0,0,0.04)' }, border: { display: false }, ticks: { callback: function(v){ return '$'+v.toLocaleString(); }, font: { size: 10 } }, beginAtZero: true }
            }
        }
    });
}

function initLine(canvasId, data) {
    var el = document.getElementById(canvasId);
    if (!el || !data || !data.labels.length) return;
    var ctx = el.getContext('2d');
    var g1 = ctx.createLinearGradient(0,0,0,180);
    g1.addColorStop(0,'rgba(99,102,241,0.25)'); g1.addColorStop(1,'rgba(99,102,241,0)');
    var g2 = ctx.createLinearGradient(0,0,0,180);
    g2.addColorStop(0,'rgba(16,185,129,0.18)'); g2.addColorStop(1,'rgba(16,185,129,0)');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                { label:'Transactions', data:data.counts, borderColor:'#6366f1', backgroundColor:g1, borderWidth:2.5, fill:true, tension:0.45, pointRadius:5, pointHoverRadius:7, pointBackgroundColor:'#fff', pointBorderColor:'#6366f1', pointBorderWidth:2.5 },
                { label:'Units Moved',  data:data.units,  borderColor:'#10b981', backgroundColor:g2, borderWidth:2, fill:true, tension:0.45, pointRadius:4, pointHoverRadius:6, pointBackgroundColor:'#fff', pointBorderColor:'#10b981', pointBorderWidth:2, borderDash:[5,3] }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: { mode:'index', intersect:false },
            animation: { duration:1000, easing:'easeInOutQuart' },
            plugins: {
                legend: { position:'top', align:'end', labels:{ usePointStyle:true, pointStyleWidth:8, padding:16, font:{ size:11, weight:'600' } } },
                tooltip: { callbacks: { title:function(i){ return 'Date: '+i[0].label; }, label:function(c){ return '  '+c.dataset.label+': '+c.raw; } } }
            },
            scales: {
                x: { grid:{ display:false }, border:{ display:false }, ticks:{ font:{ size:10 } } },
                y: { grid:{ color:'rgba(0,0,0,0.04)' }, border:{ display:false }, ticks:{ font:{ size:10 }, stepSize:1 }, beginAtZero:true }
            }
        }
    });
}

// ── Run immediately (DOM already ready since script is at bottom of page)
(function() {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js not loaded — check chart.min.js');
        return;
    }
    ssChartDefaults();
    var cd = window.SmartStockCharts;
    if (!cd) { console.error('No chart data found (window.SmartStockCharts)'); return; }
    initDonut('donutChart', cd.donut);
    initBar('barChart', cd.bar);
    initLine('lineChart', cd.line);
})();
