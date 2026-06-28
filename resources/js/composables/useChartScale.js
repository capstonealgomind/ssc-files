export function niceChartMax(peak) {
    if (peak <= 0) {
        return 4;
    }

    const target = peak * 1.15;

    if (target <= 2) {
        return Math.max(2, Math.ceil(target));
    }

    if (target <= 6) {
        return Math.ceil(target);
    }

    if (target <= 10) {
        return 10;
    }

    const magnitude = 10 ** Math.floor(Math.log10(target));
    const normalized = target / magnitude;
    const nice = normalized <= 2 ? 2 : normalized <= 5 ? 5 : 10;

    return nice * magnitude;
}

export function chartTicks(max) {
    if (max <= 2) {
        return [0, 1, max];
    }

    if (max <= 5) {
        return [0, Math.ceil(max / 2), max];
    }

    const step = max / 4;
    return [0, step, step * 2, step * 3, max].map((value) => Math.round(value));
}
