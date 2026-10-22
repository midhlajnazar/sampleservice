import type { ShowIf } from '@/types';

const money = new Intl.NumberFormat('en-AE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

export function formatMoney(amount: number, currency = 'AED'): string {
    return `${currency} ${money.format(amount)}`;
}

export function formatDate(
    value: string | null | undefined,
    withTime = false,
): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}),
    });
}

export function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(0)} KB`;
    }

    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

/** Tailwind classes for a status pill. */
export function statusClasses(status: string): string {
    const map: Record<string, string> = {
        pending_payment: 'bg-amber-50 text-amber-800 ring-amber-200',
        submitted: 'bg-sky-50 text-sky-800 ring-sky-200',
        under_review: 'bg-indigo-50 text-indigo-800 ring-indigo-200',
        info_requested: 'bg-orange-50 text-orange-800 ring-orange-200',
        approved: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        completed: 'bg-emerald-100 text-emerald-900 ring-emerald-300',
        rejected: 'bg-red-50 text-red-800 ring-red-200',
        cancelled: 'bg-neutral-100 text-neutral-600 ring-neutral-200',
        // payments & documents
        paid: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        pending: 'bg-amber-50 text-amber-800 ring-amber-200',
        failed: 'bg-red-50 text-red-800 ring-red-200',
        refunded: 'bg-neutral-100 text-neutral-700 ring-neutral-200',
        accepted: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        superseded: 'bg-neutral-100 text-neutral-500 ring-neutral-200',
    };

    return map[status] ?? 'bg-neutral-100 text-neutral-700 ring-neutral-200';
}

/** Mirrors ServiceFormValidator::isVisible on the server. */
export function isVisible(
    showIf: ShowIf | undefined,
    values: Record<string, unknown>,
): boolean {
    if (!showIf) {
        return true;
    }

    const expected = Array.isArray(showIf.value)
        ? showIf.value
        : [showIf.value];
    const actual = values[showIf.field];
    const actualList = Array.isArray(actual) ? actual : [actual];

    return actualList.some((v) => expected.includes(String(v)));
}
