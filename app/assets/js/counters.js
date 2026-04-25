export function initCounters() {
    const counters = document.querySelectorAll('.js-counter');
    const speed = 250; // Plus le chiffre est grand, plus c'est lent

    const startCounting = (el) => {
        const target = +el.getAttribute('data-target');
        const count = +el.innerText;
        const increment = target / speed;

        if (count < target) {
            el.innerText = Math.ceil(count + increment);
            setTimeout(() => startCounting(el), 15);
        } else {
            el.innerText = target;
        }
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                startCounting(entry.target);
                observer.unobserve(entry.target); // On arrête d'observer une fois lancé
            }
        });
    }, { threshold: 0.8 });

    counters.forEach(counter => observer.observe(counter));
}