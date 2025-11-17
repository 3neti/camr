<script setup lang="ts">
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Save, Star, Trash2, Check } from 'lucide-vue-next';
import { useFilterPresets, type FilterPreset } from '@/composables/useFilterPresets';
import { useNotification } from '@/composables/useNotification';

interface Props {
    storageKey: string;
    routeUrl: string;
    currentFilters: Record<string, any>;
    disabled?: boolean;
}

const props = defineProps<Props>();

const filterPresets = useFilterPresets({
    storageKey: props.storageKey,
    routeUrl: props.routeUrl,
});

const notification = useNotification();

// Save preset dialog
const showSaveDialog = ref(false);
const presetName = ref('');

// Active preset detection
const activePreset = computed(() => filterPresets.findActivePreset(props.currentFilters));
const hasActiveFilters = computed(() => {
    return Object.values(props.currentFilters).some(
        (value) => value !== null && value !== undefined && value !== ''
    );
});

function openSaveDialog() {
    if (!hasActiveFilters.value) {
        notification.warning('No active filters', {
            description: 'Please apply some filters before saving a preset.',
        });
        return;
    }

    if (activePreset.value) {
        notification.info('Preset already exists', {
            description: `These filters match the "${activePreset.value.name}" preset.`,
        });
        return;
    }

    presetName.value = '';
    showSaveDialog.value = true;
}

function saveNewPreset() {
    if (!presetName.value.trim()) {
        notification.error('Name required', {
            description: 'Please enter a name for the preset.',
        });
        return;
    }

    try {
        const preset = filterPresets.savePreset(presetName.value.trim(), props.currentFilters);
        notification.success('Preset saved', {
            description: `"${preset.name}" has been saved successfully.`,
        });
        showSaveDialog.value = false;
        presetName.value = '';
    } catch (error) {
        notification.error('Failed to save preset', {
            description: 'Please try again.',
        });
    }
}

function applyPreset(preset: FilterPreset) {
    filterPresets.applyPreset(preset);
    notification.success('Preset applied', {
        description: `Filters from "${preset.name}" have been applied.`,
    });
}

function deletePreset(preset: FilterPreset) {
    if (confirm(`Are you sure you want to delete the preset "${preset.name}"?`)) {
        filterPresets.deletePreset(preset.id);
        notification.success('Preset deleted', {
            description: `"${preset.name}" has been deleted.`,
        });
    }
}
</script>

<template>
    <div class="flex items-center gap-2">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button variant="outline" size="sm" :disabled="disabled">
                    <Star class="h-4 w-4 mr-2" :class="{ 'fill-current text-yellow-500': activePreset }" />
                    <span v-if="activePreset">{{ activePreset.name }}</span>
                    <span v-else>Filter Presets</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-56">
                <DropdownMenuLabel>Saved Presets</DropdownMenuLabel>
                <DropdownMenuSeparator />

                <template v-if="filterPresets.hasPresets.value">
                    <DropdownMenuGroup>
                        <DropdownMenuItem
                            v-for="preset in filterPresets.presets.value"
                            :key="preset.id"
                            class="cursor-pointer"
                            @click="applyPreset(preset)"
                        >
                            <Check
                                v-if="activePreset?.id === preset.id"
                                class="h-4 w-4 mr-2 text-green-600"
                            />
                            <Star v-else class="h-4 w-4 mr-2 text-muted-foreground" />
                            <span class="flex-1">{{ preset.name }}</span>
                            <Trash2
                                class="h-3 w-3 text-muted-foreground hover:text-destructive"
                                @click.stop="deletePreset(preset)"
                            />
                        </DropdownMenuItem>
                    </DropdownMenuGroup>
                    <DropdownMenuSeparator />
                </template>

                <DropdownMenuItem v-else disabled class="text-muted-foreground text-sm">
                    No saved presets
                </DropdownMenuItem>

                <DropdownMenuItem @click="openSaveDialog" class="cursor-pointer">
                    <Save class="h-4 w-4 mr-2" />
                    <span>Save Current Filters</span>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>

    <!-- Save Preset Dialog -->
    <Dialog v-model:open="showSaveDialog">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Save Filter Preset</DialogTitle>
                <DialogDescription>
                    Give your filter combination a name so you can quickly apply it later.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-4">
                <div class="space-y-2">
                    <Label for="preset-name">Preset Name</Label>
                    <Input
                        id="preset-name"
                        v-model="presetName"
                        placeholder="e.g., Online Sites, Last 7 Days"
                        @keydown.enter="saveNewPreset"
                    />
                </div>

                <div class="space-y-2">
                    <Label class="text-sm text-muted-foreground">Current Filters:</Label>
                    <div class="rounded-md border p-3 bg-muted/50 text-sm">
                        <div v-if="hasActiveFilters" class="space-y-1">
                            <div
                                v-for="[key, value] in Object.entries(currentFilters)"
                                :key="key"
                                class="flex items-center gap-2"
                            >
                                <template v-if="value !== null && value !== undefined && value !== ''">
                                    <span class="font-medium">{{ key }}:</span>
                                    <span class="text-muted-foreground">{{ value }}</span>
                                </template>
                            </div>
                        </div>
                        <div v-else class="text-muted-foreground">No active filters</div>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="showSaveDialog = false">Cancel</Button>
                <Button @click="saveNewPreset" :disabled="!presetName.trim()">
                    <Save class="h-4 w-4 mr-2" />
                    Save Preset
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
