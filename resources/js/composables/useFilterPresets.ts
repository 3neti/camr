import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

export interface FilterPreset {
    id: string;
    name: string;
    filters: Record<string, any>;
    createdAt: string;
}

interface UseFilterPresetsOptions {
    storageKey: string;
    routeUrl: string;
}

/**
 * Composable for managing saved filter presets
 * Allows users to save, load, and delete common filter combinations
 */
export function useFilterPresets(options: UseFilterPresetsOptions) {
    const { storageKey, routeUrl } = options;

    // Load presets from localStorage
    const loadPresetsFromStorage = (): FilterPreset[] => {
        try {
            const stored = localStorage.getItem(storageKey);
            return stored ? JSON.parse(stored) : [];
        } catch (error) {
            console.error('Failed to load filter presets:', error);
            return [];
        }
    };

    // Save presets to localStorage
    const savePresetsToStorage = (presets: FilterPreset[]) => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(presets));
        } catch (error) {
            console.error('Failed to save filter presets:', error);
        }
    };

    const presets = ref<FilterPreset[]>(loadPresetsFromStorage());

    const hasPresets = computed(() => presets.value.length > 0);

    /**
     * Save current filters as a new preset
     */
    const savePreset = (name: string, filters: Record<string, any>): FilterPreset => {
        // Remove empty/null/undefined values
        const cleanFilters = Object.entries(filters).reduce((acc, [key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                acc[key] = value;
            }
            return acc;
        }, {} as Record<string, any>);

        const preset: FilterPreset = {
            id: `preset-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
            name,
            filters: cleanFilters,
            createdAt: new Date().toISOString(),
        };

        presets.value.push(preset);
        savePresetsToStorage(presets.value);

        return preset;
    };

    /**
     * Apply a saved preset
     */
    const applyPreset = (preset: FilterPreset) => {
        router.get(routeUrl, preset.filters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    /**
     * Delete a saved preset
     */
    const deletePreset = (presetId: string) => {
        presets.value = presets.value.filter((p) => p.id !== presetId);
        savePresetsToStorage(presets.value);
    };

    /**
     * Update an existing preset
     */
    const updatePreset = (presetId: string, name: string, filters?: Record<string, any>) => {
        const preset = presets.value.find((p) => p.id === presetId);
        if (preset) {
            preset.name = name;
            if (filters) {
                preset.filters = Object.entries(filters).reduce((acc, [key, value]) => {
                    if (value !== null && value !== undefined && value !== '') {
                        acc[key] = value;
                    }
                    return acc;
                }, {} as Record<string, any>);
            }
            savePresetsToStorage(presets.value);
        }
    };

    /**
     * Check if current filters match a preset
     */
    const matchesPreset = (preset: FilterPreset, currentFilters: Record<string, any>): boolean => {
        const presetKeys = Object.keys(preset.filters).sort();
        const currentKeys = Object.keys(currentFilters)
            .filter((key) => currentFilters[key] !== null && currentFilters[key] !== undefined && currentFilters[key] !== '')
            .sort();

        if (presetKeys.length !== currentKeys.length) return false;
        if (presetKeys.join(',') !== currentKeys.join(',')) return false;

        return presetKeys.every((key) => preset.filters[key] === currentFilters[key]);
    };

    /**
     * Find the active preset (if any)
     */
    const findActivePreset = (currentFilters: Record<string, any>): FilterPreset | null => {
        return presets.value.find((preset) => matchesPreset(preset, currentFilters)) || null;
    };

    /**
     * Clear all presets
     */
    const clearAllPresets = () => {
        presets.value = [];
        savePresetsToStorage(presets.value);
    };

    /**
     * Export presets as JSON
     */
    const exportPresets = (): string => {
        return JSON.stringify(presets.value, null, 2);
    };

    /**
     * Import presets from JSON
     */
    const importPresets = (json: string): boolean => {
        try {
            const imported = JSON.parse(json) as FilterPreset[];
            // Validate structure
            if (!Array.isArray(imported)) return false;
            if (!imported.every((p) => p.id && p.name && p.filters && p.createdAt)) return false;

            presets.value = imported;
            savePresetsToStorage(presets.value);
            return true;
        } catch (error) {
            console.error('Failed to import presets:', error);
            return false;
        }
    };

    return {
        presets,
        hasPresets,
        savePreset,
        applyPreset,
        deletePreset,
        updatePreset,
        matchesPreset,
        findActivePreset,
        clearAllPresets,
        exportPresets,
        importPresets,
    };
}
