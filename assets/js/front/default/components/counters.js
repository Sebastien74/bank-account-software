/**
 * Counter
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (counters) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const start = parseFloat(counter.dataset.counterStart.replace(",", ".")) || 0;
                const endValue = counter.dataset.counterEnd.replace(",", ".");
                const end = parseFloat(endValue);
                const duration = parseInt(counter.dataset.counterTime) || 2000;
                const separator = counter.dataset.counterSeparator !== undefined ? JSON.parse(counter.dataset.counterSeparator) : false;
                let decimals = counter.dataset.counterDecimals ? parseInt(counter.dataset.counterDecimals) : 0;
                if (endValue && /[.,]/.test(endValue)) {
                    const decimalPlaces = endValue.split(/[.,]/)[1];
                    decimals = decimalPlaces ? decimalPlaces.length : decimals;
                }
                animateCounter(counter, start, end, duration, decimals, separator);
                observer.unobserve(counter);
            }
        });
    }, {
        threshold: 0.5
    });
    counters.forEach(counter => {
        observer.observe(counter);
    });

    function animateCounter(counter, start, end, duration, decimals, separator) {
        const startTime = performance.now();
        let current = start;
        function update() {
            const elapsed = performance.now() - startTime;
            const progress = elapsed / duration;
            current = start + (end - start) * progress;
            if ((end > start && current >= end) || (end < start && current <= end)) {
                current = end;
                counter.textContent = formatValue(current, decimals, separator);
                return;
            }
            counter.textContent = formatValue(current, decimals, separator);
            requestAnimationFrame(update);
        }

        requestAnimationFrame(update);
    }

    function formatValue(value, decimals, separator) {
        if (isNaN(value)) {
            console.error("La valeur n'est pas un nombre valide !");
            return "";
        }
        let formattedValue;
        if (decimals > 0) {
            formattedValue = value.toFixed(decimals);
        } else {
            formattedValue = Math.round(value).toString();
        }
        formattedValue = formattedValue.replace('.', ',');
        if (separator) {
            formattedValue = formattedValue.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        return formattedValue;
    }
}