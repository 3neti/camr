import { toast } from 'vue-sonner';

export interface NotificationOptions {
    description?: string;
    duration?: number;
    action?: {
        label: string;
        onClick: () => void;
    };
    cancel?: {
        label: string;
        onClick?: () => void;
    };
}

export interface PromiseNotificationOptions<T> {
    loading: string;
    success: string | ((data: T) => string);
    error: string | ((error: Error) => string);
    duration?: number;
}

/**
 * Enhanced notification/toast composable
 * Provides advanced features on top of the basic useFlash composable
 */
export function useNotification() {
    /**
     * Show a success notification
     */
    const success = (message: string, options?: NotificationOptions) => {
        toast.success(message, {
            description: options?.description,
            duration: options?.duration,
            action: options?.action,
            cancel: options?.cancel,
        });
    };

    /**
     * Show an error notification
     */
    const error = (message: string, options?: NotificationOptions) => {
        toast.error(message, {
            description: options?.description,
            duration: options?.duration,
            action: options?.action,
            cancel: options?.cancel,
        });
    };

    /**
     * Show an info notification
     */
    const info = (message: string, options?: NotificationOptions) => {
        toast.info(message, {
            description: options?.description,
            duration: options?.duration,
            action: options?.action,
            cancel: options?.cancel,
        });
    };

    /**
     * Show a warning notification
     */
    const warning = (message: string, options?: NotificationOptions) => {
        toast.warning(message, {
            description: options?.description,
            duration: options?.duration,
            action: options?.action,
            cancel: options?.cancel,
        });
    };

    /**
     * Show a loading notification
     * Returns a function to dismiss the notification
     */
    const loading = (message: string, options?: { description?: string }) => {
        const id = toast.loading(message, {
            description: options?.description,
        });

        return () => toast.dismiss(id);
    };

    /**
     * Show a notification with a promise
     * Automatically updates the notification based on the promise state
     */
    const promise = <T>(
        promiseFn: Promise<T> | (() => Promise<T>),
        options: PromiseNotificationOptions<T>
    ): Promise<T> => {
        const thePromise = typeof promiseFn === 'function' ? promiseFn() : promiseFn;

        toast.promise(thePromise, {
            loading: options.loading,
            success: (data) => {
                if (typeof options.success === 'function') {
                    return options.success(data);
                }
                return options.success;
            },
            error: (err) => {
                if (typeof options.error === 'function') {
                    return options.error(err as Error);
                }
                return options.error;
            },
            duration: options.duration,
        });

        return thePromise;
    };

    /**
     * Show a custom notification with JSX/component content
     */
    const custom = (component: any, options?: { duration?: number }) => {
        toast.custom(component, {
            duration: options?.duration,
        });
    };

    /**
     * Dismiss a specific notification or all notifications
     */
    const dismiss = (toastId?: string | number) => {
        toast.dismiss(toastId);
    };

    /**
     * Dismiss all notifications
     */
    const dismissAll = () => {
        toast.dismiss();
    };

    return {
        success,
        error,
        info,
        warning,
        loading,
        promise,
        custom,
        dismiss,
        dismissAll,
    };
}
