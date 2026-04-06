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
import { ArrowUpDown, ChevronDown, MoreHorizontal, Search, X, Filter, Calendar, User } from "lucide-react"
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

// Resolver ticket interface
interface ResolverTicket {
  id: number
  ticket_number: string
  subject: string
  description: string
  category: string
  priority: string
  status: string
  assignment_type: string
  assigned_resolver_id: number
  assignment_group_id?: number
  due_date?: string
  created_at: string
  requester_name: string
  requester_email: string
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

export function ResolverMyTicketsTab() {
  const { props } = usePage<PageProps>()
  const user = props.auth.user
  
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [tickets, setTickets] = useState<ResolverTicket[]>([])
  const [sorting, setSorting] = useState<SortingState>([])
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([])
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({})
  const [rowSelection, setRowSelection] = useState({})

  // Direct fetch function - uses direct database fetching like admin's my tickets
  const fetchMyTickets = async () => {
    console.log('=== STARTING FETCH RESOLVER MY TICKETS ===')
    setLoading(true)
    setError(null)
    
    try {
      console.log('Fetching resolver my tickets...')
      
      // Use the correct endpoint for resolver's assigned tickets
      const response = await fetch('/resolver/my-tickets', {
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
      
    } catch (err) {
      console.error('=== FETCH ERROR ===')
      console.error('Error:', err)
      const message = err instanceof Error ? err.message : 'Unknown error'
      setError(message)
      toast.error(message)
      setTickets([]) // Always set empty array to prevent blank page
    } finally {
      console.log('=== FETCH COMPLETED ===')
      setLoading(false)
    }
  }

  useEffect(() => {
    fetchMyTickets()
  }, [])

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
    <div className="space-y-4">
      {/* Error Alert */}
      {error && (
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertTitle>Error</AlertTitle>
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      {/* Filters */}
      <div className="flex items-center justify-between">
        <div className="flex flex-1 items-center space-x-2">
          <Input
            placeholder="Filter tickets..."
            value={(table.getColumn("ticket_number")?.getFilterValue() as string) ?? ""}
            onChange={(event) =>
              table.getColumn("ticket_number")?.setFilterValue(event.target.value)
            }
            className="max-w-sm"
          />
          {hasActiveFilters && (
            <Button variant="outline" onClick={clearFilters} className="flex items-center gap-2">
              <X className="h-4 w-4" />
              Clear Filters
            </Button>
          )}
        </div>
      </div>

      {/* Table */}
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
    </div>
  )
}
