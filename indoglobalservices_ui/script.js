document.addEventListener('DOMContentLoaded', () => {
    const investmentSlider = document.getElementById('investment');
    const invDisplay = document.getElementById('inv-display');
    const tierSelect = document.getElementById('tier-select');

    const resDaily = document.getElementById('res-daily');
    const resTotal = document.getElementById('res-total');
    const resBase = document.getElementById('res-base');
    const resCommission = document.getElementById('res-commission');

    const ROI_RATE = 0.005; // 0.5%
    const DAYS = 400;

    function formatCurrency(num) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(num);
    }

    function animateValue(obj, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);

            // Ease out quad
            const easeProgress = progress * (2 - progress);

            const current = start + easeProgress * (end - start);
            obj.innerHTML = formatCurrency(current);
            if (progress < 1) {
                window.requestAnimationFrame(step);
            } else {
                obj.innerHTML = formatCurrency(end);
            }
        };
        window.requestAnimationFrame(step);
    }

    let prevDaily = 0;
    let prevTotal = 0;
    let prevBase = 0;
    let prevCommission = 0;

    function calculate() {
        const principal = parseFloat(investmentSlider.value);
        const multiplier = parseFloat(tierSelect.value);

        invDisplay.innerText = formatCurrency(principal);

        const daily = principal * ROI_RATE;
        const totalBaseReturn = daily * DAYS; // This is always 2x the principal

        // Earning cap is Principal * Tier Multiplier
        const maxCap = principal * multiplier;

        // Allowed commission room
        const commissionRoom = Math.max(0, maxCap - totalBaseReturn);

        // Animate values
        animateValue(resDaily, prevDaily, daily, 300);
        animateValue(resTotal, prevTotal, maxCap, 500);
        animateValue(resBase, prevBase, totalBaseReturn, 300);
        animateValue(resCommission, prevCommission, commissionRoom, 400);

        prevDaily = daily;
        prevTotal = maxCap;
        prevBase = totalBaseReturn;
        prevCommission = commissionRoom;

        // Add a slight scale effect to total return box on update
        const totalBox = document.querySelector('.highlighted-box');
        totalBox.style.transform = 'scale(1.05)';
        setTimeout(() => {
            totalBox.style.transform = 'scale(1) translateX(10px)'.replace(' translateX(10px)', '');
        }, 150);
    }

    investmentSlider.addEventListener('input', calculate);
    tierSelect.addEventListener('change', calculate);

    // Initial calculation
    calculate();
});
