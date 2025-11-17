import { ref, computed, watch } from 'vue';

export interface TableColumn {
    key: string;
    label: string;
    visible: boolean;
    locked?: boolean; // Locked columns cannot be hidden
}

interface UseColumnPreferencesOptions {
    storageKey: string;
    defaultColumns: Omit<TableColumn, 'visible'>[];
}

/**
 * Composable for managing table column visibility preferences
 * Allows users to show/hide columns and saves preferences to localStorage
 */
export function useColumnPreferences(options: UseColumnPreferencesOptions) {
    const { storageKey, defaultColumns } = options;

    // Load preferences from localStorage
    const loadPreferencesFromStorage = (): Record<string, boolean> => {
        try {
            const stored = localStorage.getItem(storageKey);
            return stored ? JSON.parse(stored) : {};
        } catch (error) {
            console.error('Failed to load column preferences:', error);
            return {};
        }
    };

    // Save preferences to localStorage
    const savePreferencesToStorage = (preferences: Record<string, boolean>) => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(preferences));
        } catch (error) {
            console.error('Failed to save column preferences:', error);
        }
    };

    const storedPreferences = loadPreferencesFromStorage();

    // Initialize columns with stored preferences or defaults
    const columns = ref<TableColumn[]>(
        defaultColumns.map((col) => ({
            ...col,
            visible: storedPreferences[col.key] ?? true, // Default to visible if not stored
        }))
    );

    // Watch for changes and save to localStorage
    watch(
        () => columns.value.map((col) => ({ key: col.key, visible: col.visible })),
        (newColumns) => {
            const preferences = newColumns.reduce((acc, col) => {
                acc[col.key] = col.visible;
                return acc;
            }, {} as Record<string, boolean>);
            savePreferencesToStorage(preferences);
        },
        { deep: true }
    );

    const visibleColumns = computed(() => columns.value.filter((col) => col.visible));
    const hiddenColumns = computed(() => columns.value.filter((col) => !col.visible));
    const lockedColumns = computed(() => columns.value.filter((col) => col.locked));
    const toggleableColumns = computed(() => columns.value.filter((col) => !col.locked));

    const hasHiddenColumns = computed(() => hiddenColumns.value.length > 0);
    const allColumnsVisible = computed(() => visibleColumns.value.length === columns.value.length);

    /**
     * Check if a column is visible
     */
    const isColumnVisible = (key: string): boolean => {
        const column = columns.value.find((col) => col.key === key);
        return column?.visible ?? false;
    };

    /**
     * Toggle a column's visibility
     */
    const toggleColumn = (key: string) => {
        const column = columns.value.find((col) => col.key === key);
        if (column && !column.locked) {
            column.visible = !column.visible;
        }
    };

    /**
     * Show a column
     */
    const showColumn = (key: string) => {
        const column = columns.value.find((col) => col.key === key);
        if (column && !column.locked) {
            column.visible = true;
        }
    };

    /**
     * Hide a column
     */
    const hideColumn = (key: string) => {
        const column = columns.value.find((col) => col.key === key);
        if (column && !column.locked) {
            column.visible = false;
        }
    };

    /**
     * Show all columns
     */
    const showAllColumns = () => {
        columns.value.forEach((col) => {
            if (!col.locked) {
                col.visible = true;
            }
        });
    };

    /**
     * Hide all columns (except locked ones)
     */
    const hideAllColumns = () => {
        columns.value.forEach((col) => {
            if (!col.locked) {
                col.visible = false;
            }
        });
    };

    /**
     * Reset to default visibility (all visible)
     */
    const resetToDefaults = () => {
        columns.value.forEach((col) => {
            col.visible = true;
        });
    };

    /**
     * Set multiple columns' visibility at once
     */
    const setColumnsVisibility = (visibility: Record<string, boolean>) => {
        Object.entries(visibility).forEach(([key, visible]) => {
            const column = columns.value.find((col) => col.key === key);
            if (column && !column.locked) {
                column.visible = visible;
            }
        });
    };

    /**
     * Get current preferences as a plain object
     */
    const getPreferences = (): Record<string, boolean> => {
        return columns.value.reduce((acc, col) => {
            acc[col.key] = col.visible;
            return acc;
        }, {} as Record<string, boolean>);
    };

    /**
     * Export preferences as JSON
     */
    const exportPreferences = (): string => {
        return JSON.stringify(getPreferences(), null, 2);
    };

    /**
     * Import preferences from JSON
     */
    const importPreferences = (json: string): boolean => {
        try {
            const imported = JSON.parse(json) as Record<string, boolean>;
            setColumnsVisibility(imported);
            return true;
        } catch (error) {
            console.error('Failed to import preferences:', error);
            return false;
        }
    };

    return {
        columns,
        visibleColumns,
        hiddenColumns,
        lockedColumns,
        toggleableColumns,
        hasHiddenColumns,
        allColumnsVisible,
        isColumnVisible,
        toggleColumn,
        showColumn,
        hideColumn,
        showAllColumns,
        hideAllColumns,
        resetToDefaults,
        setColumnsVisibility,
        getPreferences,
        exportPreferences,
        importPreferences,
    };
}
