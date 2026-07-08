import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useRegistrationWindow() {
    const page = usePage();

    const registrationWindow = computed(() => page.props.registrationWindow ?? null);

    const isRegistrationOpen = computed(() => registrationWindow.value?.is_open ?? true);

    return {
        registrationWindow,
        isRegistrationOpen,
    };
}
