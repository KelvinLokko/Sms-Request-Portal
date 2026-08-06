const prefersReducedMotion = window.matchMedia(
    '(prefers-reduced-motion: reduce)',
).matches;

function revealOnScroll(): void {
    const targets = Array.from(
        document.querySelectorAll<HTMLElement>('[data-reveal]'),
    );

    if (targets.length === 0) {
        return;
    }

    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        targets.forEach((target) => target.classList.add('is-revealed'));

        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                const target = entry.target as HTMLElement;
                const delay = Number(target.dataset.revealDelay ?? 0);
                target.style.setProperty('--reveal-delay', `${delay}ms`);
                target.classList.add('is-revealed');
                observer.unobserve(target);
            });
        },
        { rootMargin: '0px 0px -10% 0px', threshold: 0.15 },
    );

    targets.forEach((target) => observer.observe(target));
}

function formatCount(value: number, decimals: number): string {
    return value.toLocaleString(undefined, {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

function countUp(element: HTMLElement): void {
    const target = Number(element.dataset.countup ?? 0);
    const decimals = Number(element.dataset.countupDecimals ?? 0);
    const duration = Number(element.dataset.countupDuration ?? 1600);
    const start = performance.now();

    function frame(now: number): void {
        const progress = Math.min((now - start) / duration, 1);
        // Ease-out cubic keeps the number lively at the start and settled at the end.
        const eased = 1 - Math.pow(1 - progress, 3);
        element.textContent = formatCount(target * eased, decimals);

        if (progress < 1) {
            requestAnimationFrame(frame);
        }
    }

    requestAnimationFrame(frame);
}

function animateCounters(): void {
    const counters = Array.from(
        document.querySelectorAll<HTMLElement>('[data-countup]'),
    );

    if (counters.length === 0) {
        return;
    }

    const settle = (element: HTMLElement) => {
        element.textContent = formatCount(
            Number(element.dataset.countup ?? 0),
            Number(element.dataset.countupDecimals ?? 0),
        );
    };

    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        counters.forEach(settle);

        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                countUp(entry.target as HTMLElement);
                observer.unobserve(entry.target);
            });
        },
        { threshold: 0.4 },
    );

    counters.forEach((counter) => observer.observe(counter));
}

function stickyHeader(): void {
    const header = document.querySelector<HTMLElement>('[data-site-header]');

    if (!header) {
        return;
    }

    const sync = () => {
        header.classList.toggle('is-stuck', window.scrollY > 24);
    };

    sync();
    window.addEventListener('scroll', sync, { passive: true });
}

function closeMobileMenuOnNavigate(): void {
    const menu =
        document.querySelector<HTMLDetailsElement>('[data-mobile-menu]');

    if (!menu) {
        return;
    }

    menu.addEventListener('click', (event) => {
        if ((event.target as HTMLElement).closest('a')) {
            menu.open = false;
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            menu.open = false;
        }
    });
}

revealOnScroll();
animateCounters();
stickyHeader();
closeMobileMenuOnNavigate();
