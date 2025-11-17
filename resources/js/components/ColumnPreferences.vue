<script setup lang="ts">
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
import { Checkbox } from '@/components/ui/checkbox';
import { Columns3, Eye, EyeOff, RotateCcw, Lock } from 'lucide-vue-next';
import { useColumnPreferences, type TableColumn } from '@/composables/useColumnPreferences';
import { useNotification } from '@/composables/useNotification';

interface Props {
    storageKey: string;
    defaultColumns: Omit<TableColumn, 'visible'>[];
    disabled?: boolean;
}

const props = defineProps<Props>();

const columnPrefs = useColumnPreferences({
    storageKey: props.storageKey,
    defaultColumns: props.defaultColumns,
});

const notification = useNotification();

function handleToggle(key: string) {
    columnPrefs.toggleColumn(key);
}

function showAll() {
    columnPrefs.showAllColumns();
    notification.success('All columns shown');
}

function resetDefaults() {
    columnPrefs.resetToDefaults();
    notification.success('Column preferences reset to defaults');
}

// Expose column preferences to parent
defineExpose({
    isColumnVisible: columnPrefs.isColumnVisible,
    visibleColumns: columnPrefs.visibleColumns,
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="outline" size="sm" :disabled="disabled">
                <Columns3 class="h-4 w-4 mr-2" />
                <span>Columns</span>
                <span v-if="columnPrefs.hasHiddenColumns.value" class="ml-1 text-xs text-muted-foreground">
                    ({{ columnPrefs.visibleColumns.value.length }}/{{ columnPrefs.columns.value.length }})
                </span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-56">
            <DropdownMenuLabel>Visible Columns</DropdownMenuLabel>
            <DropdownMenuSeparator />

            <DropdownMenuGroup>
                <DropdownMenuItem
                    v-for="column in columnPrefs.columns.value"
                    :key="column.key"
                    class="cursor-pointer"
                    :disabled="column.locked"
                    @click="handleToggle(column.key)"
                >
                    <div class="flex items-center gap-2 w-full">
                        <Checkbox
                            :checked="column.visible"
                            :disabled="column.locked"
                            @click.stop="handleToggle(column.key)"
                        />
                        <span class="flex-1">{{ column.label }}</span>
                        <Lock v-if="column.locked" class="h-3 w-3 text-muted-foreground" />
                        <Eye v-else-if="column.visible" class="h-3 w-3 text-green-600" />
                        <EyeOff v-else class="h-3 w-3 text-muted-foreground" />
                    </div>
                </DropdownMenuItem>
            </DropdownMenuGroup>

            <DropdownMenuSeparator />

            <DropdownMenuItem
                v-if="!columnPrefs.allColumnsVisible.value"
                @click="showAll"
                class="cursor-pointer"
            >
                <Eye class="h-4 w-4 mr-2" />
                <span>Show All</span>
            </DropdownMenuItem>

            <DropdownMenuItem @click="resetDefaults" class="cursor-pointer">
                <RotateCcw class="h-4 w-4 mr-2" />
                <span>Reset to Defaults</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
