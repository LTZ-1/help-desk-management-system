// resolver-my-tickets-tab.tsx - Resolver-specific my tickets component
"use client"
import * as React from "react"
import { useState, useEffect } from "react"
import { usePage, router } from "@inertiajs/react"
import {
  ColumnDef,
  ColumnFiltersState,
  flexRender,
  getCoreRowModel,
  getFacetedRowModel,
  getFacetedUniqueValues,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  SortingState,
  VisibilityState,
  useReactTable,
} from "@tanstack/react-table"
import { ArrowUpDown, ChevronDown, MoreHorizontal, Search, X, Filter } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import {
  Alert,
  AlertDescription,
  AlertTitle,
} from "@/components/ui/alert"
import { AlertCircle } from "lucide-react"
import { toast } from "sonner"
import { Drawer, DrawerContent, DrawerHeader, DrawerTitle } from "@/components/ui/drawer"
import { Badge } from "@/components/ui/badge"
import { Separator } from "@/components/ui/separator"
import { User as UserIcon, Mail, Calendar as CalendarIcon, Clock, CheckCircle } from "lucide-react"

// Resolver ticket interface
interface ResolverTicket {
  id: number
  ticket_number: string
  subject: string
  description: string
  category: string
  priority: string
  status: string
  created_at: string
  due_date: string | null
  assigned_to: number | null
  assignment_type: string
  resolver_id: number | null
  assigned_resolver_id: number | null
  group_id: number | null
  assigned_at: string | null
  assigned_by: number | null
  resolved_at: string | null
  // Additional fields for resolver's My Tickets tab
  requester_id: number
  requester_name: string
  requester_email: string
  requester_type: string
}

interface User {
  id: number
  name: string
  email: string
  department_id: number
  is_admin: boolean
  is_resolver: boolean
  is_none: boolean
}

interface PageProps {
  auth: {
    user: User
  }
  resolverData?: {
    tickets: ResolverTicket[]
  }
  [key: string]: any
}

const priorityColors: Record<string, string> = {
  low: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200",
  medium: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200",
  high: "bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200",
  critical: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200",
}

const statusColors: Record<string, string> = {
  open: "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200",
  assigned: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200",
  in_progress: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200",
  resolved: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200",
  closed: "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200",
}

interface ResolverMyTicketsTabProps {
  onTicketsUpdate?: (tickets: ResolverTicket[]) => void
  onLoadingState?: (loading: boolean) => void
  onErrorState?: (error: string) => void
}

