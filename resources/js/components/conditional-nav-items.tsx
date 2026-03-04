// components/conditional-nav-items.tsx
import { usePage } from '@inertiajs/react';
import { Shield } from 'lucide-react';
import { NavItem } from '@/types';

export function useConditionalNavItems(): NavItem[] {
    const { props } = usePage();
    const { auth, departments = [] } = props as any;
    
    const isITAdmin = (): boolean => {
        if (!auth.user?.is_admin || !auth.user?.department_id) {
            return false;
        }
        
        const itDepartment = departments.find((dept: any) => dept.slug === 'it');
        return itDepartment && auth.user.department_id === itDepartment.id;
    };

    return [
        {
            title: 'System Administration',
            href: '/system-admin',
            icon: Shield,
            condition: isITAdmin
        }
    ].filter(item => !item.condition || item.condition());
}