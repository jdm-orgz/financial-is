import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { type ButtonProps } from '@/components/ui/button';

interface ConfirmModalProps {
    isOpen: boolean;
    onOpenChange: (open: boolean) => void;
    onConfirm: () => void;
    title: string;
    description: string;
    confirmText?: string;
    confirmVariant?: ButtonProps['variant'];
}

export function ConfirmModal({
    isOpen,
    onOpenChange,
    onConfirm,
    title,
    description,
    confirmText = 'Confirm',
    confirmVariant = 'default',
}: ConfirmModalProps) {
    return (
        <Dialog open={isOpen} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>{description}</DialogDescription>

                <DialogFooter className="mt-4 gap-2 sm:gap-4">
                    <DialogClose asChild>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>

                    <Button variant={confirmVariant} onClick={onConfirm}>
                        {confirmText}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
