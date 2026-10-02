/**
 * Localize Laravel paginator link labels (Previous / Next).
 */
export function formatPaginationLabel(
    label: string,
    t: (key: string) => string,
): string {
    const plain = label
        .replace(/&laquo;|&raquo;|«|»/gi, '')
        .replace(/&nbsp;/gi, ' ')
        .trim();

    if (/^(previous|назад)$/i.test(plain)) {
        return `« ${t('common.previous')}`;
    }

    if (/^(next|вперёд|вперед)$/i.test(plain)) {
        return `${t('common.next')} »`;
    }

    return label;
}
