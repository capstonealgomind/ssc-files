<script setup>
const props = defineProps({
    variant: {
        type: String,
        default: 'default',
        validator: (v) => ['default', 'outline', 'ghost', 'destructive', 'secondary', 'navy', 'success'].includes(v),
    },
    size: {
        type: String,
        default: 'default',
        validator: (v) => ['default', 'sm', 'lg', 'icon'].includes(v),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    type: {
        type: String,
        default: 'button',
    },
});

const variantStyles = {
    default:     'bg-[var(--sscevs-blue)] text-white hover:bg-[var(--sscevs-blue-dark)] shadow-sm',
    navy:        'bg-[var(--sscevs-navy)] text-white hover:bg-[var(--sscevs-navy-dark)] shadow-sm',
    outline:     'border border-[var(--sscevs-blue)] bg-white text-[var(--sscevs-blue)] hover:bg-[var(--sscevs-blue-light)] shadow-sm',
    ghost:       'text-[var(--sscevs-black)] hover:bg-[var(--sscevs-blue-light)] hover:text-[var(--sscevs-blue)]',
    secondary:   'bg-[var(--sscevs-gold-light)] text-[var(--sscevs-gold-dark)] border border-[hsl(43_50%_80%)] hover:bg-[var(--sscevs-gold)] hover:text-[var(--sscevs-black)] shadow-sm',
    destructive: 'bg-[hsl(0_84.2%_60.2%)] text-white hover:bg-[hsl(0_84.2%_55%)] shadow-sm',
    success:     'bg-[hsl(142_71%_35%)] text-white hover:bg-[hsl(142_71%_30%)] shadow-sm',
};

const sizeStyles = {
    default: 'h-9 px-4 py-2 text-sm',
    sm:      'h-8 px-3 text-xs rounded-md',
    lg:      'h-10 px-6 text-base',
    icon:    'h-9 w-9',
};
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        :class="[
            'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md font-medium transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--sscevs-blue)] focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 disabled:cursor-not-allowed',
            variantStyles[variant],
            sizeStyles[size],
        ]"
    >
        <slot />
    </button>
</template>
