# UI Guidelines

- **Delete Confirmations:** Whenever implementing a delete action in an index/list page or anywhere in the UI, ALWAYS use the `DeleteModal` component (`@/components/delete-modal`) instead of the native `window.confirm` dialog. This ensures a consistent and beautiful user experience across the application.
