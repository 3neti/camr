# Notification System Guide

This guide explains how to use the enhanced notification/toast system in the CAMR application.

## Overview

The application provides two notification composables:

1. **`useFlash()`** - Handles server-side flash messages from Laravel (automatically shown on page navigation)
2. **`useNotification()`** - Enhanced client-side notifications with advanced features

Both use `vue-sonner` under the hood for a consistent user experience.

## Basic Usage

### Simple Notifications

```typescript
import { useNotification } from '@/composables/useNotification';

const notification = useNotification();

// Show different types of notifications
notification.success('Operation completed successfully');
notification.error('An error occurred');
notification.info('Here is some information');
notification.warning('Please be careful');
```

### Notifications with Descriptions

Add more context to your notifications:

```typescript
notification.success('Account created', {
    description: 'Your account has been created successfully. You can now log in.',
    duration: 5000, // Optional: custom duration in milliseconds
});
```

## Advanced Features

### Interactive Notifications

Add action buttons to notifications:

```typescript
notification.info('New update available', {
    description: 'A new version of the application is available.',
    duration: 10000,
    action: {
        label: 'Update',
        onClick: () => {
            // Handle update action
            startUpdate();
        },
    },
});
```

With both action and cancel buttons:

```typescript
notification.warning('Unsaved changes', {
    description: 'You have unsaved changes. Do you want to discard them?',
    action: {
        label: 'Save',
        onClick: () => saveChanges(),
    },
    cancel: {
        label: 'Discard',
        onClick: () => discardChanges(),
    },
});
```

### Loading Notifications

Show loading states with manual dismissal:

```typescript
const dismiss = notification.loading('Processing...', {
    description: 'Please wait while we process your request',
});

// Later, when the operation completes:
dismiss();
notification.success('Processing complete');
```

### Promise-Based Notifications

Automatically handle async operations:

```typescript
const apiCall = fetch('/api/sites', { method: 'POST', body: data });

notification.promise(apiCall, {
    loading: 'Creating site...',
    success: (data) => `Site "${data.name}" created successfully`,
    error: (err) => `Failed to create site: ${err.message}`,
});
```

The notification will automatically:
- Show a loading state while the promise is pending
- Show a success message when the promise resolves
- Show an error message if the promise rejects

### Dismissing Notifications

```typescript
// Dismiss all notifications
notification.dismissAll();

// Show a persistent notification (must be manually dismissed)
notification.info('This notification will not auto-dismiss', {
    duration: Infinity,
});
```

## Real-World Examples

### Form Submission

```typescript
import { useNotification } from '@/composables/useNotification';
import { router } from '@inertiajs/vue3';

const notification = useNotification();

function submitForm(data) {
    const dismiss = notification.loading('Saving changes...');
    
    router.post('/sites', data, {
        onSuccess: () => {
            dismiss();
            notification.success('Site created successfully');
        },
        onError: () => {
            dismiss();
            notification.error('Failed to create site', {
                description: 'Please check your input and try again.',
            });
        },
    });
}
```

### Inertia Form with Promise

```typescript
import { useNotification } from '@/composables/useNotification';
import { useForm } from '@inertiajs/vue3';

const notification = useNotification();
const form = useForm({ name: '', email: '' });

async function submit() {
    await notification.promise(
        new Promise((resolve, reject) => {
            form.post('/users', {
                onSuccess: () => resolve(null),
                onError: () => reject(new Error('Validation failed')),
            });
        }),
        {
            loading: 'Creating user...',
            success: 'User created successfully',
            error: 'Failed to create user',
        }
    );
}
```

### Bulk Operations

```typescript
notification.info(`Deleting ${count} items...`, {
    description: 'This may take a few moments.',
    action: {
        label: 'Cancel',
        onClick: () => {
            cancelBulkOperation();
            notification.warning('Operation cancelled');
        },
    },
});
```

## API Reference

### `useNotification()`

Returns an object with the following methods:

#### `success(message, options?)`
Show a success notification.

#### `error(message, options?)`
Show an error notification.

#### `info(message, options?)`
Show an info notification.

#### `warning(message, options?)`
Show a warning notification.

#### `loading(message, options?)`
Show a loading notification. Returns a dismiss function.

#### `promise(promiseFn, options)`
Show a notification that tracks a promise state.

#### `custom(component, options?)`
Show a custom notification with a Vue component.

#### `dismiss(toastId?)`
Dismiss a specific notification by ID.

#### `dismissAll()`
Dismiss all active notifications.

### Options

```typescript
interface NotificationOptions {
    description?: string;        // Additional text below the main message
    duration?: number;           // Duration in milliseconds (default: 4000)
    action?: {
        label: string;           // Text for the action button
        onClick: () => void;     // Handler when action is clicked
    };
    cancel?: {
        label: string;           // Text for the cancel button
        onClick?: () => void;    // Handler when cancel is clicked
    };
}
```

## Server-Side Flash Messages

Flash messages from Laravel controllers are automatically displayed as toasts:

```php
// In a Laravel controller
return redirect()
    ->route('sites.index')
    ->with('success', 'Site created successfully');

// Also supports: 'error', 'info', 'warning'
```

These are handled automatically by `useFlash()` which is initialized in `AppSidebarLayout.vue`.

## Demo Page

Visit `/demo/notifications` (when logged in) to see all notification features in action.

## Best Practices

1. **Use appropriate types**: Choose the right notification type (success, error, info, warning) for the context
2. **Keep messages concise**: Main messages should be short; use descriptions for additional context
3. **Provide actions when needed**: Add action buttons for operations that can be undone or need user input
4. **Handle loading states**: Use loading notifications or promise-based notifications for async operations
5. **Don't overuse**: Only show notifications for important events that require user attention
6. **Set appropriate durations**: Longer durations (5-10s) for messages with actions, shorter (2-4s) for simple confirmations

## Troubleshooting

**Notifications not showing?**
- Ensure `<Toaster />` is included in your layout (it's in `AppSidebarLayout.vue`)
- Check that you're calling the notification methods correctly

**Notifications disappearing too quickly?**
- Increase the `duration` option (in milliseconds)

**Promise notifications not updating?**
- Ensure your promise is properly resolving or rejecting
- Check the browser console for errors
