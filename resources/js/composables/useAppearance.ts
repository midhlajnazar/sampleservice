import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
};

// The portal always renders in the light theme, whatever the OS preference.
export function initializeTheme(): void {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.classList.remove('dark');
}

const appearance = ref<Appearance>('light');

export function useAppearance(): UseAppearanceReturn {
    return {
        appearance,
        resolvedAppearance: computed<ResolvedAppearance>(() => 'light'),
    };
}
