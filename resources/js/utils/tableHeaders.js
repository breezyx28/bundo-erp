export function textHeader(key, label, sortable = false) {
    return {
        key,
        label,
        ...(sortable ? { sortable: true } : {}),
    };
}

export function numericHeader(key, label, sortable = false) {
    return {
        key,
        label,
        align: 'end',
        ...(sortable ? { sortable: true } : {}),
    };
}
