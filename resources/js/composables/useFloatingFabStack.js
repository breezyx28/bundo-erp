/** Tailwind bottom offsets for stacked FABs (calculator → draft → tablet launcher). */
export const FAB_STACK_CLASSES = ['bottom-6', 'bottom-20', 'bottom-[8.5rem]'];

export function fabStackClass(index = 0) {
    return FAB_STACK_CLASSES[index] ?? FAB_STACK_CLASSES[FAB_STACK_CLASSES.length - 1];
}
