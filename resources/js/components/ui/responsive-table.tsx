import { ReactNode } from 'react';
import { cn } from '@/lib/utils';

interface ResponsiveTableProps {
    children: ReactNode;
    className?: string;
}

/**
 * Responsive Table Wrapper
 * 
 * Wraps tables with horizontal scroll on mobile devices
 * while maintaining full width on desktop.
 * 
 * Usage:
 * <ResponsiveTable>
 *   <Table>...</Table>
 * </ResponsiveTable>
 */
export function ResponsiveTable({ children, className }: ResponsiveTableProps) {
    return (
        <div className={cn("w-full overflow-x-auto", className)}>
            <div className="min-w-full inline-block align-middle">
                <div className="overflow-hidden">
                    {children}
                </div>
            </div>
        </div>
    );
}

interface ResponsiveTableContainerProps {
    children: ReactNode;
    maxHeight?: string;
    className?: string;
}

/**
 * Responsive Table Container with Vertical Scroll
 * 
 * Combines horizontal scroll (mobile) with vertical scroll (all devices)
 * 
 * Usage:
 * <ResponsiveTableContainer maxHeight="500px">
 *   <Table>...</Table>
 * </ResponsiveTableContainer>
 */
export function ResponsiveTableContainer({ 
    children, 
    maxHeight = "500px",
    className 
}: ResponsiveTableContainerProps) {
    return (
        <div 
            className={cn("w-full overflow-auto", className)}
            style={{ maxHeight }}
        >
            <div className="min-w-full inline-block align-middle">
                {children}
            </div>
        </div>
    );
}
