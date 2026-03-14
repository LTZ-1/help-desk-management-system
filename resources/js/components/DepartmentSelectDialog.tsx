// DepartmentSelectDialog.tsx
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group"
import { useState, useEffect } from "react"

interface User {
    id: number
    name: string
    email: string
    department_id?: number
    is_admin: boolean
    is_resolver: boolean
    is_none: boolean
    branch?: string
}

interface Department {
    id: number
    name: string
    slug: string
    description?: string
    is_active?: boolean
}

export interface DepartmentSelectDialogProps {
    open: boolean
    onOpenChange: (open: boolean) => void
    user: User
    departments?: Department[]
    branches?: string[]
}

export default function DepartmentSelectDialog({ 
    open, 
    onOpenChange, 
    user, 
    departments = [], 
    branches = []
}: DepartmentSelectDialogProps) {
    const [selectedDepartment, setSelectedDepartment] = useState("")
    const [branch, setBranch] = useState("")
    const [selectedRole, setSelectedRole] = useState("")
    const [password, setPassword] = useState("")
    const [loading, setLoading] = useState(false)
    const [error, setError] = useState<string | null>(null)
      const [formErrors, setFormErrors] = useState<{[key: string]: string}>({})
    // Debug: log what we receive
    useEffect(() => {
        if (open) {
            console.log('DepartmentSelectDialog received:', {
                departments,
                branches,
                departmentsCount: departments.length
            })
        }
    }, [open, departments, branches])
         // Reset form when dialog opens/closes
    useEffect(() => {
        if (open) {
            setSelectedDepartment("")
            setBranch("")
            setSelectedRole("")
            setPassword("")
            setError(null)
            setFormErrors({})
        }
    }, [open])

    const validateForm = () => {
        const errors: {[key: string]: string} = {}

        // Department is ALWAYS mandatory for ALL 
        if (!selectedDepartment) {
            errors.department = 'Department selection is required'
        }

        // Branch is always required
        if (!branch.trim()) {
            errors.branch = 'Branch is required'
        }

        // Role is always required
        if (!selectedRole) {
            errors.role = 'Please select a role'
        }

        // Password is always required
        if (!password) {
            errors.password = 'Password confirmation is required'
        }

        setFormErrors(errors)
        return Object.keys(errors).length === 0
    }


const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    if (!validateForm()) {
            setLoading(false)
            return
        }
    try {
        // Get CSRF token from meta tag
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        
        const response = await fetch('/auth/department-register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken, // Add CSRF token to headers
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                department_id: selectedDepartment ? parseInt(selectedDepartment) : null,
                branch: branch,
                is_admin: selectedRole === 'admin',
                is_resolver: selectedRole === 'resolver',
                is_none: selectedRole === 'none',
                password: password
            }),
        })

        if (response.ok) {
            const data = await response.json()
            window.location.reload() // Reload to update the user state
        } else {
            const errorData = await response.json()
            alert(errorData.message || 'Registration failed. Please check your password and try again.')
        }
    } catch (error) {
        console.error('Error:', error)
        alert('An error occurred during registration. Please try again.')
    } finally {
        setLoading(false)
    }
}

    const handleOpenChange = (newOpen: boolean) => {
        if (!newOpen && loading) return
        onOpenChange(newOpen)
        
        if (!newOpen) {
            setSelectedDepartment("")
            setBranch("")
            setSelectedRole("")
            setPassword("")
        }
    }
    

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="sm:max-w-[500px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle className="text-2xl font-bold text-center">Complete Your Registration</DialogTitle>
                    <DialogDescription className="text-center">
                        Please select your department and role to access all features.
                    </DialogDescription>
                </DialogHeader>
                
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="space-y-3">
                        <Label htmlFor="department" className="text-sm font-medium">Department *</Label>
                        {departments.length > 0 ? (
                            <Select 
                                value={selectedDepartment} 
                                onValueChange={setSelectedDepartment}
                                required
                                disabled={loading}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="Select your department" />
                                </SelectTrigger>
                                <SelectContent>
                                    {departments.map((dept) => (
                                        <SelectItem key={dept.id} value={dept.id.toString()}>
                                            <div className="flex flex-col">
                                                <span className="font-medium">{dept.name}</span>
                                                {dept.description && (
                                                    <span className="text-xs text-muted-foreground">
                                                        {dept.description}
                                                    </span>
                                                )}
                                            </div>
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        ) : (
                            <div className="text-sm text-muted-foreground p-3 border rounded-md bg-muted/50">
                                No departments available. Please contact administrator.
                            </div>
                        )}
                    </div>

                    <div className="space-y-3">
                        <Label htmlFor="branch" className="text-sm font-medium">Branch/Location *</Label>
                        <Input
                            id="branch"
                            value={branch}
                            onChange={(e) => setBranch(e.target.value)}
                            placeholder="Enter your branch or location"
                            required
                            className="w-full"
                            disabled={loading}
                        />
                        {branches.length > 0 && (
                            <p className="text-xs text-muted-foreground">
                                Suggested branches: {branches.join(', ')}
                            </p>
                        )}
                    </div>

                    <div className="space-y-3">
                        <Label className="text-sm font-medium">Role *</Label>
                        <RadioGroup 
                            value={selectedRole} 
                            onValueChange={setSelectedRole}
                            className="space-y-3"
                            required
                            disabled={loading}
                        >
                            <div className="flex items-center space-x-3 p-3 border rounded-md hover:bg-accent/50 transition-colors">
                                <RadioGroupItem value="admin" id="admin" disabled={loading} />
                                <Label htmlFor="admin" className="flex-1 cursor-pointer">
                                    <div className="font-medium">Administrator</div>
                                    <div className="text-xs text-muted-foreground">
                                        Full system access across all departments
                                    </div>
                                </Label>
                            </div>
                            
                            <div className="flex items-center space-x-3 p-3 border rounded-md hover:bg-accent/50 transition-colors">
                                <RadioGroupItem value="resolver" id="resolver" disabled={loading} />
                                <Label htmlFor="resolver" className="flex-1 cursor-pointer">
                                    <div className="font-medium">Resolver</div>
                                    <div className="text-xs text-muted-foreground">
                                        Resolve tickets within assigned department
                                    </div>
                                </Label>
                            </div>
                            
                            <div className="flex items-center space-x-3 p-3 border rounded-md hover:bg-accent/50 transition-colors">
                                <RadioGroupItem value="none" id="none" disabled={loading} />
                                <Label htmlFor="none" className="flex-1 cursor-pointer">
                                    <div className="font-medium">Regular User</div>
                                    <div className="text-xs text-muted-foreground">
                                        Create and view personal tickets only
                                    </div>
                                </Label>
                            </div>
                        </RadioGroup>
                    </div>

                    <div className="space-y-3">
                        <Label htmlFor="password" className="text-sm font-medium">Password Confirmation *</Label>
                        <Input
                            id="password"
                            type="password"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            placeholder="Enter your current password"
                            required
                            className="w-full"
                            disabled={loading}
                        />
                        <p className="text-xs text-muted-foreground">
                            For security, please confirm your identity with your password
                        </p>
                    </div>

                    <div className="flex justify-end space-x-3 pt-4 border-t">
                        <Button 
                            type="button" 
                            variant="outline" 
                            onClick={() => handleOpenChange(false)}
                            disabled={loading}
                            className="min-w-20"
                        >
                            Cancel
                        </Button>
                        <Button 
                            type="submit" 
                            disabled={loading || !selectedDepartment || !branch || !selectedRole || !password}
                            className="min-w-32"
                        >
                            {loading ? (
                                <>
                                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                    Processing...
                                </>
                            ) : (
                                'Complete Registration'
                            )}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    )
}