export function ResolverMyTicketsTab({ onTicketsUpdate, onLoadingState, onErrorState }: ResolverMyTicketsTabProps) {
  const { props } = usePage<PageProps>()
  const user = props.auth.user
  
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [tickets, setTickets] = useState<ResolverTicket[]>([])
  const [sorting, setSorting] = useState<SortingState>([])
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([])
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({})
  const [rowSelection, setRowSelection] = useState({})
  
  // Filters state - same as admin's My Tickets tab
  const [filters, setFilters] = useState({
    status: '',
    priority: '',
    category: '',
    search: ''
  })

  // Ticket details drawer state
  const [selectedTicket, setSelectedTicket] = useState<ResolverTicket | null>(null)
  const [detailsOpen, setDetailsOpen] = useState(false)

  // Direct fetch function - uses direct database fetching like admin's my tickets
  const fetchMyTickets = async () => {
    console.log('=== STARTING FETCH RESOLVER MY TICKETS ===')
    setLoading(true)
    setError(null)
    
    // Notify parent of loading state
    if (onLoadingState) onLoadingState(true)
    if (onErrorState) onErrorState(null as any)
    
    try {
      console.log('Fetching resolver my tickets...')
      
      // Use the correct endpoint for resolver's assigned tickets
      const queryParams = new URLSearchParams()
      if (filters.status) queryParams.append('status', filters.status)
      if (filters.priority) queryParams.append('priority', filters.priority)
      if (filters.category) queryParams.append('category', filters.category)
      if (filters.search) queryParams.append('search', filters.search)
      
      const response = await fetch(`/resolver/my-tickets?${queryParams.toString()}`, {
        method: 'GET',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        }
      })
      
      console.log('Response status:', response.status)
      
      if (!response.ok) {
        console.error('Response not OK:', response.status, response.statusText)
        throw new Error(`HTTP ${response.status}: ${response.statusText}`)
      }
      
      const result = await response.json()
      console.log('=== FULL API RESPONSE ===')
      console.log('Response type:', typeof result)
      console.log('Is array:', Array.isArray(result))
      console.log('Has tickets property:', 'tickets' in result)
      console.log('Full response data:', result)
      
      if (result.error) {
        console.error('API returned error:', result.error)
        throw new Error(result.error)
      }
      
      // Handle backend response structure: { tickets: formattedTickets, ... }
      const tickets = result.tickets || []
      console.log('=== TICKETS EXTRACTION ===')
      console.log('Tickets extracted:', tickets)
      console.log('Tickets length:', tickets.length)
      console.log('First ticket sample:', tickets[0])
      
      // The backend already formats the tickets correctly, so use them directly
      setTickets(tickets)
      console.log('=== DATA SET IN STATE ===')
      console.log('Data set in state. Current data length:', tickets.length)
      
      // Pass tickets data to parent dashboard
      if (onTicketsUpdate) {
        onTicketsUpdate(tickets)
      }
      
    } catch (err) {
      console.error('=== FETCH ERROR ===')
      console.error('Error:', err)
      const message = err instanceof Error ? err.message : 'Unknown error'
      setError(message)
      toast.error(message)
      setTickets([]) // Always set empty array to prevent blank page
      
      // Pass error to parent dashboard
      if (onErrorState) onErrorState(message)
      
      // Pass empty tickets to parent dashboard on error
      if (onTicketsUpdate) {
        onTicketsUpdate([])
      }
    } finally {
      console.log('=== FETCH COMPLETED ===')
      setLoading(false)
      
      // Notify parent of loading completion
      if (onLoadingState) onLoadingState(false)
    }
  }

  useEffect(() => {
    fetchMyTickets()
  }, [])

  // Refetch when filters change - same as admin's My Tickets tab
  useEffect(() => {
    fetchMyTickets()
  }, [filters])

  // Handle ticket row click to show details
  const handleTicketClick = (ticket: ResolverTicket) => {
    setSelectedTicket(ticket)
    setDetailsOpen(true)
  }

  const columns: ColumnDef<ResolverTicket>[] = [
    {
      accessorKey: "ticket_number",
      header: "Ticket #",
      cell: ({ row }) => (
        <div className="font-medium">{row.getValue("ticket_number")}</div>
      ),
    },
    {
      accessorKey: "subject",
      header: "Subject",
      cell: ({ row }) => (
        <div className="max-w-xs truncate" title={row.getValue("subject")}>
          {row.getValue("subject")}
        </div>
      ),
    },
    {
      accessorKey: "category",
      header: "Category",
      cell: ({ row }) => (
        <div className="capitalize">{row.getValue("category")}</div>
      ),
    },
    {
      accessorKey: "priority",
      header: "Priority",
      cell: ({ row }) => {
        const priority = row.getValue("priority") as string
        return (
          <div className={`px-2 py-1 rounded-full text-xs font-medium capitalize ${priorityColors[priority] || 'bg-gray-100 text-gray-800'}`}>
            {priority}
          </div>
        )
      },
    },
    {
      accessorKey: "status",
      header: "Status",
      cell: ({ row }) => {
        const status = row.getValue("status") as string
        return (
          <div className={`px-2 py-1 rounded-full text-xs font-medium capitalize ${statusColors[status] || 'bg-gray-100 text-gray-800'}`}>
            {status.replace('_', ' ')}
          </div>
        )
      },
    },
    {
      accessorKey: "assignment_type",
      header: "Assignment",
      cell: ({ row }) => {
        const assignmentType = row.getValue("assignment_type") as string
        return (
          <div className="capitalize">
            {assignmentType === 'individual' ? 'Individual' : assignmentType === 'group' ? 'Group' : 'Unassigned'}
          </div>
        )
      },
    },
    {
      accessorKey: "assigned_to",
      header: "Assigned To",
      cell: ({ row }) => <div>{row.getValue("assigned_to") || "Unassigned"}</div>,
    },
    {
      accessorKey: "due_date",
      header: "Due Date",
      cell: ({ row }) => {
        const dueDate = row.getValue("due_date") as string
        if (!dueDate) return <div className="text-gray-500">-</div>
        
        const date = new Date(dueDate)
        const isOverdue = date < new Date() && row.getValue("status") !== 'resolved'
        
        return (
          <div className={`text-sm ${isOverdue ? 'text-red-600 font-medium' : ''}`}>
            {date.toLocaleDateString()}
          </div>
        )
      },
    },
    // Additional columns for My Tickets tab - same as admin
    {
      accessorKey: "requester_id",
      header: "Requester ID",
      cell: ({ row }) => <div>{row.getValue("requester_id")}</div>,
    },
    {
      accessorKey: "requester_type",
      header: "Requester Type",
      cell: ({ row }) => <div>{row.getValue("requester_type")}</div>,
    },
    {
      accessorKey: "requester_name",
      header: "Requester Name",
      cell: ({ row }) => <div>{row.getValue("requester_name")}</div>,
    },
    {
      accessorKey: "requester_email",
      header: "Requester Email",
      cell: ({ row }) => <div>{row.getValue("requester_email")}</div>,
    },
    {
      accessorKey: "assigned_resolver_id",
      header: "Assigned Resolver ID",
      cell: ({ row }) => {
        const assignmentType = row.getValue("assignment_type")
        if (assignmentType === 'group') {
          return <div>Group: {row.getValue("group_id")}</div>
        }
        return <div>{row.getValue("assigned_resolver_id")}</div>
      },
    },
    {
      accessorKey: "group_id",
      header: "Group ID",
      cell: ({ row }) => <div>{row.getValue("group_id") || "N/A"}</div>,
    },
    {
      accessorKey: "assigned_at",
      header: "Assigned At",
      cell: ({ row }) => {
        const date = row.getValue("assigned_at") as string | null
        return date ? new Date(date).toLocaleDateString() : "Not assigned"
      },
    },
    {
      accessorKey: "resolved_at",
      header: "Resolved At",
      cell: ({ row }) => {
        const date = row.getValue("resolved_at") as string | null
        return date ? new Date(date).toLocaleDateString() : "Not resolved"
      },
    },
    {
      id: "actions",
      enableHiding: false,
      cell: ({ row }) => {
        const ticket = row.original
        
        return (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreHorizontal className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuLabel>Actions</DropdownMenuLabel>
              <DropdownMenuItem
                onClick={() => navigator.clipboard.writeText(ticket.ticket_number)}
              >
                Copy ticket number
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem>View details</DropdownMenuItem>
              <DropdownMenuItem>Update status</DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        )
      },
    },
  ]

  const table = useReactTable({
    data: tickets,
    columns,
    onSortingChange: setSorting,
    onColumnFiltersChange: setColumnFilters,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getFacetedRowModel: getFacetedRowModel(),
    getFacetedUniqueValues: getFacetedUniqueValues(),
    onColumnVisibilityChange: setColumnVisibility,
    onRowSelectionChange: setRowSelection,
    state: {
      sorting,
      columnFilters,
      columnVisibility,
      rowSelection,
    },
  })

  const clearFilters = () => {
    setColumnFilters([])
    table.resetColumnFilters()
  }

  const hasActiveFilters = columnFilters.length > 0

  return (
    <div className="w-full space-y-4">
      {/* Error Alert */}
      {error && (
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertTitle>Error</AlertTitle>
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      {/* Filters */}
      <div className="flex flex-col lg:flex-row gap-4 p-4 bg-muted/50 rounded-lg">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div className="flex flex-col sm:flex-row gap-2">
            <label className="text-sm font-medium flex items-center">Status</label>
            <select
              value={filters.status}
              onChange={(e) => setFilters(prev => ({ ...prev, status: e.target.value }))}
              className="px-3 py-2 border rounded-md text-sm"
            >
              <option value="">All Status</option>
              <option value="unassigned">Unassigned</option>
              <option value="assigned">Assigned</option>
              <option value="in_progress">In Progress</option>
              <option value="resolved">Resolved</option>
              <option value="closed">Closed</option>
            </select>
          </div>

          <div className="flex flex-col sm:flex-row gap-2">
            <label className="text-sm font-medium flex items-center">Priority</label>
            <select
              value={filters.priority}
              onChange={(e) => setFilters(prev => ({ ...prev, priority: e.target.value }))}
              className="px-3 py-2 border rounded-md text-sm"
            >
              <option value="">All Priority</option>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>

          <div className="flex flex-col sm:flex-row gap-2">
            <label className="text-sm font-medium flex items-center">Category</label>
            <select
              value={filters.category}
              onChange={(e) => setFilters(prev => ({ ...prev, category: e.target.value }))}
              className="px-3 py-2 border rounded-md text-sm"
            >
              <option value="">All Categories</option>
              <option value="technical">Technical</option>
              <option value="billing">Billing</option>
              <option value="support">Support</option>
              <option value="general">General</option>
            </select>
          </div>

          <div className="flex flex-col sm:flex-row gap-2">
            <label className="text-sm font-medium flex items-center">Search</label>
            <div className="relative">
              <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search tickets..."
                value={filters.search}
                onChange={(e) => setFilters(prev => ({ ...prev, search: e.target.value }))}
                className="pl-8"
              />
            </div>
          </div>
        </div>

        <div className="flex items-center gap-2">
          <Button variant="outline" size="sm" onClick={() => setFilters({ status: '', priority: '', category: '', search: '' })}>
            <X className="h-4 w-4 mr-2" />
            Clear
          </Button>
        </div>
      </div>

      {/* Data Table */}
      <div className="rounded-md border">
        <Table>
          <TableHeader>
            {table.getHeaderGroups().map((headerGroup) => (
              <TableRow key={headerGroup.id}>
                {headerGroup.headers.map((header) => (
                  <TableHead key={header.id}>
                    {header.isPlaceholder
                      ? null
                      : flexRender(
                          header.column.columnDef.header,
                          header.getContext()
                        )}
                  </TableHead>
                ))}
              </TableRow>
            ))}
          </TableHeader>
          <TableBody>
            {table.getRowModel().rows?.length ? (
              table.getRowModel().rows.map((row) => (
                <TableRow
                  key={row.id}
                  data-state={row.getIsSelected() && "selected"}
                  className="cursor-pointer hover:bg-muted/50"
                  onClick={() => handleTicketClick(row.original)}
                >
                  {row.getVisibleCells().map((cell) => (
                    <TableCell key={cell.id}>
                      {flexRender(
                        cell.column.columnDef.cell,
                        cell.getContext()
                      )}
                    </TableCell>
                  ))}
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell
                  colSpan={columns.length}
                  className="h-24 text-center"
                >
                  {loading ? "Loading tickets..." : "No tickets found."}
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>

      {/* Pagination */}
      <div className="flex items-center justify-end space-x-2 py-4">
        <div className="flex-1 text-sm text-muted-foreground">
          {table.getFilteredSelectedRowModel().rows.length} of{" "}
          {table.getFilteredRowModel().rows.length} row(s) selected.
        </div>
        <div className="space-x-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => table.previousPage()}
            disabled={!table.getCanPreviousPage()}
          >
            Previous
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={() => table.nextPage()}
            disabled={!table.getCanNextPage()}
          >
            Next
          </Button>
        </div>
      </div>

      {/* Ticket Details Drawer */}
      <Drawer open={detailsOpen} onOpenChange={setDetailsOpen}>
        <DrawerContent>
          <DrawerHeader>
            <DrawerTitle>Ticket Details - {selectedTicket?.ticket_number}</DrawerTitle>
          </DrawerHeader>
          <div className="p-4 space-y-4">
            {selectedTicket && (
              <>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <h4 className="font-semibold text-sm text-muted-foreground">Subject</h4>
                    <p className="font-medium">{selectedTicket.subject}</p>
                  </div>
                  <div>
                    <h4 className="font-semibold text-sm text-muted-foreground">Status</h4>
                    <Badge className={statusColors[selectedTicket.status] || 'bg-gray-100 text-gray-800'}>
                      {selectedTicket.status.replace('_', ' ')}
                    </Badge>
                  </div>
                  <div>
                    <h4 className="font-semibold text-sm text-muted-foreground">Priority</h4>
                    <Badge className={priorityColors[selectedTicket.priority] || 'bg-gray-100 text-gray-800'}>
                      {selectedTicket.priority}
                    </Badge>
                  </div>
                  <div>
                    <h4 className="font-semibold text-sm text-muted-foreground">Category</h4>
                    <p className="capitalize">{selectedTicket.category}</p>
                  </div>
                </div>

                <Separator />

                <div>
                  <h4 className="font-semibold text-sm text-muted-foreground mb-2">Description</h4>
                  <p className="text-sm">{selectedTicket.description}</p>
                </div>

                <Separator />

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <h4 className="font-semibold text-sm text-muted-foreground flex items-center gap-2">
                      <UserIcon className="h-4 w-4" />
                      Requester
                    </h4>
                    <p className="font-medium">{selectedTicket.requester_name}</p>
                    <p className="text-sm text-muted-foreground">{selectedTicket.requester_email}</p>
                    <Badge variant="outline" className="mt-1">
                      {selectedTicket.requester_type}
                    </Badge>
                  </div>
                  <div>
                    <h4 className="font-semibold text-sm text-muted-foreground flex items-center gap-2">
                      <CalendarIcon className="h-4 w-4" />
                      Dates
                    </h4>
                    <div className="space-y-1 text-sm">
                      <p>Created: {new Date(selectedTicket.created_at).toLocaleDateString()}</p>
                      {selectedTicket.due_date && (
                        <p>Due: {new Date(selectedTicket.due_date).toLocaleDateString()}</p>
                      )}
                      {selectedTicket.assigned_at && (
                        <p>Assigned: {new Date(selectedTicket.assigned_at).toLocaleDateString()}</p>
                      )}
                      {selectedTicket.resolved_at && (
                        <p>Resolved: {new Date(selectedTicket.resolved_at).toLocaleDateString()}</p>
                      )}
                    </div>
                  </div>
                </div>

                <Separator />

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <h4 className="font-semibold text-sm text-muted-foreground">Assignment</h4>
                    <p className="text-sm">Type: <span className="capitalize">{selectedTicket.assignment_type}</span></p>
                    <p className="text-sm">Assigned to: {selectedTicket.assigned_resolver_id || 'Unassigned'}</p>
                    {selectedTicket.group_id && (
                      <p className="text-sm">Group ID: {selectedTicket.group_id}</p>
                    )}
                  </div>
                  <div>
                    <h4 className="font-semibold text-sm text-muted-foreground">Details</h4>
                    <p className="text-sm">Ticket ID: {selectedTicket.id}</p>
                    <p className="text-sm">Requester ID: {selectedTicket.requester_id}</p>
                  </div>
                </div>
              </>
            )}
          </div>
        </DrawerContent>
      </Drawer>
    </div>
  )
}
