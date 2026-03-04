import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, useForm, router } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Create Ticket',
        href: '/Tickets/create',
    },
];

// Define the form data interface
interface FormData {
    requester_type: string;
    brunch: string;
    recipant: string;
    subject: string;
    description: string;
    category: string;
    priority: string;
    attachment: File | null;
}

interface Ticket {
    id: number;
    subject: string;
    description: string;
    category: string;
    priority: string;
    status: string;
    brunch: string;
    recipant: string | null;
    department: string;
    created_at: string;
    requester_type: string;
    attachment: string | null;
}

interface Department {
    id: number;
    name: string;
    slug: string;
    description?: string;
}

interface UserStats {
    total_tickets: number;
    open_tickets: number;
    assigned_tickets: number;
    resolved_tickets: number;
    overdue_tickets: number;
}
interface PageProps {
    auth: {
        user: any;
    };
    flash: any;
    tickets: Ticket[];
    departments: Department[]; // Departments from backend
    [key: string]: any;
}

export default function Index() {
     const { props } = usePage<PageProps>();
    
    const { auth, flash, tickets ,departments=[ ]} = props;
     const availableDepartments = departments || [];
    const [showTickets, setShowTickets] = useState(false);
    const [selectedTicket, setSelectedTicket] = useState<Ticket | null>(null);
    const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
    const [editFormData, setEditFormData] = useState<Partial<FormData>>({});
    const [userStats, setUserStats] = useState<UserStats>({
        total_tickets: 0,
        open_tickets: 0,
        assigned_tickets: 0,
        resolved_tickets: 0,
        overdue_tickets: 0
    });
   
    const { data, setData, post, processing, errors } = useForm<FormData>({
        requester_type: '',
        brunch: '',
        recipant: '',
        subject: '',
        description: '',
        category: '',
        priority: '',
        attachment: null,
    });

   
    // Calculate user-specific statistics
    useEffect(() => {
        const userTickets = tickets || [];
        
        const stats = {
            total_tickets: userTickets.length,
            open_tickets: userTickets.filter((ticket: Ticket) => ticket.status === 'open').length,
            assigned_tickets: userTickets.filter((ticket: Ticket) => ticket.status === 'assigned').length,
            resolved_tickets: userTickets.filter((ticket: Ticket) => ticket.status === 'resolved').length,
            overdue_tickets: userTickets.filter((ticket: Ticket) => {
                const createdDate = new Date(ticket.created_at);
                const daysOld = (Date.now() - createdDate.getTime()) / (1000 * 60 * 60 * 24);
                return daysOld > 7 && ticket.status !== 'resolved';
            }).length
        };
        
        setUserStats(stats);
    }, [tickets]);

    const handleChange = (
        e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>
    ) => {
        const { name, value, type } = e.target;
        if (type === 'file') {
            const input = e.target as HTMLInputElement;
            setData(name as keyof FormData, input.files?.[0] ?? null);
        } else {
            setData(name as keyof FormData, value);
        }
    };

    const handleCategoryChange = (value: string) => {
        setData('category', value);
    };

    // Add this function near the other handle functions
const handleRecipantChange = (value: string) => {
    setData('recipant', value);
};

    const handlePriorityChange = (value: string) => {
        setData('priority', value);
    };

    const handleRequesterTypeChange = (value: string) => {
        setData('requester_type', value);
    };

  
const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        post('/Tickets/store', {
            onError: (errors) => {
                console.log('Submission errors:', errors);
            },
            onSuccess: () => {
                console.log('Ticket submitted successfully');
            }
        });
    };
    const handleDelete = (ticketId: number) => {
        if (confirm('Are you sure you want to delete this ticket?')) {
            router.delete(`/tickets/${ticketId}`);
        }
    };

    const handleEdit = (ticket: Ticket) => {
        setSelectedTicket(ticket);
        setEditFormData({
            requester_type: ticket.requester_type,
            brunch: ticket.brunch,
            recipant: ticket.recipant || '',
            subject: ticket.subject,
            description: ticket.description,
            category: ticket.category,
            priority: ticket.priority,
        });
        setIsEditDialogOpen(true);
    };
    
    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (selectedTicket) {
            router.put(`/tickets/${selectedTicket.id}`, editFormData);
            setIsEditDialogOpen(false);
        }
    };

    // Job titles for microfinance institution
    const jobTitles = [
        "Loan Officer",
        "Branch Manager",
        "Credit Analyst",
        "Customer Service Representative",
        "Financial Advisor",
        "Operations Manager",
        "Risk Manager",
        "Compliance Officer",
        "IT Support Specialist",
        "Marketing Officer",
        "Accountant",
        "Internal Auditor",
        "HR Manager",
        "Training Coordinator",
        "Business Development Officer",
        "Microfinance Consultant",
        "Field Agent",
        "Collection Officer",
        "Product Manager",
        "Client Relationship Manager"
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create a Ticket" />
            <div className="w-full p-6 space-y-6">
            
                {/* Notification Alert */}
                {flash?.success && (
                    <Alert variant="default" className="mb-4">
                        <AlertTitle>Success</AlertTitle>
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                )}
                {flash?.error && (
                    <Alert variant="destructive" className="mb-4">
                        <AlertTitle>Error</AlertTitle>
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

                {/* User Statistics Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Tickets</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{userStats.total_tickets}</div>
                            <p className="text-xs text-muted-foreground">Your total tickets</p>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Open Tickets</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{userStats.open_tickets}</div>
                            <p className="text-xs text-muted-foreground">Waiting for resolution</p>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Assigned Tickets</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{userStats.assigned_tickets}</div>
                            <p className="text-xs text-muted-foreground">Currently being worked on</p>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Resolved Tickets</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{userStats.resolved_tickets}</div>
                            <p className="text-xs text-muted-foreground">Successfully completed</p>
                        </CardContent>
                    </Card>
                </div>

                {/* My Tickets Button */}
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold">Create Support Ticket</h1>
                    <Button 
                        onClick={() => setShowTickets(!showTickets)}
                        variant={showTickets ? "default" : "outline"}
                    >
                        {showTickets ? "Hide My Tickets" : "Show My Tickets"}
                    </Button>
                </div>

                {/* Display current user info */}
                <Alert className="bg-blue-50">
                    <AlertTitle>User Information</AlertTitle>
                    <AlertDescription>
                        <p><strong>Name:</strong> {auth.user.name}</p>
                        <p><strong>Email:</strong> {auth.user.email}</p>
                        <p><strong>ID:</strong> {auth.user.id}</p>
                        <p><strong>Department:</strong> {auth.user.department_id}</p>
                        <p className="text-sm text-gray-600 mt-1">
                            Your name , department and email  will be automatically associated with this ticket.
                        </p>
                    </AlertDescription>
                </Alert>

                {/* Tickets Listing Section */}
                {showTickets && (
                    <div className="space-y-4">
                        <h2 className="text-xl font-semibold">My Tickets ({tickets.length})</h2>
                        {tickets.length === 0 ? (
                            <Alert>
                                <AlertDescription>You haven't created any tickets yet.</AlertDescription>
                            </Alert>
                        ) : (
                            <div className="grid gap-4">
                                {tickets.map((ticket: Ticket) => (
                                    <Card key={ticket.id}>
                                        <CardHeader>
                                            <div className="flex justify-between items-start">
                                                <div>
                                                    <CardTitle className="text-lg">{ticket.subject}</CardTitle>
                                                    <CardDescription>
                                                        {ticket.description.substring(0, 100)}...
                                                    </CardDescription>
                                                </div>
                                                <Badge variant={ticket.status === 'open' ? 'default' : 'secondary'}>
                                                    {ticket.status}
                                                </Badge>
                                            </div>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="flex justify-between items-center">
                                                <div className="text-sm text-muted-foreground">
                                                    Category: {ticket.category} • Priority: {ticket.priority}
                                                    <br />
                                                    Branch: {ticket.brunch} • Department: {ticket.department}
                                                    {ticket.attachment && (
                                                        <>
                                                            <br />
                                                            Attachment: {ticket.attachment.split('/').pop()}
                                                        </>
                                                    )}
                                                </div>
                                                <div className="flex gap-2">
                                                    <Button 
                                                        variant="outline" 
                                                        size="sm"
                                                        onClick={() => handleEdit(ticket)}
                                                    >
                                                        Edit
                                                    </Button>
                                                    <Button 
                                                        variant="destructive" 
                                                        size="sm"
                                                        onClick={() => handleDelete(ticket.id)}
                                                    >
                                                        Delete
                                                    </Button>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        )}
                    </div>
                )}

                {/* Create Ticket Form */}
                {!showTickets && ( 
                    <form className="space-y-4" onSubmit={handleSubmit}>
                        {/* Requester Type Dropdown */}
                        <div>
                            <Label htmlFor="requester_type">Your Job Title *</Label>
                            <Select
                                value={data.requester_type}
                                onValueChange={handleRequesterTypeChange}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select your job title" />
                                </SelectTrigger>
                                <SelectContent>
                                    {jobTitles.map((title) => (
                                        <SelectItem key={title} value={title}>
                                            {title}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.requester_type && (
                                <Alert variant="destructive" className="mt-2">
                                    <AlertTitle>Error</AlertTitle>
                                    <AlertDescription>{errors.requester_type}</AlertDescription>
                                </Alert>
                            )}
                        </div>

                        {/* Brunch */}
                        <div>
                            <Label htmlFor="brunch">Branch *</Label>
                            <Input
                                id="brunch"
                                name="brunch"
                                value={data.brunch}
                                onChange={handleChange}
                                placeholder="Enter your branch location"
                            />
                            {errors.brunch && (
                                <Alert variant="destructive" className="mt-2">
                                    <AlertTitle>Error</AlertTitle>
                                    <AlertDescription>{errors.brunch}</AlertDescription>
                                </Alert>
                            )}
                        </div>

                       {/* Recipient Dropdown - Required */}
                        <div>
                            <Label htmlFor="recipant">Recipient Department *</Label>
                            {availableDepartments.length > 0 ? (
                                <Select
                                    value={data.recipant}
                                    onValueChange={handleRecipantChange}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select recipient department" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {availableDepartments.map((dept) => (
                                            <SelectItem key={dept.id} value={dept.name}>
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
                            {errors.recipant && (
                                <Alert variant="destructive" className="mt-2">
                                    <AlertTitle>Error</AlertTitle>
                                    <AlertDescription>{errors.recipant}</AlertDescription>
                                </Alert>
                            )}
                        </div>

                        {/* Subject */}
                        <div>
                            <Label htmlFor="subject">Subject *</Label>
                            <Input
                                id="subject"
                                name="subject"
                                value={data.subject}
                                onChange={handleChange}
                                placeholder="Enter ticket subject"
                            />
                            {errors.subject && (
                                <Alert variant="destructive" className="mt-2">
                                    <AlertTitle>Error</AlertTitle>
                                    <AlertDescription>{errors.subject}</AlertDescription>
                                </Alert>
                            )}
                        </div>

                        {/* Description */}
                        <div>
                            <Label htmlFor="description">Description *</Label>
                            <Textarea
                                id="description"
                                name="description"
                                value={data.description}
                                onChange={handleChange}
                                placeholder="Describe the issue in detail"
                                rows={5}
                            />
                            {errors.description && (
                                <Alert variant="destructive" className="mt-2">
                                    <AlertTitle>Error</AlertTitle>
                                    <AlertDescription>{errors.description}</AlertDescription>
                                </Alert>
                            )}
                        </div>

                        {/* Category Dropdown */}
                        <div>
                            <Label htmlFor="category">Category *</Label>
                            <Select
                                value={data.category}
                                onValueChange={handleCategoryChange}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select a category" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="loan">Loan Inquiry</SelectItem>
                                    <SelectItem value="technical">Technical Issue</SelectItem>
                                    <SelectItem value="billing">Billing</SelectItem>
                                    <SelectItem value="account">Account Management</SelectItem>
                                    <SelectItem value="complaint">Customer Complaint</SelectItem>
                                    <SelectItem value="suggestion">Improvement Suggestion</SelectItem>
                                    <SelectItem value="other">Other</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.category && (
                                <Alert variant="destructive" className="mt-2">
                                    <AlertTitle>Error</AlertTitle>
                                    <AlertDescription>{errors.category}</AlertDescription>
                                </Alert>
                            )}
                        </div>

                        {/* Priority Dropdown */}
                        <div>
                            <Label htmlFor="priority">Priority *</Label>
                            <Select
                                value={data.priority}
                                onValueChange={handlePriorityChange}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select priority level" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="low">Low</SelectItem>
                                    <SelectItem value="medium">Medium</SelectItem>
                                    <SelectItem value="high">High</SelectItem>
                                    <SelectItem value="critical">Critical</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.priority && (
                                <Alert variant="destructive" className="mt-2">
                                    <AlertTitle>Error</AlertTitle>
                                    <AlertDescription>{errors.priority}</AlertDescription>
                                </Alert>
                            )}
                        </div>

                        {/* Attachment */}
                        <div>
                            <Label htmlFor="attachment">Attachment (Optional)</Label>
                            <Input
                                id="attachment"
                                name="attachment"
                                type="file"
                                className="w-full border rounded px-3 py-2 focus:outline-none focus:ring"
                                onChange={handleChange}
                            />
                            {errors.attachment && (
                                <Alert variant="destructive" className="mt-2">
                                    <AlertTitle>Error</AlertTitle>
                                    <AlertDescription>{errors.attachment}</AlertDescription>
                                </Alert>
                            )}
                        </div>

                        {/* Submit Button */}
                        <div className="pt-4">
                            <Button type="submit" disabled={processing} className="w-full md:w-auto">
                                {processing ? "Submitting Ticket..." : "Submit Ticket"}
                            </Button>
                        </div>
                    </form>
                )}

                {/* Edit Ticket Dialog */}
                <Dialog open={isEditDialogOpen} onOpenChange={setIsEditDialogOpen}>
                    <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>Edit Ticket</DialogTitle>
                            <DialogDescription>
                                Make changes to your ticket here.
                            </DialogDescription>
                        </DialogHeader>
                        {selectedTicket && (
                            <form onSubmit={handleEditSubmit} className="space-y-4">
                                {/* Edit form fields (same as create form but for editing) */}
                                {/* ... existing edit form code ... */}
                                      {/* Subject */}
                <div>
                    <Label htmlFor="edit-subject">Subject *</Label>
                    <Input
                        id="edit-subject"
                        value={editFormData.subject || ''}
                        onChange={(e) => setEditFormData({...editFormData, subject: e.target.value})}
                        required
                    />
                </div>

                {/* Description */}
                <div>
                    <Label htmlFor="edit-description">Description *</Label>
                    <Textarea
                        id="edit-description"
                        value={editFormData.description || ''}
                        onChange={(e) => setEditFormData({...editFormData, description: e.target.value})}
                        rows={4}
                        required
                    />
                </div>

                {/* Requester Type */}
                <div>
                    <Label htmlFor="edit-requester-type">Job Title *</Label>
                    <Select
                        value={editFormData.requester_type || ''}
                        onValueChange={(value) => setEditFormData({...editFormData, requester_type: value})}
                    >
                        <SelectTrigger id="edit-requester-type">
                            <SelectValue placeholder="Select your job title" />
                        </SelectTrigger>
                        <SelectContent>
                            {jobTitles.map((title) => (
                                <SelectItem key={title} value={title}>
                                    {title}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Branch */}
                <div>
                    <Label htmlFor="edit-brunch">Branch *</Label>
                    <Input
                        id="edit-brunch"
                        value={editFormData.brunch || ''}
                        onChange={(e) => setEditFormData({...editFormData, brunch: e.target.value})}
                        required
                    />
                </div>
<div>
    <Label htmlFor="edit-recipant">Recipient Department *</Label>
    <Select
        value={editFormData.recipant || ''}
        onValueChange={(value) => setEditFormData({...editFormData, recipant: value})}
    >
        <SelectTrigger id="edit-recipant">
            <SelectValue placeholder="Select recipient department" />
        </SelectTrigger>
        <SelectContent>
            {availableDepartments.map((dept) => (
                <SelectItem key={dept.id} value={dept.name}>
                    {dept.name}
                </SelectItem>
            ))}
        </SelectContent>
    </Select>
</div>

                {/* Category */}
                <div>
                    <Label htmlFor="edit-category">Category *</Label>
                    <Select
                        value={editFormData.category || ''}
                        onValueChange={(value) => setEditFormData({...editFormData, category: value})}
                    >
                        <SelectTrigger id="edit-category">
                            <SelectValue placeholder="Select a category" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="loan">Loan Inquiry</SelectItem>
                            <SelectItem value="technical">Technical Issue</SelectItem>
                            <SelectItem value="billing">Billing</SelectItem>
                            <SelectItem value="account">Account Management</SelectItem>
                            <SelectItem value="complaint">Customer Complaint</SelectItem>
                            <SelectItem value="suggestion">Improvement Suggestion</SelectItem>
                            <SelectItem value="other">Other</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {/* Priority */}
                <div>
                    <Label htmlFor="edit-priority">Priority *</Label>
                    <Select
                        value={editFormData.priority || ''}
                        onValueChange={(value) => setEditFormData({...editFormData, priority: value})}
                    >
                        <SelectTrigger id="edit-priority">
                            <SelectValue placeholder="Select priority level" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="low">Low</SelectItem>
                            <SelectItem value="medium">Medium</SelectItem>
                            <SelectItem value="high">High</SelectItem>
                            <SelectItem value="critical">Critical</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {/* Attachment (Read-only info) */}
                {selectedTicket.attachment && (
                    <div>
                        <Label>Current Attachment</Label>
                        <p className="text-sm text-muted-foreground">
                            {selectedTicket.attachment}
                        </p>
                        <p className="text-xs text-muted-foreground mt-1">
                            To change the attachment, please create a new ticket.
                        </p>
                    </div>
                )}

                <div className="flex gap-2 justify-end pt-4">
                    <Button 
                        type="button" 
                        variant="outline" 
                        onClick={() => setIsEditDialogOpen(false)}
                    >
                        Cancel
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? "Saving..." : "Save Changes"}
                    </Button>
                </div>

                            </form>
                        )}
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}