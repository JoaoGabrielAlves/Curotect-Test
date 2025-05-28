import { computed, ref, watch, type Ref } from 'vue';

export function useAnimatedCounter(target: Ref<number>, duration: number = 800) {
    const current = ref(target.value);
    const isAnimating = ref(false);

    const animatedValue = computed(() => Math.floor(current.value));

    const animateToTarget = (newTarget: number) => {
        if (newTarget === current.value) return;

        const startValue = current.value;
        const difference = newTarget - startValue;
        const startTime = performance.now();

        isAnimating.value = true;

        const animate = (currentTime: number) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // Easing function for smooth animation
            const easeOutQuart = 1 - Math.pow(1 - progress, 4);

            current.value = startValue + difference * easeOutQuart;

            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                current.value = newTarget;
                isAnimating.value = false;
            }
        };

        requestAnimationFrame(animate);
    };

    // Watch for changes in the target value
    watch(
        target,
        (newValue, oldValue) => {
            if (newValue !== oldValue) {
                animateToTarget(newValue);
            }
        },
        { immediate: false },
    );

    // Initialize with target value
    current.value = target.value;

    return {
        animatedValue,
        isAnimating,
    };
}
