import { createPopper } from '@popperjs/core';
import './bootstrap';
import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

window.Alpine = Alpine;
window.createPopper = createPopper;
window.flatpickr = flatpickr;

Alpine.start();

// Lazy load heavy components on DOM ready only when present in DOM
document.addEventListener('DOMContentLoaded', () => {
    // Map imports
    if (document.querySelector('#mapOne')) {
        import('./components/map').then(module => module.initMap());
    }

    // Chart imports (ApexCharts lazy loaded with charts)
    if (document.querySelector('[id^="chart"]')) {
        import('apexcharts').then(ApexCharts => {
            window.ApexCharts = ApexCharts.default || ApexCharts;

            if (document.querySelector('#chartOne')) {
                import('./components/chart/chart-1').then(module => module.initChartOne());
            }
            if (document.querySelector('#chartTwo')) {
                import('./components/chart/chart-2').then(module => module.initChartTwo());
            }
            if (document.querySelector('#chartThree')) {
                import('./components/chart/chart-3').then(module => module.initChartThree());
            }
            if (document.querySelector('#chartSix')) {
                import('./components/chart/chart-6').then(module => module.initChartSix());
            }
            if (document.querySelector('#chartEight')) {
                import('./components/chart/chart-8').then(module => module.initChartEight());
            }
            if (document.querySelector('#chartThirteen')) {
                import('./components/chart/chart-13').then(module => module.initChartThirteen());
            }
        });
    }

    // Calendar init (Lazy loaded only when calendar element exists)
    if (document.querySelector('#calendar')) {
        import('./components/calendar-init').then(module => module.calendarInit());
    }
});
