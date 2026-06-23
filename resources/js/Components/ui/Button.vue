<script setup>
const props = defineProps({
    variant: {
        type: String,
        default: 'default',
        validator: (v) => ['default', 'outline', 'ghost', 'destructive', 'secondary'].includes(v),
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
    default:     'bg-[hsl(240_5.9%_10%)] text-[hsl(0_0%_98%)] hover:bg-[hsl(240_5.9%_16%)] shadow-sm',
    outline:     'border border-[hsl(240_5.9%_90%)] bg-white text-[hsl(240_10%_3.9%)] hover:bg-[hsl(240_4.8%_95.9%)] shadow-sm',
    ghost:       'text-[hsl(240_10%_3.9%)] hover:bg-[hsl(240_4.8%_95.9%)]',
    secondary:   'bg-[hsl(240_4.8%_95.9%)] text-[hsl(240_5.9%_10%)] hover:bg-[hsl(240_5%_89%)] shadow-sm',
    destructive: 'bg-[hsl(0_84.2%_60.2%)] text-white hover:bg-[hsl(0_84.2%_55%)] shadow-sm',
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
            'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
            variantStyles[variant],
            sizeStyles[size],
        ]"
    >
        <slot />
    </button>
</template>
