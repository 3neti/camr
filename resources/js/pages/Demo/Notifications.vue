<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useNotification } from '@/composables/useNotification';
import { Bell, CheckCircle, XCircle, Info, AlertTriangle, Loader2, RefreshCw } from 'lucide-vue-next';

const notification = useNotification();

// Basic notifications
function showSuccess() {
    notification.success('Operation completed successfully');
}

function showError() {
    notification.error('An error occurred');
}

function showInfo() {
    notification.info('Here is some information');
}

function showWarning() {
    notification.warning('Please be careful');
}

// Enhanced notifications with descriptions
function showSuccessWithDescription() {
    notification.success('Account created', {
        description: 'Your account has been created successfully. You can now log in.',
        duration: 5000,
    });
}

function showErrorWithDescription() {
    notification.error('Failed to save changes', {
        description: 'There was a problem saving your changes. Please try again.',
        duration: 5000,
    });
}

// Notifications with actions
function showWithAction() {
    notification.info('New update available', {
        description: 'A new version of the application is available.',
        duration: 10000,
        action: {
            label: 'Update',
            onClick: () => {
                notification.success('Update started');
            },
        },
    });
}

function showWithCancelAction() {
    notification.warning('Unsaved changes', {
        description: 'You have unsaved changes. Do you want to discard them?',
        duration: 10000,
        action: {
            label: 'Save',
            onClick: () => {
                notification.success('Changes saved');
            },
        },
        cancel: {
            label: 'Discard',
            onClick: () => {
                notification.info('Changes discarded');
            },
        },
    });
}

// Loading notifications
function showLoading() {
    const dismiss = notification.loading('Processing...', {
        description: 'Please wait while we process your request',
    });

    // Simulate async operation
    setTimeout(() => {
        dismiss();
        notification.success('Processing complete');
    }, 3000);
}

// Promise-based notifications
function showPromiseSuccess() {
    const fakeApiCall = new Promise((resolve) => {
        setTimeout(() => resolve({ id: 123, name: 'Test Site' }), 2000);
    });

    notification.promise(fakeApiCall, {
        loading: 'Creating site...',
        success: (data: any) => `Site "${data.name}" created successfully`,
        error: 'Failed to create site',
    });
}

function showPromiseError() {
    const fakeApiCall = new Promise((_, reject) => {
        setTimeout(() => reject(new Error('Network error')), 2000);
    });

    notification.promise(fakeApiCall, {
        loading: 'Deleting site...',
        success: 'Site deleted successfully',
        error: (err: Error) => `Failed to delete site: ${err.message}`,
    });
}

// Multiple notifications
function showMultiple() {
    notification.info('First notification');
    setTimeout(() => notification.success('Second notification'), 500);
    setTimeout(() => notification.warning('Third notification'), 1000);
    setTimeout(() => notification.error('Fourth notification'), 1500);
}

// Dismiss actions
function showDismissable() {
    notification.info('This notification can be dismissed manually', {
        duration: Infinity, // Never auto-dismiss
    });
}

function dismissAll() {
    notification.dismissAll();
}
</script>

<template>
    <Head title="Notification Demo" />

    <AppLayout>
        <div class="container mx-auto py-6 space-y-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Notification System Demo</h1>
                <p class="text-muted-foreground">
                    Demonstration of the enhanced notification/toast system features
                </p>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Basic Notifications -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Bell class="h-5 w-5" />
                            Basic Notifications
                        </CardTitle>
                        <CardDescription>
                            Simple notifications with different types
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <Button @click="showSuccess" variant="outline" class="w-full justify-start">
                            <CheckCircle class="mr-2 h-4 w-4 text-green-600" />
                            Success Notification
                        </Button>
                        <Button @click="showError" variant="outline" class="w-full justify-start">
                            <XCircle class="mr-2 h-4 w-4 text-red-600" />
                            Error Notification
                        </Button>
                        <Button @click="showInfo" variant="outline" class="w-full justify-start">
                            <Info class="mr-2 h-4 w-4 text-blue-600" />
                            Info Notification
                        </Button>
                        <Button @click="showWarning" variant="outline" class="w-full justify-start">
                            <AlertTriangle class="mr-2 h-4 w-4 text-yellow-600" />
                            Warning Notification
                        </Button>
                    </CardContent>
                </Card>

                <!-- Enhanced Notifications -->
                <Card>
                    <CardHeader>
                        <CardTitle>Enhanced Notifications</CardTitle>
                        <CardDescription>
                            Notifications with descriptions and longer duration
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <Button @click="showSuccessWithDescription" variant="outline" class="w-full justify-start">
                            <CheckCircle class="mr-2 h-4 w-4 text-green-600" />
                            Success with Description
                        </Button>
                        <Button @click="showErrorWithDescription" variant="outline" class="w-full justify-start">
                            <XCircle class="mr-2 h-4 w-4 text-red-600" />
                            Error with Description
                        </Button>
                    </CardContent>
                </Card>

                <!-- Action Notifications -->
                <Card>
                    <CardHeader>
                        <CardTitle>Interactive Notifications</CardTitle>
                        <CardDescription>
                            Notifications with action buttons
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <Button @click="showWithAction" variant="outline" class="w-full justify-start">
                            <Bell class="mr-2 h-4 w-4" />
                            Notification with Action
                        </Button>
                        <Button @click="showWithCancelAction" variant="outline" class="w-full justify-start">
                            <AlertTriangle class="mr-2 h-4 w-4" />
                            Notification with Action & Cancel
                        </Button>
                    </CardContent>
                </Card>

                <!-- Loading Notifications -->
                <Card>
                    <CardHeader>
                        <CardTitle>Loading Notifications</CardTitle>
                        <CardDescription>
                            Show loading state with automatic dismissal
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <Button @click="showLoading" variant="outline" class="w-full justify-start">
                            <Loader2 class="mr-2 h-4 w-4 animate-spin" />
                            Show Loading (3s)
                        </Button>
                    </CardContent>
                </Card>

                <!-- Promise-based Notifications -->
                <Card>
                    <CardHeader>
                        <CardTitle>Promise Notifications</CardTitle>
                        <CardDescription>
                            Automatically handle async operations
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <Button @click="showPromiseSuccess" variant="outline" class="w-full justify-start">
                            <CheckCircle class="mr-2 h-4 w-4 text-green-600" />
                            Promise Success (2s)
                        </Button>
                        <Button @click="showPromiseError" variant="outline" class="w-full justify-start">
                            <XCircle class="mr-2 h-4 w-4 text-red-600" />
                            Promise Error (2s)
                        </Button>
                    </CardContent>
                </Card>

                <!-- Multiple & Control -->
                <Card>
                    <CardHeader>
                        <CardTitle>Multiple & Control</CardTitle>
                        <CardDescription>
                            Show multiple notifications and control them
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <Button @click="showMultiple" variant="outline" class="w-full justify-start">
                            <Bell class="mr-2 h-4 w-4" />
                            Show Multiple (4)
                        </Button>
                        <Button @click="showDismissable" variant="outline" class="w-full justify-start">
                            <Info class="mr-2 h-4 w-4" />
                            Show Persistent
                        </Button>
                        <Button @click="dismissAll" variant="destructive" class="w-full justify-start">
                            <RefreshCw class="mr-2 h-4 w-4" />
                            Dismiss All
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
